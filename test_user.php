<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\User::where('account', '25010220221')->first();
echo "User ID: " . $user->id . "\n";
echo "Password hash: " . $user->password . "\n";
echo "Hash info: " . print_r(password_get_info($user->password), true);
echo "Password verify (wjc123456): " . password_verify('wjc123456', $user->password) . "\n";
echo "Password verify (Wjc123456): " . password_verify('Wjc123456', $user->password) . "\n";