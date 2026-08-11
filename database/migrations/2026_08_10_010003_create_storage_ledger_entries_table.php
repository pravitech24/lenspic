<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('storage_ledger_entries',function(Blueprint $table){$table->id();$table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();$table->foreignId('group_id')->nullable()->constrained()->nullOnDelete();$table->foreignId('media_asset_id')->nullable()->constrained()->nullOnDelete();$table->string('event_type',40);$table->bigInteger('byte_delta');$table->string('idempotency_key',120)->unique();$table->json('metadata')->nullable();$table->timestamp('created_at')->useCurrent();$table->index(['owner_id','created_at']);}); }
 public function down(): void { Schema::dropIfExists('storage_ledger_entries'); }
};
