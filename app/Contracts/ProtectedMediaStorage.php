<?php
namespace App\Contracts;
use Illuminate\Http\UploadedFile;
interface ProtectedMediaStorage { public function disk(): string; public function putFile(string $prefix,UploadedFile $file,string $extension): string; public function put(string $key,string $contents,string $mimeType): void; public function exists(string $key): bool; public function size(string $key): int; public function checksum(string $key): string; public function readStream(string $key); public function delete(string $key): void; }
