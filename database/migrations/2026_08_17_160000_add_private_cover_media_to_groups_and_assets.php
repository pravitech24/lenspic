<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            $table->text('processing_error')->nullable()->after('state');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->foreignId('cover_media_asset_id')->nullable()->after('cover_photo')->constrained('media_assets')->nullOnDelete();
            $table->foreignId('pending_cover_media_asset_id')->nullable()->after('cover_media_asset_id')->constrained('media_assets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pending_cover_media_asset_id');
            $table->dropConstrainedForeignId('cover_media_asset_id');
        });
        Schema::table('media_assets', fn (Blueprint $table) => $table->dropColumn('processing_error'));
    }
};
