<?php
// check_passwords.php
require_once 'vendor/autoload.php';

use App\Models\User;

// 连接到Laravel应用
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $users = User::all();
    
    if ($users->isEmpty()) {
        echo "没有找到用户记录\n";
        exit;
    }
    
    echo "用户密码状态检查：\n";
    echo "====================================\n";
    
    foreach ($users as $user) {
        $password = $user->password;
        $length = strlen($password);
        $prefix = substr($password, 0, 10);
        $isBcrypt = (strpos($password, '$2y$') === 0);
        
        echo "用户: {$user->account}\n";
        echo "密码长度: {$length}\n";
        echo "密码前缀: {$prefix}\n";
        echo "是否Bcrypt: " . ($isBcrypt ? '是' : '否') . "\n";
        echo "完整密码: {$password}\n";
        echo "------------------------------------\n";
    }
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
