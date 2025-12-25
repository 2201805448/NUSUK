<?php

$baseUrl = 'http://127.0.0.1:8000/api';
$email = 'test_login_' . time() . '@example.com';
$password = 'password123';

echo "1. Registering new user ($email)...\n";

$ch = curl_init($baseUrl . '/register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'full_name' => 'Login Tester',
    'email' => $email,
    'phone_number' => '1234567890',
    'password' => $password,
    'role' => 'USER'
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
echo "Response: $response\n\n";

if ($httpCode !== 201) {
    echo "Registration failed. Aborting login test.\n";
    exit(1);
}

echo "2. Testing Login...\n";

$ch = curl_init($baseUrl . '/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => $email,
    'password' => $password
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
echo "Response: $response\n\n";

$data = json_decode($response, true);
if (isset($data['token'])) {
    echo "SUCCESS: Login Successful! Token received.\n";
} else {
    echo "FAILURE: Login did not return a token.\n";
}
