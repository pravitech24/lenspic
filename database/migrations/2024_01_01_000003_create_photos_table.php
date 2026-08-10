<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->bigInteger('file_size')->default(0);
            $table->string('mime_type')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->timestamp('taken_at')->nullable();
            $table->string('caption')->nullable();
            $table->integer('downloads_count')->default(0);
            $table->integer('views_count')->default(0);
            $table->string('uploader_name')->nullable();
            $table->string('uploader_phone')->nullable();
            $table->timestamps();
        });
        Schema::create('photo_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['photo_id', 'user_id']);
        });
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('comments'); Schema::dropIfExists('photo_likes'); Schema::dropIfExists('photos'); }
};
