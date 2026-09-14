<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('studio_team_memberships', fn (Blueprint $table) => $table->json('assigned_group_ids')->nullable()->after('permissions'));
        Schema::table('team_invitations', fn (Blueprint $table) => $table->json('assigned_group_ids')->nullable()->after('permissions'));
    }
    public function down(): void
    {
        Schema::table('studio_team_memberships', fn (Blueprint $table) => $table->dropColumn('assigned_group_ids'));
        Schema::table('team_invitations', fn (Blueprint $table) => $table->dropColumn('assigned_group_ids'));
    }
};
