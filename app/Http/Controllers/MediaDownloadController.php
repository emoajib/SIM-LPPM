<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaDownloadController extends Controller
{
    public function download(Request $request, Media $media)
    {
        $obLevel = ob_get_level();
        ob_start();

        try {
            // 1. Strict UUID Validation
            if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $media->uuid)) {
                abort(400, 'Malformed Identifier');
            }

            // 2. Signed URL Verification
            // The URL is generated via URL::temporarySignedRoute() in Blade views.
            // If the request has a valid signature → skip policy authorization (the URL IS the access token).
            // If no signature → fall back to policy-based authorization for backward compatibility.
            if ($request->has('signature') || $request->has('expires')) {
                if (! URL::hasValidSignature($request)) {
                    Log::warning('MEDIA DOWNLOAD: Invalid or expired signature', [
                        'media_uuid' => $media->uuid,
                        'user_id' => Auth::id(),
                        'ip' => $request->ip(),
                    ]);
                    abort(403, 'Akses Ditolak: Tautan tidak valid atau sudah kadaluwarsa.');
                }
            } else {
                // 2b. Policy-Based Authorization (for non-signed requests)
                $this->authorize('download', $media);
            }

            $diskName = config('media-library.disk_name', 'public');

            // 3. Path Traversal & Existence Check for local disks
            // Vetted by AI - Manual Review Required by Senior Engineer/Manager
            // IMPORTANT: $media->getPath() may return a RELATIVE path (via CustomPathGenerator).
            // realpath() on a relative path resolves from PHP's CWD (not disk root) → always fails → 404.
            // Fix: use getPathRelativeToRoot() + disk->path() to build the correct absolute path first.
            $disk = Storage::disk($diskName);
            $relativePath = $media->getPathRelativeToRoot();

            if (str_contains($relativePath, '..')) {
                abort(403, 'Invalid file path.');
            }

            $absolutePath = $disk->path($relativePath);
            $realPath = realpath($absolutePath);

            // Fallback: coba path lama format {collection}/{modelslug}-{id8}/{media_id}/{filename}
            // File yang diupload sebelum CustomPathGenerator diupdate ke format NIDN masih ada di path lama.
            if ($realPath === false) {
                $modelSlug = Str::slug(class_basename($media->model_type));
                $modelId8  = is_string($media->model_id) ? substr($media->model_id, 0, 8) : $media->model_id;
                $legacyRel = $media->collection_name.'/'.$modelSlug.'-'.$modelId8.'/'.$media->id.'/'.$media->file_name;
                $legacyAbs = $disk->path($legacyRel);
                $realPath  = realpath($legacyAbs);

                if ($realPath !== false) {
                    Log::info('MediaDownload: resolved via legacy path fallback', [
                        'media_id'  => $media->id,
                        'legacy'    => $legacyRel,
                        'user_id'   => Auth::id(),
                    ]);
                } else {
                    Log::warning('MediaDownload: file not found at primary or fallback path', [
                        'media_id' => $media->id,
                        'primary'  => $relativePath,
                        'legacy'   => $legacyRel,
                    ]);
                    abort(404, 'File fisik tidak ditemukan di server.');
                }
            }

            // Security Barrier: Ensure path is within the disk's root
            $diskRoot = realpath($disk->path(''));
            if ($diskRoot === false || ! str_starts_with($realPath, $diskRoot)) {
                abort(403, 'Path traversal detected or illegal file path access.');
            }

            // 4. Runtime Integrity Check (Actual MIME vs Record MIME)
            $mimeAliases = [
                'image/jpg' => 'image/jpeg',
                'image/pjpeg' => 'image/jpeg',
                'image/x-png' => 'image/png',
            ];
            $actualMime = mime_content_type($realPath);
            $normalizeActual = $mimeAliases[$actualMime] ?? $actualMime;
            // Robustly normalize both recorded and actual MIME types before comparison.
            // This handles case-insensitivity and extra parameters (e.g., charset) which caused incorrect mismatches.
            $normalizedRecorded = $media->mime_type;
            if ($normalizedRecorded) {
                $baseMime = strtolower(explode(';', $normalizedRecorded, 2)[0]);
                $normalizedRecorded = $mimeAliases[$baseMime] ?? $baseMime;
            }

            $normalizedActualForCheck = $actualMime;
            if ($normalizedActualForCheck) {
                $baseMime = strtolower(explode(';', $normalizedActualForCheck, 2)[0]);
                $normalizedActualForCheck = $mimeAliases[$baseMime] ?? $baseMime;
            }

            if ($normalizedActualForCheck !== $normalizedRecorded) {
                Log::critical('SECURITY ALERT: MIME-Type Mismatch for file '.$media->uuid, [
                    'recorded_mime' => $media->mime_type,
                    'actual_mime' => $actualMime,
                    'user_id' => Auth::id(),
                ]);
                abort(422, 'Data integrity policy violated. File content does not match expected format.');
            }

            // 5. Clean Buffer & Stream Download with Secure Headers
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }

            // Vetted by AI - Manual Review Required by Senior Engineer/Manager
            if ($request->has('preview') || $request->has('view')) {
                return response()->file($realPath, [
                    'Content-Type' => $media->mime_type ?? 'application/pdf',
                    'X-Content-Type-Options' => 'nosniff',
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                ]);
            }

            return response()->download($realPath, $media->file_name, [
                'Content-Type' => $media->mime_type ?? 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'; sandbox",
                'X-Frame-Options' => 'DENY',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
        } catch (\Exception $e) {
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }
            throw $e;
        }
    }
}
