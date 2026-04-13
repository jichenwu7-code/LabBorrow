<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('category_id')->comment('关联设备分类');
            $table->string('name', 100)->comment('设备名称')->index();
            $table->string('model', 50)->nullable()->comment('设备型号')->index();
            $table->text('description')->nullable()->comment('设备详情');
            $table->integer('total_quantity')->default(0)->comment('总库存');
            $table->tinyInteger('status')->default(1)->comment('1=可借 0=维护中/下架')->index();
            $table->timestamps();

            $table->foreign('category_id')
                ->references('id')
                ->on('device_categories')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('devices');
    }
};