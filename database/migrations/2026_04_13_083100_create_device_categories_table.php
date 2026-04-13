<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('device_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 50)->unique()->comment('分类名称');
            $table->text('description')->nullable()->comment('分类描述');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('device_categories');
    }
};