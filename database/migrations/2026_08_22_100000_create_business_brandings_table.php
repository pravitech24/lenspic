<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up():void
    {
        Schema::create('business_brandings',function(Blueprint$table){
            $table->id();$table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('business_name',100)->nullable();
            $table->string('business_phone_country_code',6)->nullable();$table->string('business_phone_number',15)->nullable();$table->boolean('show_business_phone_in_gallery')->default(false);
            $table->string('business_email')->nullable();$table->boolean('show_business_email_in_gallery')->default(false);
            $table->string('website',500)->nullable();$table->boolean('show_website_in_gallery')->default(false);
            $table->string('instagram_url',500)->nullable();$table->boolean('show_instagram_in_gallery')->default(false);
            $table->string('facebook_url',500)->nullable();$table->boolean('show_facebook_in_gallery')->default(false);
            $table->string('whatsapp_country_code',6)->nullable();$table->string('whatsapp_phone_number',15)->nullable();$table->boolean('show_whatsapp_in_portfolio')->default(false);
            $table->string('youtube_url',500)->nullable();$table->boolean('show_youtube_in_portfolio')->default(false);
            $table->string('vimeo_url',500)->nullable();$table->boolean('show_vimeo_in_portfolio')->default(false);
            $table->string('logo_disk',40)->nullable();$table->string('logo_object_key',700)->nullable();$table->string('logo_mime_type',30)->nullable();$table->unsignedBigInteger('logo_size_bytes')->nullable();
            $table->timestamps();
        });
    }
    public function down():void{Schema::dropIfExists('business_brandings');}
};
