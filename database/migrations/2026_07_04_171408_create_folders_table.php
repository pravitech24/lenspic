<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cover_photo_id')->nullable()->constrained('photos')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('highlighted')->default(false);
            $table->timestamps();

            $table->index(['group_id', 'display_order']);
            $table->unique(['group_id', 'name']);
        });

        Schema::table('photos', function (Blueprint $table) {
            $table->foreignId('folder_id')->nullable()->after('group_id')->constrained()->nullOnDelete();
            $table->index(['group_id', 'folder_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
