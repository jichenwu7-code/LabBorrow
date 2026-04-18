<?php
// test_password_hash.php

// 测试我们生成的bcrypt哈希
$hash = '$2y$12$VceSAd3u/10NGvY3T0OdwOpH1B9chDXURrwXEbeqXJV5BTPMXRi/u';

// 获取哈希信息
$info = password_get_info($hash);

echo "哈希密码: $hash\n";
echo "====================================\n";
echo "算法ID: {$info['algo']}\n";
echo "算法名称: {$info['algoName']}\n";
echo "成本因子: {$info['options']['cost']}\n";
echo "====================================\n";

// 测试验证
$password = 'wjc123456';
$verify = password_verify($password, $hash);
echo "密码验证结果: " . ($verify ? '成功' : '失败') . "\n";

// 测试其他哈希格式
$otherHash = password_hash('test', PASSWORD_BCRYPT);
echo "\n新生成的哈希: $otherHash\n";
$otherInfo = password_get_info($otherHash);
echo "算法名称: {$otherInfo['algoName']}\n";
