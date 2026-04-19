<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WjcController;
use App\Http\Controllers\ZhyController;
use App\Http\Controllers\GyzController;
use App\Http\Middleware\DisableSession;

// 为所有API路由应用禁用session的中间件
Route::middleware(DisableSession::class)->group(function () {

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
    Route::post('/reset-password', [WjcController::class, 'resetPassword']);

    // 需要登录的路由
    Route::middleware('auth:api')->group(function () {
        // 用户模块
        Route::post('/logout', [WjcController::class, 'logout']);
        Route::get('/user', [WjcController::class, 'userInfo']);
        Route::post('/user/profile', [WjcController::class, 'updateProfile']);
        Route::delete('/user', [WjcController::class, 'deleteUser']);

        // 借用申请模块
        Route::post('/bookings', [ZhyController::class, 'store']);
        Route::get('/bookings/my', [ZhyController::class, 'myBookings']);
        Route::post('/bookings/{id}/return', [ZhyController::class, 'returnBooking']);

        // 管理员接口
        Route::get('/admin/bookings', [WjcController::class, 'bookingList']);
        Route::post('/admin/bookings/{id}/audit', [WjcController::class, 'auditBooking']);
        Route::post('/admin/bookings/{id}/return-audit', [WjcController::class, 'auditReturnBooking']);
        Route::post('/admin/devices', [WjcController::class, 'storeDevice']);
        Route::post('/admin/devices/{id}', [WjcController::class, 'updateDevice']);
        Route::post('/admin/devices/{id}/status', [WjcController::class, 'updateDeviceStatus']);
        Route::post('/admin/categories', [WjcController::class, 'storeCategory']);
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

});
