<?php

namespace Tests\Unit\Services;

use App\Services\ImageOptimizationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageOptimizationServiceTest extends TestCase
{
    public function test_it_optimizes_jpeg_uploads_without_changing_dimensions(): void
    {
        Storage::fake('public');

        $sourcePath = tempnam(sys_get_temp_dir(), 'lenspic-src-');
        $image = imagecreatetruecolor(800, 600);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 0, 0));
        imagejpeg($image, $sourcePath, 100);
        imagedestroy($image);

        $file = new UploadedFile(
            $sourcePath,
            'sample.jpg',
            'image/jpeg',
            null,
            true
        );

        $service = new ImageOptimizationService();
        $result = $service->optimizeAndStore($file, 'photos/test-group', 'sample.jpg');

        $this->assertSame('photos/test-group/sample.jpg', $result['path']);
        $this->assertTrue(Storage::disk('public')->exists($result['path']));
        $this->assertGreaterThan(0, $result['size']);

        [$width, $height] = getimagesize(Storage::disk('public')->path($result['path']));
        $this->assertSame(800, $width);
        $this->assertSame(600, $height);
        $this->assertLessThan(filesize($sourcePath), $result['size']);

        @unlink($sourcePath);
    }
}
