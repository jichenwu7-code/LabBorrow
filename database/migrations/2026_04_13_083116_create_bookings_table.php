<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->comment('申请人');
            $table->unsignedBigInteger('device_id')->comment('借用设备');
            $table->unsignedBigInteger('admin_id')->nullable()->comment('审核管理员');
            $table->date('start_date')->comment('借用开始日期')->index();
            $table->date('end_date')->comment('借用结束日期');
            $table->text('purpose')->nullable()->comment('借用用途');
            $table->string('status', 20)->default('pending')->comment('pending/approved/rejected/returned')->index();
            $table->text('reject_reason')->nullable()->comment('拒绝原因');
            $table->timestamp('returned_at')->nullable()->comment('实际归还时间');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('device_id')
                ->references('id')
                ->on('devices')
                ->onDelete('restrict');

            $table->foreign('admin_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookings');
    }
};