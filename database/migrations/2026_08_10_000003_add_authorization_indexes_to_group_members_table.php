<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('group_members', function (Blueprint $table) {
            $table->index(['group_id', 'membership_status', 'access_type'], 'group_members_access_idx');
            $table->index(['user_id', 'membership_status'], 'group_members_user_status_idx');
            $table->index(['group_id', 'role', 'membership_status'], 'group_members_role_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('group_members', function (Blueprint $table) {
            $table->dropIndex('group_members_access_idx');
            $table->dropIndex('group_members_user_status_idx');
            $table->dropIndex('group_members_role_status_idx');
        });
    }
};
