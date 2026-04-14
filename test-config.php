<?php

// 手动读取.env文件
$envFile = __DIR__.'/.env';
if (file_exists($envFile)) {
    $lines = file($envFile);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, "'\"");
            $_ENV[$key] = $value;
        }
    }
}

// 检查环境变量
echo "SESSION_CONNECTION: " . ($_ENV['SESSION_CONNECTION'] ?? 'Not set') . "\n";
echo "DB_CONNECTION: " . ($_ENV['DB_CONNECTION'] ?? 'Not set') . "\n";

// 尝试直接连接数据库
try {
    $dsn = "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_DATABASE']}";
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    echo "Database connection successful!\n";
    $pdo = null;
} catch (PDOException $e) {
    echo "Database connection failed: {$e->getMessage()}\n";
}
