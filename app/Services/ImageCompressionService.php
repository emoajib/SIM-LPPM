<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImageCompressionService
{
    // Vetted by AI - Manual Review Required by Senior Engineer/Manager

    /**
     * Supported image mime types for compression.
     */
    protected const SUPPORTED_MIMES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
    ];

    /**
     * Compress an image if applicable, returning the path to the compressed file
     * or the original file path if compression is skipped or fails.
     *
     * @param  mixed  $file  string filepath, UploadedFile, or TemporaryUploadedFile
     * @param  int  $maxWidth  Default 1920px
     * @param  int  $maxHeight  Default 1920px
     * @param  int  $quality  Default 85%
     * @return string Path to the file to be stored
     */
    public function compressIfImage(
        mixed $file,
        int $maxWidth = 1920,
        int $maxHeight = 1920,
        int $quality = 85
    ): string {
        $realPath = $this->resolveRealPath($file);

        if (! $realPath || ! file_exists($realPath)) {
            return is_string($realPath) ? $realPath : '';
        }

        if (! $this->canCompress($realPath)) {
            return $realPath;
        }

        try {
            return $this->performCompression($realPath, $maxWidth, $maxHeight, $quality);
        } catch (\Throwable $e) {
            Log::warning('Image compression failed, falling back to original: '.$e->getMessage(), [
                'file' => $realPath,
            ]);

            return $realPath;
        }
    }

    /**
     * Resolve the real file path from various input types.
     */
    protected function resolveRealPath(mixed $file): ?string
    {
        if ($file instanceof UploadedFile) {
            $path = $file->getRealPath();
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        if (is_string($file)) {
            return $file;
        }

        return null;
    }

    /**
     * Check if GD extension is available and file is a supported image.
     */
    public function canCompress(string $filePath): bool
    {
        if (! extension_loaded('gd')) {
            return false;
        }

        $mime = @mime_content_type($filePath);
        if (! is_string($mime) || ! in_array($mime, self::SUPPORTED_MIMES, true)) {
            return false;
        }

        return true;
    }

    /**
     * Perform the actual GD resize and compression.
     */
    protected function performCompression(
        string $sourcePath,
        int $maxWidth,
        int $maxHeight,
        int $quality
    ): string {
        $imageInfo = @getimagesize($sourcePath);
        if ($imageInfo === false) {
            return $sourcePath;
        }

        [$width, $height, $imageType] = $imageInfo;

        if ($width <= 0 || $height <= 0) {
            return $sourcePath;
        }

        /** @var \GdImage|false $sourceImage */
        $sourceImage = match ($imageType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if ($sourceImage === false || ! ($sourceImage instanceof \GdImage)) {
            return $sourcePath;
        }

        // Handle EXIF orientation for JPEGs
        if ($imageType === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $sourceImage = $this->fixExifOrientation($sourcePath, $sourceImage);
            $width = imagesx($sourceImage);
            $height = imagesy($sourceImage);
        }

        $targetDimensions = $this->calculateDimensions($width, $height, $maxWidth, $maxHeight);
        $newWidth = $targetDimensions['width'];
        $newHeight = $targetDimensions['height'];

        $fileSize = filesize($sourcePath) ?: 0;
        // If within dimensions and under 512KB, retain original
        if ($newWidth === $width && $newHeight === $height && $fileSize < 512 * 1024) {
            imagedestroy($sourceImage);

            return $sourcePath;
        }

        /** @var \GdImage|false $targetImage */
        $targetImage = imagecreatetruecolor($newWidth, $newHeight);
        if ($targetImage === false || ! ($targetImage instanceof \GdImage)) {
            imagedestroy($sourceImage);

            return $sourcePath;
        }

        // Preserve transparency for PNG and WebP
        if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_WEBP) {
            imagealphablending($targetImage, false);
            imagesavealpha($targetImage, true);
            $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
            if ($transparent !== false) {
                imagefilledrectangle($targetImage, 0, 0, $newWidth, $newHeight, $transparent);
            }
        }

        imagecopyresampled(
            $targetImage,
            $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $width, $height
        );

        imagedestroy($sourceImage);

        $extension = match ($imageType) {
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            default => 'jpg',
        };

        $tempDestination = tempnam(sys_get_temp_dir(), 'sim_opt_').'.'.$extension;

        $saved = match ($imageType) {
            IMAGETYPE_PNG => imagepng($targetImage, $tempDestination, 8),
            IMAGETYPE_WEBP => imagewebp($targetImage, $tempDestination, $quality),
            default => imagejpeg($targetImage, $tempDestination, $quality),
        };

        imagedestroy($targetImage);

        if (! $saved || ! file_exists($tempDestination)) {
            return $sourcePath;
        }

        // If compressed file is larger or equal and dimensions didn't change, retain original
        $newSize = filesize($tempDestination) ?: 0;
        if ($newSize >= $fileSize && $newWidth === $width && $newHeight === $height) {
            @unlink($tempDestination);

            return $sourcePath;
        }

        return $tempDestination;
    }

    /**
     * Calculate proportional dimensions.
     *
     * @return array{width: int, height: int}
     */
    protected function calculateDimensions(int $width, int $height, int $maxWidth, int $maxHeight): array
    {
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return ['width' => $width, 'height' => $height];
        }

        $ratio = min($maxWidth / $width, $maxHeight / $height);

        return [
            'width' => max(1, (int) round($width * $ratio)),
            'height' => max(1, (int) round($height * $ratio)),
        ];
    }

    /**
     * Rotate image according to EXIF orientation tag if needed.
     */
    protected function fixExifOrientation(string $filePath, \GdImage $image): \GdImage
    {
        try {
            $exif = @exif_read_data($filePath);
            if (! empty($exif['Orientation'])) {
                $orientation = (int) $exif['Orientation'];
                $rotated = match ($orientation) {
                    3 => imagerotate($image, 180, 0),
                    6 => imagerotate($image, -90, 0),
                    8 => imagerotate($image, 90, 0),
                    default => $image,
                };

                if ($rotated !== false) {
                    if ($rotated !== $image) {
                        imagedestroy($image);
                    }

                    return $rotated;
                }
            }
        } catch (\Throwable) {
            // EXIF read error fallback
        }

        return $image;
    }
}
