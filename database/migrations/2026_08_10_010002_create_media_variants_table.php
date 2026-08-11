<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('media_variants',function(Blueprint $table){$table->id();$table->foreignId('media_asset_id')->constrained()->cascadeOnDelete();$table->string('variant_type',30);$table->unsignedInteger('version')->default(1);$table->string('storage_disk',40);$table->string('object_key',700);$table->string('mime_type',100);$table->unsignedBigInteger('size_bytes');$table->char('checksum_sha256',64);$table->unsignedInteger('width')->nullable();$table->unsignedInteger('height')->nullable();$table->string('state',30)->default('ready');$table->text('error')->nullable();$table->timestamps();$table->unique(['media_asset_id','variant_type','version'],'media_variant_version_unique');$table->unique(['storage_disk','object_key'],'media_variants_disk_key_unique');}); }
 public function down(): void { Schema::dropIfExists('media_variants'); }
};
