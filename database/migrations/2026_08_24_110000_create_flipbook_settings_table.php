<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up():void{Schema::create('flipbook_settings',function(Blueprint$table){$table->id();$table->foreignId('studio_owner_id')->unique()->constrained('users')->cascadeOnDelete();$table->string('business_name',150)->nullable();$table->string('logo_disk',40)->nullable();$table->string('logo_object_key',700)->nullable();$table->string('logo_mime_type',30)->nullable();$table->unsignedBigInteger('logo_size_bytes')->nullable();$table->boolean('apply_to_portfolio')->default(false);$table->timestamps();});}public function down():void{Schema::dropIfExists('flipbook_settings');}};
