<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('upload_batches', fn (Blueprint $table) => $table->unsignedInteger('cancelled_files')->default(0)->after('failed_files'));
        Schema::table('upload_batch_files', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('attempts');
            $table->timestamp('completed_at')->nullable()->after('started_at');
            $table->timestamp('failed_at')->nullable()->after('completed_at');
            $table->timestamp('cancelled_at')->nullable()->after('failed_at');
        });
    }

    public function down(): void
    {
        Schema::table('upload_batch_files', fn (Blueprint $table) => $table->dropColumn(['started_at', 'completed_at', 'failed_at', 'cancelled_at']));
        Schema::table('upload_batches', fn (Blueprint $table) => $table->dropColumn('cancelled_files'));
    }
};
