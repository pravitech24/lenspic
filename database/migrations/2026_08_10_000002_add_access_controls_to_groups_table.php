<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->boolean('anyone_with_link_can_join')->default(false)->after('privacy');
            $table->string('anonymous_access_mode', 20)->default('disabled')->after('anyone_with_link_can_join');
            $table->boolean('downloads_enabled')->default(true)->after('anonymous_access_mode');
            $table->boolean('participants_can_edit_identity')->default(false)->after('downloads_enabled');
            $table->unsignedInteger('access_policy_version')->default(1)->after('participants_can_edit_identity');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['anyone_with_link_can_join', 'anonymous_access_mode', 'downloads_enabled', 'participants_can_edit_identity', 'access_policy_version']);
        });
    }
};
