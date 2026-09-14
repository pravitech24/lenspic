<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::table('groups', fn (Blueprint $table) => $table->boolean('favourites_enabled')->default(true)->after('downloads_enabled')); }
    public function down(): void { Schema::table('groups', fn (Blueprint $table) => $table->dropColumn('favourites_enabled')); }
};
