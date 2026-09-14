<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up():void
    {
        Schema::table('media_quota_usage_events',fn(Blueprint$table)=>$table->decimal('quantity',12,2)->change());
        Schema::table('media_assets',fn(Blueprint$table)=>$table->string('quality_mode',30)->default('standard')->change());
        DB::table('media_assets')->whereIn('quality_mode',['original','storage_saver'])->update(['quality_mode'=>'standard']);
    }
    public function down():void
    {
        DB::table('media_assets')->where('quality_mode','standard')->update(['quality_mode'=>'storage_saver']);
        Schema::table('media_assets',fn(Blueprint$table)=>$table->string('quality_mode',30)->default('original')->change());
        Schema::table('media_quota_usage_events',fn(Blueprint$table)=>$table->unsignedBigInteger('quantity')->change());
    }
};
