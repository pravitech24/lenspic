<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile_country_code', 8)->nullable()->after('phone');
            $table->string('mobile_e164', 20)->nullable()->unique()->after('mobile_country_code');
            $table->string('account_type', 20)->nullable()->after('role');
            $table->string('onboarding_step', 40)->default('mobile_pending');
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->string('status', 20)->default('active');
        });
        // Accounts created before this migration are already fully registered.
        DB::table('users')->update(['onboarding_step'=>'completed','onboarding_completed_at'=>now()]);
        Schema::create('otp_requests', function (Blueprint $table) {
            $table->id(); $table->string('mobile_e164', 20)->nullable()->index(); $table->string('email')->nullable()->index(); $table->string('channel', 20)->default('mobile'); $table->string('otp_hash');
            $table->timestamp('expires_at'); $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('resend_available_at'); $table->timestamp('verified_at')->nullable(); $table->timestamps();
        });
        Schema::create('selfie_verifications', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->string('image_path');
            $table->unsignedTinyInteger('face_count')->nullable(); $table->decimal('blur_score', 8, 3)->nullable();
            $table->decimal('lighting_score', 8, 3)->nullable(); $table->string('verification_status', 20)->default('pending');
            $table->string('failure_reason')->nullable(); $table->timestamp('verified_at')->nullable(); $table->timestamps();
        });
        Schema::create('photographer_profiles', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('first_name', 100); $table->string('last_name', 100); $table->string('company_name', 160);
            $table->string('company_email')->unique();
            $table->foreignId('selfie_verification_id')->nullable()->constrained('selfie_verifications')->nullOnDelete(); $table->timestamps();
        });
        Schema::table('groups', function (Blueprint $table) {
            $table->string('event_code', 6)->nullable()->unique(); $table->string('invitation_token', 64)->nullable()->unique();
            $table->timestamp('invitation_expires_at')->nullable(); $table->timestamp('event_code_expires_at')->nullable();
            $table->string('membership_status', 20)->default('open'); $table->unsignedInteger('membership_limit')->nullable();
            $table->string('location')->nullable();
        });
        Schema::table('group_members', fn (Blueprint $table) => $table->string('join_method', 20)->default('direct'));
    }
    public function down(): void {
        Schema::table('group_members', fn (Blueprint $table) => $table->dropColumn('join_method'));
        Schema::table('groups', fn (Blueprint $table) => $table->dropColumn(['event_code','invitation_token','invitation_expires_at','event_code_expires_at','membership_status','membership_limit','location']));
        Schema::dropIfExists('photographer_profiles'); Schema::dropIfExists('selfie_verifications'); Schema::dropIfExists('otp_requests');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['mobile_country_code','mobile_e164','account_type','onboarding_step','onboarding_completed_at','status']));
    }
};
