<?php

require __DIR__.'/vendor/autoload.php';
require __DIR__.'/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

try {
    $connection = DB::connection();
    echo "数据库连接成功\n";
    
    // 测试查询
    $users = DB::table('users')->get();
    echo "用户表查询成功，共有 {$users->count()} 个用户\n";
} catch (Exception $e) {
    echo "数据库连接失败: {$e->getMessage()}\n";
}
