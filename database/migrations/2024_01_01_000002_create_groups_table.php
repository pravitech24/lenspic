<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('event_date')->nullable();
            $table->string('event_type')->default('event');
            $table->string('cover_photo')->nullable();
            $table->string('share_token', 20)->unique();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_guest_upload')->default(false);
            $table->boolean('watermark_enabled')->default(false);
            $table->string('watermark_text')->nullable();
            $table->boolean('face_recognition_enabled')->default(false);
            $table->enum('privacy', ['public', 'private', 'link_only'])->default('link_only');
            $table->timestamps();
        });
        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['admin', 'member'])->default('member');
            $table->timestamp('joined_at')->nullable();
            $table->string('selfie_path')->nullable();
            $table->timestamps();
            $table->unique(['group_id', 'user_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('group_members'); Schema::dropIfExists('groups'); }
};
