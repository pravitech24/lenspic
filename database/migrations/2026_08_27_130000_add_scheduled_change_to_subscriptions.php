<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::table('subscriptions', function (Blueprint $table) { $table->string('scheduled_plan', 30)->nullable()->after('status'); $table->timestamp('scheduled_change_at')->nullable()->after('scheduled_plan'); }); }
    public function down(): void { Schema::table('subscriptions', fn (Blueprint $table) => $table->dropColumn(['scheduled_plan','scheduled_change_at'])); }
};
