<?php

namespace Tests\Unit;

use App\Services\ImageCompressionService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImageCompressionServiceTest extends TestCase
{
    // Vetted by AI - Manual Review Required by Senior Engineer/Manager

    protected ImageCompressionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImageCompressionService;
    }

    public function test_it_returns_original_if_file_does_not_exist()
    {
        $result = $this->service->compressIfImage('/path/to/nonexistent/file.jpg');
        $this->assertSame('/path/to/nonexistent/file.jpg', $result);
    }

    public function test_it_bypasses_pdf_and_documents()
    {
        $tempPdf = tempnam(sys_get_temp_dir(), 'test_pdf_').'.pdf';
        file_put_contents($tempPdf, '%PDF-1.4 dummy content');

        $result = $this->service->compressIfImage($tempPdf);
        $this->assertSame($tempPdf, $result);

        @unlink($tempPdf);
    }

    public function test_it_compresses_and_resizes_large_jpeg()
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not loaded.');
        }

        // Create a 2400x1800 test image
        $width = 2400;
        $height = 1800;
        $img = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($img, 100, 150, 200);
        imagefilledrectangle($img, 0, 0, $width, $height, $color);

        $sourcePath = tempnam(sys_get_temp_dir(), 'test_large_').'.jpg';
        imagejpeg($img, $sourcePath, 100);
        imagedestroy($img);

        $originalSize = filesize($sourcePath);

        $compressedPath = $this->service->compressIfImage($sourcePath, maxWidth: 1920, maxHeight: 1920, quality: 80);

        $this->assertFileExists($compressedPath);
        $this->assertNotSame($sourcePath, $compressedPath);

        [$newWidth, $newHeight] = getimagesize($compressedPath);
        $this->assertLessThanOrEqual(1920, $newWidth);
        $this->assertLessThanOrEqual(1920, $newHeight);
        $this->assertLessThan($originalSize, filesize($compressedPath));

        @unlink($sourcePath);
        @unlink($compressedPath);
    }

    public function test_it_handles_png_with_transparency()
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not loaded.');
        }

        $width = 2000;
        $height = 1000;
        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefilledrectangle($img, 0, 0, $width, $height, $transparent);

        $sourcePath = tempnam(sys_get_temp_dir(), 'test_png_').'.png';
        imagepng($img, $sourcePath);
        imagedestroy($img);

        $compressedPath = $this->service->compressIfImage($sourcePath, maxWidth: 1000, maxHeight: 1000);

        $this->assertFileExists($compressedPath);
        [$newWidth, $newHeight] = getimagesize($compressedPath);
        $this->assertSame(1000, $newWidth);
        $this->assertSame(500, $newHeight);

        @unlink($sourcePath);
        @unlink($compressedPath);
    }

    public function test_it_accepts_uploaded_file_instance()
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not loaded.');
        }

        $file = UploadedFile::fake()->image('photo.jpg', 2200, 1600);
        $compressedPath = $this->service->compressIfImage($file, maxWidth: 1920, maxHeight: 1920);

        $this->assertFileExists($compressedPath);
        [$newWidth, $newHeight] = getimagesize($compressedPath);
        $this->assertLessThanOrEqual(1920, $newWidth);
        $this->assertLessThanOrEqual(1920, $newHeight);

        @unlink($compressedPath);
    }
}
