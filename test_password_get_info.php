<?php
// test_password_get_info.php
require_once 'vendor/autoload.php';

// 连接到Laravel应用
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

// 从数据库获取用户密码
$user = User::where('account', '25010220221')->first();

if ($user) {
    $password = $user->password;
    echo "用户密码: $password\n";
    echo "密码长度: " . strlen($password) . "\n";
    echo "密码前缀: " . substr($password, 0, 20) . "...\n";
    
    // 测试password_get_info
    $info = password_get_info($password);
    echo "\npassword_get_info返回值: " . print_r($info, true);
    
    // 测试Hash::check
    try {
        $result = Illuminate\Support\Facades\Hash::check('wjc123456', $password);
        echo "\nHash::check结果: " . ($result ? '成功' : '失败');
    } catch (Exception $e) {
        echo "\nHash::check错误: " . $e->getMessage();
    }
} else {
    echo "未找到用户";
}
