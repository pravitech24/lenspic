<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE subscriptions MODIFY plan ENUM('free','basic','standard','essential','premium','pro','business','enterprise') NOT NULL DEFAULT 'free'");
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('provider_order_id')->nullable()->after('payment_id')->index();
            $table->string('billing_cycle', 20)->nullable()->after('provider_order_id');
            $table->string('currency', 3)->default('INR')->after('amount');
            $table->unique('payment_id');
        });

        Schema::create('razorpay_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider_order_id')->unique();
            $table->string('plan', 30);
            $table->string('billing_cycle', 20);
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('INR');
            $table->string('status', 20)->default('created');
            $table->string('payment_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('razorpay_orders');
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropUnique(['payment_id']);
            $table->dropIndex(['provider_order_id']);
            $table->dropColumn(['provider_order_id', 'billing_cycle', 'currency']);
        });
    }
};
