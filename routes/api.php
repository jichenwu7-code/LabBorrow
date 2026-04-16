<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WjcController;
use App\Http\Controllers\ZhyController;

// 公共路由
Route::post('/register', [WjcController::class, 'register']);
Route::post('/login', [WjcController::class, 'login']);
Route::post('/send-code', [WjcController::class, 'sendCode']);   // 新增的
Route::post('/verify-code', [WjcController::class, 'verifyCode']); // 新增的

// 需要登录的路由
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [WjcController::class, 'logout']);
    Route::get('/user', [WjcController::class, 'userInfo']);
    Route::put('/user/password', [WjcController::class, 'updatePassword']);
    Route::put('/user/profile', [WjcController::class, 'updateProfile']);
// 借用申请模块（邹鸿耀负责）
Route::post('/bookings', [ZhyController::class, 'store']);
Route::get('/bookings/my', [ZhyController::class, 'myBookings']);
Route::post('/bookings/{id}/return', [ZhyController::class, 'returnBooking']);
});

// 管理员接口
Route::middleware('auth:api')->group(function () {
    Route::get('/admin/bookings', [WjcController::class, 'bookingList']);
    Route::post('/admin/bookings/{id}/audit', [WjcController::class, 'auditBooking']);

    Route::post('/admin/devices', [WjcController::class, 'storeDevice']);
    Route::put('/admin/devices/{id}', [WjcController::class, 'updateDevice']);
    Route::post('/admin/devices/{id}/status', [WjcController::class, 'updateDeviceStatus']);
});
