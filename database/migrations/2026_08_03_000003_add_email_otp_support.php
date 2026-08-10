<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE otp_requests MODIFY mobile_e164 VARCHAR(20) NULL');
        }
        if (!Schema::hasColumn('otp_requests', 'email')) {
            Schema::table('otp_requests', fn (Blueprint $table) => $table->string('email')->nullable()->after('mobile_e164')->index());
        }
        if (!Schema::hasColumn('otp_requests', 'channel')) {
            Schema::table('otp_requests', fn (Blueprint $table) => $table->string('channel', 20)->default('mobile')->after('email'));
        }
    }

    public function down(): void
    {
        Schema::table('otp_requests', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropColumn(['email', 'channel']);
        });
    }
};
