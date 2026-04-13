<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('account', 50)->unique()->comment('用户账号（学号/邮箱）');
            $table->string('name', 30)->comment('用户姓名');
            $table->string('password', 255)->comment('bcrypt加密密码');
            $table->tinyInteger('role')->comment('1=学生/普通用户 2=管理员')->index();
            $table->string('phone', 20)->nullable()->comment('联系方式');
            $table->string('email', 100)->nullable()->comment('备用邮箱');
            $table->tinyInteger('status')->default(1)->comment('1=正常 0=禁用');
            $table->timestamps();

            // 索引
            $table->index('name');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};