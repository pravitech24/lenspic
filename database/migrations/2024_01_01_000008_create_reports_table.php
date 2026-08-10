<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('type'); // 'user_activity', 'group_activity', 'storage_usage', 'subscription_status'
            $table->json('data');
            $table->dateTime('report_date');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('reports');
    }
};
