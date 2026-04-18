<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    return response()->json(['message' => 'Test successful']);
});

// 登录路由（用于认证中间件重定向）
Route::get('/login', function () {
    return response()->json([
        'code' => 401,
        'message' => '未授权',
        'data' => null
    ], 401);
})->name('login');
