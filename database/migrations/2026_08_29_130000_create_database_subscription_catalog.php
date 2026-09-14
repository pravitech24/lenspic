<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id(); $table->uuid('uuid')->unique(); $table->string('code',40)->unique(); $table->string('name',100); $table->string('slug',120)->unique();
            $table->string('short_description',500); $table->unsignedSmallInteger('rank')->default(0); $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true); $table->boolean('is_public')->default(true); $table->boolean('is_recommended')->default(false);
            $table->string('badge_text',80)->nullable(); $table->string('badge_style',30)->nullable();
            $table->unsignedInteger('photo_storage_limit'); $table->unsignedInteger('photo_reuse_limit'); $table->unsignedInteger('video_storage_mb')->default(0);
            $table->unsignedSmallInteger('team_seat_limit')->nullable(); $table->unsignedInteger('group_limit')->nullable(); $table->unsignedInteger('guest_limit')->nullable();
            $table->unsignedSmallInteger('grace_period_days')->default(0); $table->unsignedInteger('version')->default(1); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('subscription_plan_prices', function (Blueprint $table) {
            $table->id(); $table->foreignId('subscription_plan_id')->constrained()->cascadeOnUpdate()->restrictOnDelete(); $table->enum('billing_interval',['quarterly','yearly']);
            $table->char('currency',3)->default('INR'); $table->unsignedBigInteger('base_amount_paise'); $table->unsignedSmallInteger('gst_rate_basis_points')->default(1800);
            $table->unsignedBigInteger('comparison_amount_paise')->nullable(); $table->boolean('is_active')->default(true); $table->string('razorpay_plan_id',100)->nullable();
            $table->timestamp('effective_from'); $table->timestamp('effective_until')->nullable(); $table->timestamps();
            $table->index(['subscription_plan_id','billing_interval','is_active'],'plan_price_active_idx');
        });
        Schema::create('subscription_features', function (Blueprint $table) {
            $table->id(); $table->string('code',80)->unique(); $table->string('name',120); $table->string('description',500)->nullable(); $table->string('icon',40);
            $table->enum('value_type',['boolean','integer','decimal','text']); $table->string('unit',30)->nullable(); $table->string('category',50); $table->boolean('is_active')->default(true); $table->unsignedSmallInteger('display_order')->default(0); $table->timestamps();
        });
        Schema::create('subscription_plan_features', function (Blueprint $table) {
            $table->id(); $table->foreignId('subscription_plan_id')->constrained()->restrictOnDelete(); $table->foreignId('subscription_feature_id')->constrained()->restrictOnDelete();
            $table->boolean('is_included')->default(false); $table->boolean('value_boolean')->nullable(); $table->bigInteger('value_integer')->nullable(); $table->decimal('value_decimal',14,2)->nullable(); $table->text('value_text')->nullable();
            $table->boolean('is_addon')->default(false); $table->unsignedBigInteger('addon_price_paise')->nullable(); $table->string('display_label',160)->nullable(); $table->unsignedSmallInteger('display_order')->default(0); $table->timestamps();
            $table->unique(['subscription_plan_id','subscription_feature_id'],'plan_feature_unique');
        });
        Schema::create('subscription_entitlement_snapshots', function (Blueprint $table) {
            $table->id(); $table->foreignId('subscription_id')->unique()->constrained()->cascadeOnDelete(); $table->foreignId('subscription_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('plan_code',40); $table->string('plan_name',100); $table->unsignedInteger('plan_version'); $table->enum('billing_interval',['quarterly','yearly']);
            $table->unsignedBigInteger('base_amount_paise'); $table->unsignedBigInteger('gst_amount_paise'); $table->unsignedBigInteger('total_amount_paise'); $table->char('currency',3);
            $table->unsignedInteger('photo_limit'); $table->unsignedInteger('photo_reuse_limit'); $table->unsignedInteger('video_limit_mb'); $table->unsignedSmallInteger('team_seat_limit')->nullable();
            $table->unsignedInteger('group_limit')->nullable(); $table->unsignedInteger('guest_limit')->nullable(); $table->json('features'); $table->json('addons')->nullable();
            $table->timestamp('starts_at'); $table->timestamp('expires_at'); $table->timestamps();
        });
        Schema::create('subscription_addons', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('subscription_feature_id')->constrained()->restrictOnDelete();
            $table->string('status',20)->default('pending'); $table->unsignedBigInteger('base_amount_paise'); $table->unsignedBigInteger('gst_amount_paise'); $table->unsignedBigInteger('total_amount_paise'); $table->string('payment_id',100)->nullable();
            $table->timestamp('starts_at')->nullable(); $table->timestamp('expires_at')->nullable(); $table->timestamps(); $table->index(['user_id','status','expires_at']);
        });
        Schema::create('subscription_plan_audits', function (Blueprint $table) {
            $table->id(); $table->foreignId('subscription_plan_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action',80); $table->uuid('request_id'); $table->json('previous_values')->nullable(); $table->json('new_values')->nullable(); $table->timestamps();
        });
        Schema::table('razorpay_orders', function (Blueprint $table) {
            $table->foreignId('subscription_plan_id')->nullable()->after('user_id')->constrained()->nullOnDelete(); $table->foreignId('subscription_plan_price_id')->nullable()->after('subscription_plan_id')->constrained()->nullOnDelete();
            $table->unsignedBigInteger('base_amount_paise')->nullable()->after('amount'); $table->unsignedBigInteger('gst_amount_paise')->nullable()->after('base_amount_paise'); $table->json('entitlement_snapshot')->nullable(); $table->timestamp('expires_at')->nullable();
        });
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('subscription_plan_id')->nullable()->after('user_id')->constrained()->nullOnDelete(); $table->unsignedInteger('plan_version')->nullable()->after('plan');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', fn(Blueprint $t)=>$t->dropConstrainedForeignId('subscription_plan_id'));
        Schema::table('subscriptions', fn(Blueprint $t)=>$t->dropColumn('plan_version'));
        Schema::table('razorpay_orders', function(Blueprint $t){$t->dropConstrainedForeignId('subscription_plan_price_id');$t->dropConstrainedForeignId('subscription_plan_id');$t->dropColumn(['base_amount_paise','gst_amount_paise','entitlement_snapshot','expires_at']);});
        Schema::dropIfExists('subscription_plan_audits'); Schema::dropIfExists('subscription_addons'); Schema::dropIfExists('subscription_entitlement_snapshots'); Schema::dropIfExists('subscription_plan_features'); Schema::dropIfExists('subscription_features'); Schema::dropIfExists('subscription_plan_prices'); Schema::dropIfExists('subscription_plans');
    }
};
