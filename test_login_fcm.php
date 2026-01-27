<?php

$baseUrl = 'http://127.0.0.1:8000/api';
$email = 'test_fcm_' . time() . '@example.com';
$password = 'password123';
$fcmToken = 'dumm_fcm_token_' . time();

echo "1. Registering new user ($email)...\n";

$ch = curl_init($baseUrl . '/register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'full_name' => 'FCM Tester',
    'email' => $email,
    'phone_number' => '1234567899',
    'password' => $password,
    'role' => 'USER'
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 201) {
    echo "Registration failed. HTTP Code: $httpCode\n";
    echo "Response: $response\n";
    exit(1);
}

echo "2. Testing Login with FCM Token...\n";

$ch = curl_init($baseUrl . '/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => $email,
    'password' => $password,
    'fcm_token' => $fcmToken
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
if (!isset($data['token'])) {
    echo "Login failed. Response: $response\n";
    exit(1);
}

echo "Login Successful. Token received.\n";

// Verify Database
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('email', $email)->first();
if ($user && $user->fcm_token === $fcmToken) {
    echo "SUCCESS: FCM Token saved correctly in database.\n";
} else {
    echo "FAILURE: FCM Token NOT saved.\n";
    echo "Expected: $fcmToken\n";
    echo "Actual: " . ($user ? $user->fcm_token : 'User not found') . "\n";
}
