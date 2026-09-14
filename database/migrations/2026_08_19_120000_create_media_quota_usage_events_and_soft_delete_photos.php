<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('photos', fn (Blueprint $table) => $table->softDeletes());
        Schema::create('media_quota_usage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('media_asset_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 30);
            $table->unsignedBigInteger('quantity');
            $table->timestamp('period_starts_at');
            $table->timestamp('period_ends_at');
            $table->string('idempotency_key', 160)->unique();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['owner_id', 'period_starts_at', 'event_type'], 'quota_owner_period_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_quota_usage_events');
        Schema::table('photos', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
