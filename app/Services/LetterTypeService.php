<?php

namespace App\Services;

use App\Models\LetterType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LetterTypeService
{
    /**
     * Create a new letter type.
     */
    public function create(array $data): LetterType
    {
        return LetterType::create($data);
    }

    /**
     * Update a letter type.
     */
    public function update(LetterType $type, array $data): LetterType
    {
        $type->update($data);

        return $type->fresh();
    }

    /**
     * Soft delete a letter type (only if no letters use it).
     */
    public function delete(LetterType $type): void
    {
        if ($type->letters()->count() > 0) {
            throw new \DomainException('Jenis surat tidak bisa dihapus karena sudah digunakan oleh '.$type->letters()->count().' surat.');
        }

        $type->delete();
    }

    /**
     * Restore a soft-deleted letter type.
     */
    public function restore(LetterType $type): void
    {
        $type->restore();
    }

    /**
     * Upload a template PDF for a letter type.
     */
    public function uploadTemplate(LetterType $type, UploadedFile $file, int $uploadedBy): string
    {
        validator([
            'file' => $file,
        ], [
            'file' => 'required|file|mimes:pdf|max:5120',
        ])->validate();

        // Validate PDF magic bytes
        $handle = fopen($file->getPathname(), 'r');
        $firstBytes = fread($handle, 5);
        fclose($handle);

        if ($firstBytes !== '%PDF-') {
            throw new \DomainException('File bukan PDF yang valid.');
        }

        // Backup existing template
        if ($type->template_file_path && Storage::disk('local')->exists($type->template_file_path)) {
            $backupPath = 'letter-templates/backup/'.Str::slug($type->code).'-'.now()->format('Ymd-His').'.pdf';
            Storage::disk('local')->copy($type->template_file_path, $backupPath);

            // Cleanup old backups (keep max 5)
            $this->cleanupBackups($type->code);

            // Delete old template
            Storage::disk('local')->delete($type->template_file_path);
        }

        $filename = 'letter-templates/'.Str::slug($type->code).'.pdf';
        Storage::disk('local')->put($filename, file_get_contents($file));

        $type->update([
            'template_file_path' => $filename,
            'template_file_original_name' => $file->getClientOriginalName(),
            'template_file_size' => $file->getSize(),
            'template_uploaded_at' => now(),
            'template_uploaded_by' => $uploadedBy,
        ]);

        return $filename;
    }

    /**
     * Download a template PDF.
     */
    public function downloadTemplate(LetterType $type): StreamedResponse
    {
        if (! $type->template_file_path || ! Storage::disk('local')->exists($type->template_file_path)) {
            abort(404, 'Template tidak ditemukan.');
        }

        return Storage::disk('local')->download(
            $type->template_file_path,
            $type->template_file_original_name ?? ($type->code.'.pdf')
        );
    }

    /**
     * Delete a template PDF.
     */
    public function deleteTemplate(LetterType $type): void
    {
        if ($type->template_file_path && Storage::disk('local')->exists($type->template_file_path)) {
            Storage::disk('local')->delete($type->template_file_path);
        }

        $type->update([
            'template_file_path' => null,
            'template_file_original_name' => null,
            'template_file_size' => null,
            'template_uploaded_at' => null,
            'template_uploaded_by' => null,
        ]);
    }

    /**
     * Get all active letter types.
     */
    public function getActiveTypes()
    {
        return LetterType::where('is_active', true)->orderBy('code')->get();
    }

    /**
     * Cleanup old backups, keeping max 5 per template.
     */
    private function cleanupBackups(string $code): void
    {
        $backupDir = 'letter-templates/backup';
        $files = Storage::disk('local')->files($backupDir);
        $codeFiles = array_filter($files, fn ($f) => str_starts_with(basename($f), Str::slug($code)));

        if (count($codeFiles) > 5) {
            usort($codeFiles, fn ($a, $b) => Storage::disk('local')->lastModified($a) <=> Storage::disk('local')->lastModified($b));
            $toDelete = array_slice($codeFiles, 0, count($codeFiles) - 5);
            foreach ($toDelete as $file) {
                Storage::disk('local')->delete($file);
            }
        }
    }
}
