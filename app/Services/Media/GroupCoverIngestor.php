<?php

namespace App\Services\Media;

use App\Contracts\ProtectedMediaStorage;
use App\Jobs\ProcessGroupCover;
use App\Models\{Group, MediaAsset, MediaVariant, StorageLedgerEntry, User};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class GroupCoverIngestor
{
    private const TYPES = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'];

    public function __construct(private ProtectedMediaStorage $storage, private MediaAssetCleanup $cleanup) {}

    public function ingest(UploadedFile $file, Group $group, User $user): MediaAsset
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
        $extension = self::TYPES[$mime] ?? null;
        $clientExtension = strtolower($file->getClientOriginalExtension());
        if (!$extension || !in_array($clientExtension, $extension === 'jpg' ? ['jpg','jpeg'] : [$extension], true)) {
            throw new RuntimeException('The cover image content and filename do not match an accepted format.');
        }

        $key = $this->storage->putFile(config('media.prefixes.covers').'/groups/'.$group->id.'/originals', $file, $extension);
        $asset = null;
        try {
            [$asset, $displaced] = DB::transaction(function () use ($file, $group, $user, $mime, $key) {
                $locked = Group::query()->lockForUpdate()->findOrFail($group->id);
                $info = @getimagesize($file->getRealPath());
                if (!$info || $info[0] < 800 || $info[1] < 300) throw new RuntimeException('The cover image dimensions are too small.');
                $asset = MediaAsset::create([
                    'uuid'=>(string) Str::uuid(), 'group_id'=>$locked->id, 'owner_id'=>$locked->creator_id,
                    'uploader_id'=>$user->id, 'kind'=>'group_cover', 'storage_disk'=>$this->storage->disk(),
                    'original_object_key'=>$key, 'original_filename'=>basename($file->getClientOriginalName()),
                    'mime_type'=>$mime, 'size_bytes'=>$this->storage->size($key), 'checksum_sha256'=>$this->storage->checksum($key),
                    'width'=>$info[0], 'height'=>$info[1], 'state'=>'queued', 'visibility'=>'group', 'migration_state'=>'native',
                ]);
                MediaVariant::create([
                    'media_asset_id'=>$asset->id, 'variant_type'=>'original', 'storage_disk'=>$this->storage->disk(),
                    'object_key'=>$key, 'mime_type'=>$mime, 'size_bytes'=>$asset->size_bytes,
                    'checksum_sha256'=>$asset->checksum_sha256, 'width'=>$asset->width, 'height'=>$asset->height, 'state'=>'ready',
                ]);
                StorageLedgerEntry::create([
                    'owner_id'=>$asset->owner_id, 'group_id'=>$locked->id, 'media_asset_id'=>$asset->id,
                    'event_type'=>'original_stored', 'byte_delta'=>$asset->size_bytes, 'idempotency_key'=>'cover-original-'.$asset->uuid,
                ]);
                User::whereKey($asset->owner_id)->increment('storage_used', $asset->size_bytes);
                $displaced = $locked->pendingCoverMediaAsset;
                $locked->update(['pending_cover_media_asset_id'=>$asset->id]);
                return [$asset, $displaced];
            });

            ProcessGroupCover::dispatch($asset->id)->afterCommit();
            if ($displaced) $this->cleanup->schedule($displaced);
            return $asset;
        } catch (Throwable $exception) {
            if ($asset) {
                $asset->update([
                    'state' => 'failed',
                    'processing_error' => 'Cover processing could not be queued. Please retry.',
                ]);
            } elseif ($this->storage->exists($key)) {
                $this->storage->delete($key);
            }
            throw $exception;
        }
    }
}
