<?php

namespace App\Services\Media;

use App\Contracts\ProtectedMediaStorage;
use App\Jobs\ProcessMediaAsset;
use App\Models\{Group, MediaAsset, MediaVariant, Photo, StorageLedgerEntry, UploadBatchFile, User};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use App\Services\Storage\StorageUsage;
use App\Support\MediaQuality;

class QueuedMediaIngestor
{
    private const TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    public function __construct(private ProtectedMediaStorage $storage, private StorageUsage $usage) {}

    public function ingest(UploadedFile $file, Group $group, User $user, UploadBatchFile $batchFile, ?int $folderId = null): Photo
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
        $extension = self::TYPES[$mime] ?? null;
        if (!$extension || $file->getSize() <= 0 || $file->getSize() > (int) config('media.max_upload_bytes')) throw new RuntimeException('Invalid image content or size.');
        if ($folderId && !$group->folders()->whereKey($folderId)->exists()) throw new RuntimeException('The selected folder does not belong to this group.');
        if (!in_array(strtolower($file->getClientOriginalExtension()), $extension === 'jpg' ? ['jpg', 'jpeg'] : [$extension], true)) throw new RuntimeException('The filename extension does not match the image content.');
        $metadata=$mime==='image/jpeg'&&function_exists('exif_read_data')?(@exif_read_data($file->getRealPath())?:[]):[];$orientation=(int)($metadata['Orientation']??1);$captured=isset($metadata['DateTimeOriginal'])?\Carbon\Carbon::createFromFormat('Y:m:d H:i:s',$metadata['DateTimeOriginal']):null;$safeExif=array_filter(['make'=>$metadata['Make']??null,'model'=>$metadata['Model']??null]);

        $owner=User::findOrFail($group->creator_id);$qualityMode=$owner->meta['preferences']['upload_quality_preference']??MediaQuality::STANDARD;
        if(!in_array($qualityMode,MediaQuality::VALUES,true))$qualityMode=MediaQuality::STANDARD;
        $key = $this->storage->putFile(config('media.prefixes.originals').'/groups/'.$group->id, $file, $extension);
        try {
            [$photo, $asset] = DB::transaction(function () use ($file, $group, $user, $batchFile, $mime, $key, $folderId, $qualityMode, $orientation, $captured, $safeExif) {
                $size = $this->storage->size($key);
                User::whereKey($group->creator_id)->lockForUpdate()->firstOrFail();
                $owner = User::findOrFail($group->creator_id);
                $this->usage->assertPhotoCapacity($owner,$qualityMode);
                $dimensions = @getimagesize($file->getRealPath());
                $photo = Photo::create(['group_id' => $group->id, 'folder_id' => $folderId, 'uploader_id' => $user->id, 'filename' => basename($key), 'original_filename' => basename($file->getClientOriginalName()), 'path' => $key, 'thumbnail_path' => $key, 'file_size' => $size, 'mime_type' => $mime, 'width' => $dimensions[0] ?? null, 'height' => $dimensions[1] ?? null]);
                $asset = MediaAsset::create(['uuid' => (string) Str::uuid(), 'photo_id' => $photo->id, 'group_id' => $group->id, 'owner_id' => $group->creator_id, 'uploader_id' => $user->id, 'storage_disk' => $this->storage->disk(), 'original_object_key' => $key, 'original_filename' => basename($file->getClientOriginalName()), 'mime_type' => $mime, 'quality_mode'=>$qualityMode, 'size_bytes' => $size, 'original_upload_bytes'=>$size, 'stored_master_bytes'=>$size,'storage_saved_bytes'=>0,'orientation'=>$orientation,'captured_at'=>$captured,'exif'=>$safeExif, 'checksum_sha256' => $this->storage->checksum($key), 'width' => $dimensions[0] ?? null, 'height' => $dimensions[1] ?? null, 'state' => 'queued', 'migration_state' => 'native']);
                MediaVariant::create(['media_asset_id' => $asset->id, 'variant_type' => 'original', 'storage_disk' => $this->storage->disk(), 'object_key' => $key, 'mime_type' => $mime, 'size_bytes' => $size, 'checksum_sha256' => $asset->checksum_sha256, 'width' => $asset->width, 'height' => $asset->height, 'state' => 'ready']);
                StorageLedgerEntry::create(['owner_id' => $asset->owner_id, 'group_id' => $group->id, 'media_asset_id' => $asset->id, 'event_type' => 'original_stored', 'byte_delta' => $size, 'idempotency_key' => 'original-'.$asset->uuid]);
                User::whereKey($asset->owner_id)->increment('storage_used', $size);
                $this->usage->record($owner,\App\Models\MediaQuotaUsageEvent::PHOTO_UPLOADED,MediaQuality::quotaUnits($qualityMode),'photo-uploaded-'.$asset->uuid,$group->id,$asset->id,$user->id);
                $batchFile->update(['media_asset_id' => $asset->id, 'state' => 'queued']);
                return [$photo, $asset];
            });
            ProcessMediaAsset::dispatch($asset->id, $batchFile->id)->afterCommit();
            return $photo;
        } catch (Throwable $exception) {
            $this->storage->delete($key);
            throw $exception;
        }
    }
}
