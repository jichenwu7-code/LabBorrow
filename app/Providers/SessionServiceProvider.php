<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class SessionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        // 注册服务
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        // 在应用启动时设置session驱动为array，避免SQLite连接问题
        config(['session.driver' => 'array']);
    }
}