<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->json('meta')->nullable()->after('is_admin');
            $table->string('plan')->default('free')->after('meta'); // free, standard, essential, premium
            $table->timestamp('plan_expires_at')->nullable()->after('plan');
            $table->bigInteger('storage_used')->default(0)->after('plan_expires_at'); // bytes
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['meta','plan','plan_expires_at','storage_used']);
        });
    }
};
