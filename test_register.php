<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

require __DIR__.'/vendor/autoload.php';
require __DIR__.'/bootstrap/app.php';

// 创建一个模拟的请求数据
$data = [
    'account' => '2024001',
    'name' => '张三',
    'password' => '123456',
    'password_confirmation' => '123456'
];

try {
    // 验证请求数据
    $validator = Validator::make($data, [
        'account' => 'required|unique:users',
        'name' => 'required',
        'password' => 'required|confirmed',
    ]);
    
    if ($validator->fails()) {
        throw new Exception('验证失败: ' . implode(', ', $validator->errors()->all()));
    }
    
    $validated = $validator->validated();
    echo "验证通过\n";
    
    // 创建用户
    $user = User::create([
        'account' => $validated['account'],
        'name' => $validated['name'],
        'password' => Hash::make($validated['password']),
        'role' => 1, //默认学生
        'status' => 1,
    ]);
    
    echo "用户创建成功，ID: {$user->id}\n";
} catch (Exception $e) {
    echo "错误: {$e->getMessage()}\n";
    echo "堆栈跟踪:\n{$e->getTraceAsString()}\n";
}
