<?php
// test_login.php
require_once 'vendor/autoload.php';

// 连接到Laravel应用
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

// 模拟登录请求
$account = '25010220221';
$password = 'wjc123456';

echo "测试登录...\n";
echo "账号: $account\n";
echo "密码: $password\n";

try {
    // 手动验证用户
    $user = User::where('account', $account)->first();
    
    if (!$user) {
        echo "用户不存在\n";
        exit;
    }
    
    echo "用户存在: {$user->name}\n";
    
    // 验证密码
    if (!password_verify($password, $user->password)) {
        echo "密码错误\n";
        exit;
    }
    
    echo "密码验证成功\n";
    
    // 生成JWT token
    $payload = [
        'sub' => $user->id,
        'iat' => time(),
        'exp' => time() + 3600
    ];
    
    $token = \Tymon\JWTAuth\Facades\JWTAuth::encode($payload)->get();
    
    echo "生成的token: $token\n";
    echo "登录成功！\n";
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    echo "堆栈: " . $e->getTraceAsString() . "\n";
}
