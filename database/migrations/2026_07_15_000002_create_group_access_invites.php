<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('group_access_invites', function(Blueprint $table) {
            $table->id(); $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->string('access_type',20); $table->string('access_code',6)->unique(); $table->string('invitation_token',64)->unique();
            $table->boolean('is_active')->default(true); $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('max_uses')->nullable(); $table->unsignedInteger('used_count')->default(0);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete(); $table->timestamp('revoked_at')->nullable(); $table->timestamps();
            $table->unique(['group_id','access_type']);
        });
        Schema::create('group_access_audits', function(Blueprint $table) {
            $table->id(); $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('access_invite_id')->nullable()->constrained('group_access_invites')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->string('action',40);
            $table->json('metadata')->nullable(); $table->timestamp('created_at')->useCurrent();
        });
        Schema::table('group_members', function(Blueprint $table) {
            $table->string('membership_status',20)->default('active'); $table->string('access_type',20)->default('full_access');
            $table->foreignId('access_invite_id')->nullable()->constrained('group_access_invites')->nullOnDelete();
            $table->timestamp('requested_at')->nullable(); $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('access_upgraded_at')->nullable();
        });
    }
    public function down(): void {
        Schema::table('group_members', function(Blueprint $table) { $table->dropConstrainedForeignId('access_invite_id'); $table->dropConstrainedForeignId('approved_by'); $table->dropColumn(['membership_status','access_type','requested_at','approved_at','access_upgraded_at']); });
        Schema::dropIfExists('group_access_audits'); Schema::dropIfExists('group_access_invites');
    }
};
