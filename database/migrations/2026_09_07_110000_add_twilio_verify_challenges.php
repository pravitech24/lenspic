<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('otp_requests', function (Blueprint $table) {
            $table->string('otp_hash')->nullable()->change();
            $table->string('provider', 24)->nullable();
            $table->string('delivery_status', 24)->nullable();
            $table->string('failure_category', 40)->nullable();
            $table->string('destination_masked', 32)->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->string('session_hash', 64)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('otp_requests', function (Blueprint $table) {
            $table->dropColumn(['provider','delivery_status','failure_category','destination_masked','accepted_at','session_hash']);
        });
        // Retain nullable hashes: historical provider-generated SMS challenges have no local hash.
    }
};
