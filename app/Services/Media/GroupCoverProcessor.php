<?php

namespace App\Services\Media;

use App\Contracts\ProtectedMediaStorage;
use App\Models\{Group, MediaAsset, MediaVariant, StorageLedgerEntry, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class GroupCoverProcessor
{
    public function __construct(private ProtectedMediaStorage $storage, private MediaAssetCleanup $cleanup) {}

    public function process(MediaAsset $asset): void
    {
        if ($asset->kind !== 'group_cover') throw new RuntimeException('Unsupported cover asset.');
        $source = $asset->variant('original');
        if (!$source || !$this->storage->exists($source->object_key)) throw new RuntimeException('Cover source is unavailable.');
        $stream = $this->storage->readStream($source->object_key); $bytes = stream_get_contents($stream); fclose($stream);
        $image = @imagecreatefromstring($bytes);
        if (!$image) throw new RuntimeException('Cover source is invalid.');
        $image = $this->orient($image, $bytes, $asset->mime_type);

        try {
            foreach ([['cover_thumbnail',400,225,82],['cover_grid',800,450,84],['cover_large',1600,900,86]] as [$type,$width,$height,$quality]) {
                if ($asset->variant($type)) continue;
                [$output,$mime,$extension] = $this->render($image,$width,$height,$quality);
                $key = config('media.prefixes.covers').'/groups/'.$asset->group_id.'/'.$asset->uuid.'/'.$type.'/'.Str::uuid().'.'.$extension;
                $this->storage->put($key,$output,$mime);
                $variant = MediaVariant::create(['media_asset_id'=>$asset->id,'variant_type'=>$type,'storage_disk'=>$this->storage->disk(),'object_key'=>$key,'mime_type'=>$mime,'size_bytes'=>strlen($output),'checksum_sha256'=>hash('sha256',$output),'width'=>$width,'height'=>$height,'state'=>'ready']);
                $ledger=StorageLedgerEntry::firstOrCreate(['idempotency_key'=>'cover-variant-'.$asset->uuid.'-'.$type],['owner_id'=>$asset->owner_id,'group_id'=>$asset->group_id,'media_asset_id'=>$asset->id,'event_type'=>'variant_created','byte_delta'=>$variant->size_bytes]);
                if($ledger->wasRecentlyCreated)User::whereKey($asset->owner_id)->increment('storage_used',$variant->size_bytes);
            }
        } finally { imagedestroy($image); }

        $oldId = DB::transaction(function () use ($asset) {
            $group = Group::query()->lockForUpdate()->find($asset->group_id);
            $asset->update(['state'=>'completed','processing_error'=>null]);
            if (!$group || $group->pending_cover_media_asset_id !== $asset->id) return -1;
            $oldId = $group->cover_media_asset_id;
            $group->update(['cover_media_asset_id'=>$asset->id,'pending_cover_media_asset_id'=>null]);
            return $oldId ?: 0;
        });
        if ($oldId === -1) $this->cleanup->schedule($asset);
        elseif ($oldId > 0 && ($old=MediaAsset::find($oldId))) $this->cleanup->schedule($old);
    }

    private function orient($image,string $bytes,string $mime)
    {
        if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) return $image;
        $temporary = tmpfile(); if (!$temporary) return $image; fwrite($temporary,$bytes); $meta=stream_get_meta_data($temporary);
        $orientation=@exif_read_data($meta['uri'])['Orientation']??1; fclose($temporary);
        $angle=match($orientation){3=>180,6=>-90,8=>90,default=>0}; if(!$angle)return$image;
        $rotated=imagerotate($image,$angle,0); if($rotated){imagedestroy($image);return$rotated;} return$image;
    }

    private function render($source,int $targetWidth,int $targetHeight,int $quality): array
    {
        $width=imagesx($source);$height=imagesy($source);$targetRatio=$targetWidth/$targetHeight;$sourceRatio=$width/$height;
        if($sourceRatio>$targetRatio){$cropHeight=$height;$cropWidth=(int)round($height*$targetRatio);$sourceX=(int)(($width-$cropWidth)/2);$sourceY=0;}
        else{$cropWidth=$width;$cropHeight=(int)round($width/$targetRatio);$sourceX=0;$sourceY=(int)(($height-$cropHeight)/2);}
        $target=imagecreatetruecolor($targetWidth,$targetHeight);imagecopyresampled($target,$source,0,0,$sourceX,$sourceY,$targetWidth,$targetHeight,$cropWidth,$cropHeight);
        ob_start();if(function_exists('imagewebp')){imagewebp($target,null,$quality);$mime='image/webp';$extension='webp';}else{imagejpeg($target,null,$quality);$mime='image/jpeg';$extension='jpg';}$output=(string)ob_get_clean();imagedestroy($target);return[$output,$mime,$extension];
    }
}
