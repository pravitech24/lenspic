<?php
namespace App\Services\Media;
use App\Contracts\ProtectedMediaStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
class LaravelProtectedMediaStorage implements ProtectedMediaStorage {
 public function disk(): string{return (string)config('media.disk','private_local');}
 public function putFile(string $prefix,UploadedFile $file,string $extension): string{$prefix=$this->safePrefix($prefix);$extension=strtolower(preg_replace('/[^a-z0-9]/i','',$extension));if($extension==='')throw new RuntimeException('A safe extension is required.');$key=$prefix.'/'.Str::uuid().'.'.$extension;$stream=fopen($file->getRealPath(),'rb');try{if(!Storage::disk($this->disk())->put($key,$stream,['visibility'=>'private','ContentType'=>$file->getMimeType()]))throw new RuntimeException('Private media write failed.');}finally{if(is_resource($stream))fclose($stream);}return $key;}
 public function put(string $key,string $contents,string $mimeType): void{$key=$this->safeKey($key);if(!Storage::disk($this->disk())->put($key,$contents,['visibility'=>'private','ContentType'=>$mimeType]))throw new RuntimeException('Private media write failed.');}
 public function exists(string $key): bool{return Storage::disk($this->disk())->exists($this->safeKey($key));}
 public function size(string $key): int{return Storage::disk($this->disk())->size($this->safeKey($key));}
 public function checksum(string $key): string{$stream=$this->readStream($key);$hash=hash_init('sha256');hash_update_stream($hash,$stream);fclose($stream);return hash_final($hash);}
 public function readStream(string $key){$stream=Storage::disk($this->disk())->readStream($this->safeKey($key));if(!is_resource($stream))throw new RuntimeException('Private media could not be read.');return $stream;}
 public function delete(string $key): void{Storage::disk($this->disk())->delete($this->safeKey($key));}
 private function safePrefix(string $prefix): string{$prefix=trim($prefix,'/');if($prefix===''||str_contains($prefix,'..')||str_contains($prefix,"\0"))throw new RuntimeException('Unsafe media prefix.');return $prefix;}
 private function safeKey(string $key): string{if($key===''||str_starts_with($key,'/')||str_contains($key,'..')||str_contains($key,"\0")||str_contains($key,'\\'))throw new RuntimeException('Unsafe media key.');return $key;}
}
