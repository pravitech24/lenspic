<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('razorpay_orders', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->timestamp('paid_at')->nullable()->after('status');
            $table->timestamp('failed_at')->nullable()->after('paid_at');
            $table->timestamp('cancelled_at')->nullable()->after('failed_at');
            $table->text('failure_reason')->nullable()->after('cancelled_at');
        });
        DB::table('razorpay_orders')->whereNull('uuid')->orderBy('id')->eachById(fn ($order) => DB::table('razorpay_orders')->where('id', $order->id)->update(['uuid' => (string) Str::uuid()]));
        Schema::table('razorpay_orders', fn (Blueprint $table) => $table->unique('uuid'));
    }

    public function down(): void
    {
        Schema::table('razorpay_orders', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn(['uuid', 'paid_at', 'failed_at', 'cancelled_at', 'failure_reason']);
        });
    }
};
