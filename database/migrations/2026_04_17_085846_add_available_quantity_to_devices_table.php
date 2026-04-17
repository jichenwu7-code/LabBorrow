<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('devices', function (Blueprint $table) {
            // 添加可用数量字段，默认等于总数量
            $table->integer('available_quantity')->default(0);
        });
    }

    public function down()
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('available_quantity');
        });
    }
};
