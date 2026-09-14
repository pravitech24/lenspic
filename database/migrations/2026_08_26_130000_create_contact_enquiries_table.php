<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('contact_enquiries')) return;
        Schema::create('contact_enquiries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('full_name', 120);
            $table->string('business_name', 160)->nullable();
            $table->string('email', 254)->index();
            $table->string('phone_country_code', 8);
            $table->string('phone_number', 20);
            $table->string('country', 100);
            $table->string('enquiry_type', 40)->index();
            $table->text('message');
            $table->string('status', 30)->default('new')->index();
            $table->string('source', 60)->default('public_contact');
            $table->char('fingerprint', 64)->index();
            $table->timestamp('consented_at');
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('contact_enquiries'); }
};
