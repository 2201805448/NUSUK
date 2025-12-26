<?php

$baseUrl = 'http://127.0.0.1:8000/api';
$adminEmail = 'pkg_admin_' . time() . '@example.com';
$password = 'password123';

function callApi($url, $method = 'POST', $data = [], $token = null)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

echo "=== Testing Add Umrah Package ===\n\n";

// 1. Register Admin
echo "1. Registering Admin...\n";
$res = callApi($baseUrl . '/register', 'POST', [
    'full_name' => 'Package Admin',
    'email' => $adminEmail,
    'password' => $password,
    'phone_number' => '1122334455',
    'role' => 'ADMIN'
]);

if ($res['code'] !== 201) {
    echo "FAILED: Could not register admin.\n";
    exit(1);
}
$token = $res['body']['token'];
echo "SUCCESS: Admin registered.\n\n";

// 2. Add Package
echo "2. Adding New Package...\n";
$packageData = [
    'package_name' => 'Gold Umrah Package 2024',
    'price' => 5000.00,
    'duration_days' => 14,
    'description' => 'A luxury package including 5-star hotels.',
    'services' => 'Visa, Flight, Hotel, Transport',
    'mod_policy' => 'Free modification up to 7 days before.',
    'cancel_policy' => 'Non-refundable within 3 days.',
    'is_active' => true
];

$res = callApi($baseUrl . '/packages', 'POST', $packageData, $token);

echo "Response Code: " . $res['code'] . "\n";
if ($res['code'] === 201) {
    echo "SUCCESS: Package created.\n";
    print_r($res['body']);
} else {
    echo "FAILED: Could not create package.\n";
    print_r($res['body']);
    exit(1);
}

echo "\nDone.\n";
