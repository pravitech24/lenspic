<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::create('studio_team_memberships', function (Blueprint $table) {
            $table->id(); $table->uuid('uuid')->unique();
            $table->foreignId('studio_owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 30); $table->string('status', 20)->default('active');
            $table->json('permissions')->nullable(); $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('joined_at')->nullable(); $table->timestamp('suspended_at')->nullable(); $table->timestamp('last_active_at')->nullable();
            $table->timestamps(); $table->unique(['studio_owner_id','user_id']); $table->index(['studio_owner_id','status']);
        });
        Schema::create('team_invitations', function (Blueprint $table) {
            $table->id(); $table->uuid('uuid')->unique();
            $table->foreignId('studio_owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 100); $table->string('email'); $table->string('role', 30); $table->json('permissions')->nullable();
            $table->string('token_hash', 64); $table->string('status', 20)->default('pending');
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->index(); $table->timestamp('accepted_at')->nullable(); $table->timestamp('revoked_at')->nullable(); $table->timestamp('last_sent_at')->nullable();
            $table->timestamps(); $table->index(['studio_owner_id','email']); $table->index(['studio_owner_id','status']);
        });
        Schema::table('plan_entitlements', fn (Blueprint $table) => $table->unsignedSmallInteger('team_member_limit')->default(5)->after('usage_reset_period'));
        DB::table('plan_entitlements')->update(['team_member_limit' => 5]);
    }
    public function down(): void
    {
        Schema::table('plan_entitlements', fn (Blueprint $table) => $table->dropColumn('team_member_limit'));
        Schema::dropIfExists('team_invitations'); Schema::dropIfExists('studio_team_memberships');
    }
};
