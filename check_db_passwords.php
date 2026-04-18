<?php
// check_db_passwords.php
require_once 'vendor/autoload.php';

use App\Models\User;
use Illuminate\Support\Facades\DB;

// 连接到Laravel应用
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // 直接从数据库读取，不经过模型
    $users = DB::table('users')->select('id', 'account', 'password')->get();
    
    if ($users->isEmpty()) {
        echo "没有找到用户记录\n";
        exit;
    }
    
    echo "数据库中用户密码检查：\n";
    echo "====================================\n";
    
    foreach ($users as $user) {
        $password = $user->password;
        $length = strlen($password);
        $prefix = substr($password, 0, 10);
        $isBcrypt = (strpos($password, '$2y$') === 0);
        
        // 检查是否有空格或不可见字符
        $hasSpace = strpos($password, ' ') !== false;
        $hasControlChars = preg_match('/[\x00-\x1F\x7F]/', $password);
        
        echo "用户: {$user->account}\n";
        echo "密码长度: {$length}\n";
        echo "密码前缀: {$prefix}\n";
        echo "是否Bcrypt: " . ($isBcrypt ? '是' : '否') . "\n";
        echo "是否有空格: " . ($hasSpace ? '是' : '否') . "\n";
        echo "是否有控制字符: " . ($hasControlChars ? '是' : '否') . "\n";
        echo "密码哈希: {$password}\n";
        
        // 测试password_get_info
        $info = password_get_info($password);
        echo "算法名称: {$info['algoName']}\n";
        echo "------------------------------------\n";
    }
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
