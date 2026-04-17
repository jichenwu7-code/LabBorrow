<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WjcController;
use App\Http\Controllers\GyzController;

// 测试路由
Route::get('/test-api', function () {
    return response()->json(['message' => 'API test successful']);
});

// 登录路由（用于认证中间件重定向）
Route::get('/login', function () {
    return response()->json([
        'code' => 401,
        'message' => '未授权',
        'data' => null
    ], 401);
})->name('login');

// 公共路由
Route::post('/register', [WjcController::class, 'register']);
Route::post('/login', [WjcController::class, 'login']);
Route::post('/send-code', [WjcController::class, 'sendCode']);
Route::post('/verify-code', [WjcController::class, 'verifyCode']);
Route::put('/reset-password', [WjcController::class, 'resetPassword']);

// 需要登录的路由
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [WjcController::class, 'logout']);
    Route::get('/user', [WjcController::class, 'userInfo']);
    Route::put('/user/profile', [WjcController::class, 'updateProfile']);
    Route::delete('/user', [WjcController::class, 'deleteUser']);
});

// 管理员接口
Route::middleware('auth:api')->group(function () {
    Route::get('/admin/bookings', [WjcController::class, 'bookingList']);
    Route::post('/admin/bookings/{id}/audit', [WjcController::class, 'auditBooking']);

    Route::post('/admin/devices', [WjcController::class, 'storeDevice']);
    Route::put('/admin/devices/{id}', [WjcController::class, 'updateDevice']);
    Route::post('/admin/devices/{id}/status', [WjcController::class, 'updateDeviceStatus']);
});

// 设备接口
Route::get('/devices', [GyzController::class, 'index']);
Route::get('/categories', [GyzController::class, 'categories']);
Route::get('/devices/available', [GyzController::class, 'available']);
Route::get('/devices/filter', [GyzController::class, 'filterByStatus']);
Route::get('/devices/status-options', [GyzController::class, 'statusOptions']);
Route::get('/devices/hot', [GyzController::class, 'hotDevices']);
Route::get('/categories/{id}/devices', [GyzController::class, 'devicesByCategory']);
Route::get('/devices/{id}', [GyzController::class, 'show']);
Route::get('/devices/{id}/check-available', [GyzController::class, 'checkAvailable']);
