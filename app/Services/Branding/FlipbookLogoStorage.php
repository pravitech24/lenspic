<?php
namespace App\Services\Branding;
use App\Contracts\ProtectedMediaStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
class FlipbookLogoStorage {
 public function __construct(private ProtectedMediaStorage$storage){}
 public function store(UploadedFile$file,int$ownerId):array{$bytes=@file_get_contents($file->getRealPath());$image=$bytes!==false?@imagecreatefromstring($bytes):false;if(!$image)throw ValidationException::withMessages(['logo'=>'The selected logo is corrupted or is not a valid image.']);imagedestroy($image);$mime=(new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);$map=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];if(!isset($map[$mime]))throw ValidationException::withMessages(['logo'=>'The selected logo format is not supported.']);$key=$this->storage->putFile('branding/flipbooks/users/'.$ownerId,$file,$map[$mime]);return['logo_disk'=>$this->storage->disk(),'logo_object_key'=>$key,'logo_mime_type'=>$mime,'logo_size_bytes'=>$this->storage->size($key)];}
 public function delete(?string$key):void{if($key&&$this->storage->exists($key))$this->storage->delete($key);}
}
