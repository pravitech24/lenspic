<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Photo;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class FaceRecognitionService
{
    private array $recognitionTempFiles = [];

    public function matchGroupPhotos(Group $group, UploadedFile|string $selfie, Collection|array $photos): Collection
    {
        $endpoint = $this->endpoint();
        $request = Http::acceptJson()->timeout($this->timeout());

        $token = trim((string) config('services.face_recognition.token', ''));
        if ($token !== '') {
            $request = $request->withToken($token);
        }

        $request = $this->attachSelfie($request, $selfie);

        $attachedPhotos = 0;
        foreach ($photos as $photo) {
            $photoPath = $photo instanceof Photo ? $photo->path : (string) $photo;
            $absolutePath = $this->resolvePath($photoPath);
            $photoId = $photo instanceof Photo ? $photo->id : null;

            if (!$absolutePath) {
                continue;
            }

            $recognitionPath = $this->preparePhotoForRecognition($absolutePath);
            if (!$recognitionPath || !is_file($recognitionPath)) {
                continue;
            }

            if ($photoId !== null) {
                $request = $request->attach('photo_ids[]', (string) $photoId);
            }

            $request = $request->attach('photos[]', fopen($recognitionPath, 'r'), basename($recognitionPath));
            $attachedPhotos++;
        }

        if ($attachedPhotos === 0) {
            $this->cleanupTempFiles();
            return collect();
        }

        try {
            $response = $request->post($endpoint, [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'photo_count' => $attachedPhotos,
            ]);
        } catch (ConnectionException $exception) {
            $this->cleanupTempFiles();
            Log::warning('Face recognition API connection failed', [
                'group_id' => $group->id,
                'endpoint' => $endpoint,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Unable to reach the face recognition service.', 0, $exception);
        }

        if (!$response->successful()) {
            $this->cleanupTempFiles();
            Log::warning('Face recognition API request failed', [
                'group_id' => $group->id,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Face recognition service returned an error.');
        }

        $matches = $this->normalizeMatches($response->json());
        $this->cleanupTempFiles();

        return $matches;
    }

    private function endpoint(): string
    {
        $baseUrl = trim((string) config('services.face_recognition.url', ''));

        if ($baseUrl === '') {
            throw new RuntimeException('FACE_RECOGNITION_API_URL is not configured.');
        }

        return rtrim($baseUrl, '/') . '/' . ltrim((string) config('services.face_recognition.match_path', '/recognize'), '/');
    }

    private function timeout(): int
    {
        return max(5, (int) config('services.face_recognition.timeout', 90));
    }

    private function attachSelfie($request, UploadedFile|string $selfie)
    {
        if ($selfie instanceof UploadedFile) {
            $path = $selfie->getRealPath();
            $filename = $selfie->getClientOriginalName();
        } else {
            $path = Storage::disk('public')->path($selfie);
            $filename = basename($path);
        }

        if (!is_file($path)) {
            throw new RuntimeException('Stored selfie was not found.');
        }

        $optimizedSelfie = $this->preparePhotoForRecognition($path, 'selfie');
        if ($optimizedSelfie) {
            return $request->attach('selfie', fopen($optimizedSelfie, 'r'), basename($optimizedSelfie));
        }

        return $request->attach('selfie', fopen($path, 'r'), $filename);
    }

    private function preparePhotoForRecognition(string $absolutePath, string $type = 'photo'): ?string
    {
        $targetPath = sys_get_temp_dir() . '/lenspic-face-' . uniqid() . '.jpg';

        try {
            $image = @imagecreatefromstring(file_get_contents($absolutePath));
            if ($image === false) {
                return is_file($absolutePath) ? $absolutePath : null;
            }

            $width = imagesx($image);
            $height = imagesy($image);
            $maxSide = 600;
            $maxPixels = 360_000;
            $scale = min(1.0, $maxSide / max($width, 1), sqrt($maxPixels / max($width * $height, 1)));

            if ($scale < 1.0) {
                $newWidth = max(1, (int) round($width * $scale));
                $newHeight = max(1, (int) round($height * $scale));
                $resized = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $resized;
            }

            imagejpeg($image, $targetPath, 64);
            imagedestroy($image);
            $this->recognitionTempFiles[] = $targetPath;

            return $targetPath;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function resolvePath(string $path): ?string
    {
        $absolutePath = Storage::disk('public')->path($path);

        return is_file($absolutePath) ? $absolutePath : null;
    }

    private function cleanupTempFiles(): void
    {
        foreach ($this->recognitionTempFiles as $file) {
            @unlink($file);
        }

        $this->recognitionTempFiles = [];
    }

    private function normalizeMatches(array $payload): Collection
    {
        $rawMatches = $payload['matches']
            ?? $payload['matched_photos']
            ?? $payload['results']
            ?? $payload['photos']
            ?? [];

        if (!is_array($rawMatches)) {
            return collect();
        }

        $minimumScore = $this->minimumScore();

        return collect($rawMatches)
            ->map(fn ($match) => $this->normalizeMatch($match))
            ->filter(fn ($match) => is_array($match) && isset($match['photo_id']))
            ->filter(function (array $match) use ($minimumScore): bool {
                if (!array_key_exists('score', $match) || $match['score'] === null) {
                    return true;
                }

                if ($minimumScore <= 0.0) {
                    return true;
                }

                $score = is_numeric($match['score']) ? (float) $match['score'] : null;

                return $score !== null && $score >= $minimumScore;
            })
            ->sortByDesc(fn (array $match) => $match['score'] ?? -1)
            ->values()
            ->unique('photo_id')
            ->values()
            ->take($this->resultLimit());
    }

    private function minimumScore(): float
    {
        $threshold = trim((string) config('services.face_recognition.min_score', ''));
        if ($threshold === '') {
            $threshold = (string) config('services.face_recognition.min_score', 0.35);
        }

        return max(0.0, min(0.95, (float) $threshold));
    }

    private function resultLimit(): int
    {
        $limit = (int) config('services.face_recognition.result_limit', 0);
        if ($limit <= 0) {
            $limit = (int) config('services.face_recognition.result_limit', 3);
        }

        return max(1, $limit);
    }

    private function normalizeMatch(mixed $match): ?array
    {
        if (is_numeric($match)) {
            return ['photo_id' => (int) $match, 'score' => null];
        }

        if (!is_array($match)) {
            return null;
        }

        $photoId = data_get($match, 'photo_id')
            ?? data_get($match, 'photoId')
            ?? data_get($match, 'id')
            ?? data_get($match, 'photo.id');

        if (!$photoId) {
            return null;
        }

        return [
            'photo_id' => (int) $photoId,
            'score' => data_get($match, 'score')
                ?? data_get($match, 'similarity')
                ?? data_get($match, 'confidence'),
        ];
    }
}
