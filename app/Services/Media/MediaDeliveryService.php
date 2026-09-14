<?php
namespace App\Services\Media;
use App\Models\MediaVariant;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
class MediaDeliveryService {
 public function __construct(private CloudFrontUrlSigner $signer){}
 public function deliver(MediaVariant $variant,bool $download=false,?string $filename=null){$name=$download?$this->safeFilename($filename?:$variant->mediaAsset->original_filename,$variant->media_asset_id,$variant->mime_type):null;$disposition=$name?HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT,$name,'lenspic-photo-'.$variant->media_asset_id.'.jpg'):null;if(config('media.delivery_driver')==='cloudfront'){$query=$download?['response-content-disposition'=>$disposition,'response-content-type'=>$variant->mime_type]:[];return redirect()->away($this->signer->sign($variant->object_key,now()->addSeconds($download?(int)config('media.download_ttl',900):(int)config('media.url_ttl',300)),$query));}$stream=Storage::disk($variant->storage_disk)->readStream($variant->object_key);abort_unless(is_resource($stream),404);return new StreamedResponse(function()use($stream){fpassthru($stream);fclose($stream);},200,array_filter(['Content-Type'=>$variant->mime_type,'Content-Length'=>(string)$variant->size_bytes,'Cache-Control'=>'private, no-store','Content-Disposition'=>$disposition])); }
 private function safeFilename(?string $name,int $id,string $mime):string{$extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];$extension=$extensions[strtolower($mime)]??'jpg';$base=pathinfo(basename(str_replace('\\','/',(string)$name)),PATHINFO_FILENAME);$base=trim((string)preg_replace('/[^A-Za-z0-9._-]+/','-',$base),'.-_');if($base==='')$base='lenspic-photo-'.$id;return substr($base,0,120).'.'.$extension;}
}
