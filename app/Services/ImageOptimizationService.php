<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ImageOptimizationService
{
    public function optimizeAndStore(UploadedFile $file, string $directory, ?string $filename = null, string $disk = 'public'): array
    {
        $filename ??= Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());
        $tmpPath = $this->createTemporaryPath($filename);

        $sourcePath = $file->getRealPath();
        if (!is_file($sourcePath)) {
            throw new RuntimeException('The uploaded file could not be read.');
        }

        try {
            $imageInfo = @getimagesize($sourcePath);
            if ($imageInfo === false) {
                throw new RuntimeException('The uploaded file is not a valid image.');
            }

            [$width, $height, $type] = $imageInfo;
            $this->prepareMemoryLimit($width, $height);

            $image = $this->loadImage($sourcePath, $file->getClientMimeType());
            if ($image === false) {
                throw new RuntimeException('The uploaded file is not a valid image.');
            }

            $targetDimensions = $this->resolveTargetDimensions($width, $height);
            if ($targetDimensions !== null) {
                $image = $this->resizeImage($image, $targetDimensions['width'], $targetDimensions['height']);
            }

            $outputMime = $this->chooseOutputMime($file->getClientMimeType(), $imageInfo, $image);
            $optimizedPath = $this->saveOptimizedImage($image, $tmpPath, $outputMime);
            imagedestroy($image);

            $storedPath = Storage::disk($disk)->putFileAs($directory, new UploadedFile($optimizedPath, $this->changeExtension($filename, $outputMime), $outputMime, null, true), $this->changeExtension($filename, $outputMime));
            @unlink($optimizedPath);

            if ($storedPath === false) {
                throw new RuntimeException('The optimized image could not be stored.');
            }

            return [
                'path' => $storedPath,
                'size' => Storage::disk($disk)->size($storedPath),
            ];
        } catch (Throwable $exception) {
            report($exception);

            $storedPath = Storage::disk($disk)->putFileAs($directory, $file, $filename);
            if ($storedPath === false) {
                throw new RuntimeException('The image could not be stored.');
            }

            return [
                'path' => $storedPath,
                'size' => Storage::disk($disk)->size($storedPath),
            ];
        }
    }

    public function optimizeAndStoreMany(array $files, string $directory): array
    {
        $result = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $result[] = $this->optimizeAndStore($file, $directory);
            }
        }

        return $result;
    }

    private function createTemporaryPath(string $filename): string
    {
        $tempDir = storage_path('framework/cache/images');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        return $tempDir . '/' . Str::uuid() . '-' . basename($filename);
    }

    private function loadImage(string $sourcePath, string $mimeType)
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($sourcePath),
            'image/png' => imagecreatefrompng($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
            default => false,
        };
    }

    private function saveOptimizedImage($image, string $outputPath, string $mimeType): string
    {
        $quality = $this->qualityForMime($mimeType);

        if ($mimeType === 'image/png') {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
            imagepng($image, $outputPath, 8);
            return $outputPath;
        }

        if ($mimeType === 'image/webp') {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
            imagewebp($image, $outputPath, $quality);
            return $outputPath;
        }

        imageinterlace($image, true);
        imagejpeg($image, $outputPath, $quality);

        return $outputPath;
    }

    private function prepareMemoryLimit(int $width, int $height): void
    {
        $estimatedBytes = max($width * $height * 4 * 2, 64 * 1024 * 1024);
        $currentLimit = ini_get('memory_limit');

        if ($currentLimit === '-1') {
            return;
        }

        $currentBytes = $this->parseMemoryLimit($currentLimit);
        if ($currentBytes === null || $currentBytes >= $estimatedBytes) {
            return;
        }

        ini_set('memory_limit', '1G');
        ini_set('max_execution_time', '600');
    }

    private function resolveTargetDimensions(int $width, int $height): ?array
    {
        $maxDimension = 4096;
        $maxPixels = 8_000_000;
        $pixelCount = $width * $height;

        if ($width <= $maxDimension && $height <= $maxDimension && $pixelCount <= $maxPixels) {
            return null;
        }

        $scale = min(1.0, min($maxDimension / max($width, 1), $maxDimension / max($height, 1)));
        $pixelScale = sqrt($maxPixels / max($pixelCount, 1));
        $finalScale = min($scale, $pixelScale);

        return [
            'width' => max(1, (int) round($width * $finalScale)),
            'height' => max(1, (int) round($height * $finalScale)),
        ];
    }

    private function resizeImage($image, int $width, int $height)
    {
        $resized = imagecreatetruecolor($width, $height);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
        imagefilledrectangle($resized, 0, 0, $width, $height, $transparent);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
        imagedestroy($image);

        return $resized;
    }

    private function parseMemoryLimit(string $limit): ?int
    {
        if ($limit === '-1') {
            return null;
        }

        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    private function chooseOutputMime(string $mimeType, array $imageInfo, $image): string
    {
        $hasTransparency = $this->hasTransparency($image);

        if ($hasTransparency) {
            return 'image/png';
        }

        return $mimeType === 'image/jpg' ? 'image/jpeg' : $mimeType;
    }

    private function hasTransparency($image): bool
    {
        $width = imagesx($image);
        $height = imagesy($image);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba >> 24) & 0xFF;
                if ($alpha > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    private function changeExtension(string $filename, string $mimeType): string
    {
        $extension = match ($mimeType) {
            'image/webp' => 'webp',
            'image/avif' => 'avif',
            'image/png' => 'png',
            default => 'jpg',
        };

        return preg_replace('/\.[^.]+$/', '', $filename) . '.' . $extension;
    }

    private function qualityForMime(string $mimeType): int
    {
        return match ($mimeType) {
            'image/avif' => 82,
            'image/webp' => 82,
            'image/jpeg', 'image/jpg' => 86,
            default => 90,
        };
    }
}
