<?php
namespace App\Services\Media;
use App\Models\MediaVariant;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
class MediaDeliveryService {
 public function __construct(private CloudFrontUrlSigner $signer){}
 public function deliver(MediaVariant $variant,bool $download=false){if(config('media.delivery_driver')==='cloudfront'){return redirect()->away($this->signer->sign($variant->object_key,now()->addSeconds($download?(int)config('media.download_ttl',900):(int)config('media.url_ttl',300))));}$stream=Storage::disk($variant->storage_disk)->readStream($variant->object_key);abort_unless(is_resource($stream),404);$name=$download?$variant->mediaAsset->original_filename:null;return new StreamedResponse(function()use($stream){fpassthru($stream);fclose($stream);},200,array_filter(['Content-Type'=>$variant->mime_type,'Content-Length'=>(string)$variant->size_bytes,'Cache-Control'=>'private, no-store','Content-Disposition'=>$name?'attachment; filename="'.addslashes($name).'"':null])); }
}
