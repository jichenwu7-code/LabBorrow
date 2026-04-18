<?php
// update_passwords.php
require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Hash;
use App\Models\User;

// 连接到Laravel应用
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 要设置的密码
$password = 'wjc123456';
$hashedPassword = Hash::make($password);

// 更新所有用户的密码
try {
    $users = User::all();
    
    if ($users->isEmpty()) {
        echo "没有找到用户记录\n";
        exit;
    }
    
    foreach ($users as $user) {
        $user->password = $hashedPassword;
        $user->save();
        echo "更新用户 {$user->account} 的密码成功\n";
    }
    
    echo "\n所有用户密码已更新为: {$password}\n";
    echo "密码哈希: {$hashedPassword}\n";
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
