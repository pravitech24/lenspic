<?php
namespace App\Services\Media;
use App\Contracts\ProtectedMediaStorage;
use App\Models\{Group,MediaAsset,MediaVariant,Photo,StorageLedgerEntry,User};
use App\Services\ImageOptimizationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB,Storage};
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use App\Services\Storage\StorageUsage;
use App\Support\MediaQuality;
class PrivateMediaIngestor {
 private const MIME_EXTENSIONS=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
 public function __construct(private ProtectedMediaStorage $storage,private ImageOptimizationService $optimizer,private StorageUsage $usage){}
 public function ingest(UploadedFile $file,Group $group,User $uploader): Photo {
  $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());$extension=self::MIME_EXTENSIONS[$mime]??null;if(!$extension)throw new RuntimeException('Unsupported or unsafe image type.');if($file->getSize()<=0||$file->getSize()>(int)config('media.max_upload_bytes',52428800))throw new RuntimeException('Image size is outside the allowed range.');
  $clientExtension=strtolower($file->getClientOriginalExtension());if(!in_array($clientExtension,$extension==='jpg'?['jpg','jpeg']:[$extension],true))throw new RuntimeException('The filename extension does not match the image content.');
  $disk=$this->storage->disk();$groupSegment='groups/'.$group->id;$written=[];
  try {
   $originalKey=$this->storage->putFile(config('media.prefixes.originals').'/'.$groupSegment,$file,$extension);$written[]=$originalKey;
   $optimizedName=Str::uuid().'.'.$extension;$optimized=$this->optimizer->optimizeAndStore($file,config('media.prefixes.optimized').'/'.$groupSegment,$optimizedName,$disk);$optimizedKey=$optimized['path'];$written[]=$optimizedKey;
   [$thumbnailBytes,$thumbnailMime,$thumbWidth,$thumbHeight]=$this->thumbnail($file);$thumbExtension=$thumbnailMime==='image/png'?'png':'jpg';$thumbnailKey=config('media.prefixes.thumbnails').'/'.$groupSegment.'/'.Str::uuid().'.'.$thumbExtension;$this->storage->put($thumbnailKey,$thumbnailBytes,$thumbnailMime);$written[]=$thumbnailKey;
   $info=@getimagesize($file->getRealPath());$owner=User::findOrFail($group->creator_id);$qualityMode=$owner->meta['preferences']['upload_quality_preference']??MediaQuality::STANDARD;if(!in_array($qualityMode,MediaQuality::VALUES,true))$qualityMode=MediaQuality::STANDARD;
   return DB::transaction(function()use($file,$group,$uploader,$owner,$disk,$mime,$originalKey,$optimizedKey,$thumbnailKey,$thumbnailMime,$thumbWidth,$thumbHeight,$info,$qualityMode){
    User::whereKey($owner->id)->lockForUpdate()->firstOrFail();$this->usage->assertPhotoCapacity($owner,$qualityMode);
    $photo=Photo::create(['group_id'=>$group->id,'uploader_id'=>$uploader->id,'filename'=>basename($optimizedKey),'original_filename'=>basename($file->getClientOriginalName()),'path'=>$optimizedKey,'thumbnail_path'=>$thumbnailKey,'file_size'=>$this->storage->size($originalKey),'mime_type'=>$mime,'width'=>$info[0]??null,'height'=>$info[1]??null]);
    $asset=MediaAsset::create(['uuid'=>(string)Str::uuid(),'photo_id'=>$photo->id,'group_id'=>$group->id,'owner_id'=>$owner->id,'uploader_id'=>$uploader->id,'storage_disk'=>$disk,'original_object_key'=>$originalKey,'original_filename'=>basename($file->getClientOriginalName()),'mime_type'=>$mime,'quality_mode'=>$qualityMode,'size_bytes'=>$this->storage->size($originalKey),'checksum_sha256'=>$this->storage->checksum($originalKey),'width'=>$info[0]??null,'height'=>$info[1]??null,'state'=>'ready','migration_state'=>'native']);
    $physicalBytes=0;foreach([['original',$originalKey,$mime,$info[0]??null,$info[1]??null],['optimized',$optimizedKey,$mime,$info[0]??null,$info[1]??null],['thumbnail',$thumbnailKey,$thumbnailMime,$thumbWidth,$thumbHeight]] as [$type,$key,$variantMime,$width,$height]){$variantBytes=$this->storage->size($key);$physicalBytes+=$variantBytes;MediaVariant::create(['media_asset_id'=>$asset->id,'variant_type'=>$type,'storage_disk'=>$disk,'object_key'=>$key,'mime_type'=>$variantMime,'size_bytes'=>$variantBytes,'checksum_sha256'=>$this->storage->checksum($key),'width'=>$width,'height'=>$height,'state'=>'ready']);}
    StorageLedgerEntry::create(['owner_id'=>$owner->id,'group_id'=>$group->id,'media_asset_id'=>$asset->id,'event_type'=>'media_ingested','byte_delta'=>$physicalBytes,'idempotency_key'=>'media-ingested-'.$asset->uuid]);$owner->increment('storage_used',$physicalBytes);$this->usage->record($owner,\App\Models\MediaQuotaUsageEvent::PHOTO_UPLOADED,MediaQuality::quotaUnits($qualityMode),'photo-uploaded-'.$asset->uuid,$group->id,$asset->id,$uploader->id);return $photo;
   });
  } catch(Throwable $exception){foreach($written as $key){try{$this->storage->delete($key);}catch(Throwable){}}throw $exception;}
 }
 private function thumbnail(UploadedFile $file): array { $source=@imagecreatefromstring(file_get_contents($file->getRealPath()));if(!$source)throw new RuntimeException('Thumbnail generation failed.');$width=imagesx($source);$height=imagesy($source);$scale=min(1,640/max($width,$height));$targetWidth=max(1,(int)round($width*$scale));$targetHeight=max(1,(int)round($height*$scale));$target=imagecreatetruecolor($targetWidth,$targetHeight);imagecopyresampled($target,$source,0,0,0,0,$targetWidth,$targetHeight,$width,$height);ob_start();imagejpeg($target,null,82);$bytes=(string)ob_get_clean();imagedestroy($source);imagedestroy($target);return[$bytes,'image/jpeg',$targetWidth,$targetHeight]; }
}
