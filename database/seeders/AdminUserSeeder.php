<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 添加管理员用户
        $adminUsers = [
            [
                'account' => 'admin1',
                'name' => '管理员1',
                'password' => 'admin123',
                'role' => 2, // 假设2是管理员角色
                'status' => 1,
                'email' => 'admin@example.com',
                'phone' => '13800138000',
            ],
            [
                'account' => 'admin2',
                'name' => '管理员2',
                'password' => 'admin123',
                'role' => 2,
                'status' => 1,
                'email' => 'admin2@example.com',
                'phone' => '13800138001',
            ],
        ];

        foreach ($adminUsers as $userData) {
            // 检查账号是否已存在
            $existingUser = \App\Models\User::where('account', $userData['account'])->first();
            
            if (!$existingUser) {
                // 账号不存在，创建新管理员
                \App\Models\User::create([
                    'account' => $userData['account'],
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'role' => $userData['role'],
                    'status' => $userData['status'],
                    'email' => $userData['email'],
                    'phone' => $userData['phone'],
                ]);
            }
        }
    }
}