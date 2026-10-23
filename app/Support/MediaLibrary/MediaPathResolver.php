<?php

namespace App\Support\MediaLibrary;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaPathResolver
{
    public static function resolve(Media $media): ?string
    {
        $disk = Storage::disk($media->disk ?: config('media-library.disk_name', 'public'));
        $fileName = basename($media->file_name);

        $relativePaths = array_unique(array_filter([
            $media->getPathRelativeToRoot(),
            self::legacyRelativePath($media, $fileName),
        ]));

        foreach ($relativePaths as $relativePath) {
            if (str_contains($relativePath, '..')) {
                continue;
            }

            $absolutePath = $disk->path($relativePath);
            if (is_file($absolutePath)) {
                return $absolutePath;
            }
        }

        return self::findRelocatedFile($disk->path($media->collection_name), $media->id, $fileName);
    }

    private static function legacyRelativePath(Media $media, string $fileName): string
    {
        $modelSlug = Str::slug(class_basename($media->model_type));
        $modelId = is_string($media->model_id) ? substr($media->model_id, 0, 8) : $media->model_id;

        return $media->collection_name.'/'.$modelSlug.'-'.$modelId.'/'.$media->id.'/'.$fileName;
    }

    private static function findRelocatedFile(string $collectionPath, int $mediaId, string $fileName): ?string
    {
        $pattern = rtrim($collectionPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.$mediaId.DIRECTORY_SEPARATOR.$fileName;

        foreach (glob($pattern) ?: [] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
