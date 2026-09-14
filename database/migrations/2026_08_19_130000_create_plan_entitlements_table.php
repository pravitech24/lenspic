<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plan_entitlements', function (Blueprint $table) {
            $table->id();
            $table->string('plan', 40)->unique();
            $table->unsignedInteger('photo_limit');
            $table->unsignedInteger('video_storage_limit_mb');
            $table->unsignedInteger('photo_delete_reupload_limit');
            $table->unsignedInteger('video_delete_reupload_limit_mb');
            $table->unsignedSmallInteger('deleted_media_usage_release_hours')->default(24);
            $table->string('usage_reset_period', 30)->default('billing_cycle');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        foreach (['free','trial','basic','standard','essential','premium','pro','business','enterprise'] as $plan) {
            DB::table('plan_entitlements')->insert([
                'plan' => $plan,
                'photo_limit' => 100000,
                'video_storage_limit_mb' => 5000,
                'photo_delete_reupload_limit' => 200000,
                'video_delete_reupload_limit_mb' => 10000,
                'deleted_media_usage_release_hours' => 24,
                'usage_reset_period' => 'billing_cycle',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void { Schema::dropIfExists('plan_entitlements'); }
};
