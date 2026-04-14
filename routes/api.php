<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WjcController;

// 测试路由
Route::get('/test-api', function () {
    return response()->json(['message' => 'API test successful']);
});

// 公共路由
Route::post('/register', [WjcController::class, 'register']);
Route::post('/login', [WjcController::class, 'login']);

// 需要登录的路由
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [WjcController::class, 'logout']);
    Route::get('/user', [WjcController::class, 'userInfo']);
    Route::put('/user/password', [WjcController::class, 'updatePassword']);
    Route::put('/user/profile', [WjcController::class, 'updateProfile']);
});