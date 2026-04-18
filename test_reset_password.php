<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$request = \Illuminate\Http\Request::create(
    '/api/reset-password',
    'POST',
    [
        'password' => 'wjc634321',
        'password_confirmation' => 'wjc634321'
    ],
    [],
    [],
    [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_CONTENT_TYPE' => 'application/json',
        'HTTP_VERIFY_TOKEN' => 'test_token'
    ]
);

$request->headers->set('Content-Type', 'application/json');

$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$response = $kernel->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Content-Type: " . $response->headers->get('Content-Type') . "\n";
echo "Content: " . $response->getContent() . "\n";