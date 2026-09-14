<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('otp_destination_locks', function (Blueprint $table) {
            $table->string('key', 64)->primary();
        });
        Schema::table('otp_requests', function (Blueprint $table) {
            $table->uuid('public_reference')->nullable()->unique();
            $table->string('request_key', 64)->nullable()->unique();
            $table->string('authentication_channel')->nullable();
            $table->string('disclosure_version')->nullable();
        });
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_reference')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose')->default('authentication_otp');
            $table->string('provider')->default('meta');
            $table->string('recipient_hash', 64);
            $table->string('recipient_masked', 32);
            $table->string('template');
            $table->string('language');
            $table->string('idempotency_key', 64)->unique();
            $table->string('meta_message_id')->nullable()->unique();
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamp('last_attempted_at')->nullable();
            $table->string('provider_error_code')->nullable();
            $table->string('provider_error_message')->nullable();
            foreach (['accepted','sent','delivered','read','failed'] as $status) $table->timestamp($status.'_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('whatsapp_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_hash', 64)->unique();
            $table->string('meta_message_id')->index();
            $table->string('event_type');
            $table->timestamp('occurred_at');
            $table->string('provider_error_code')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_webhook_events');
        Schema::dropIfExists('whatsapp_messages');
        Schema::table('otp_requests', fn (Blueprint $table) => $table->dropColumn(['public_reference','request_key','authentication_channel','disclosure_version']));
        Schema::dropIfExists('otp_destination_locks');
    }
};
