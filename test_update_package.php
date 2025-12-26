<?php

$baseUrl = 'http://127.0.0.1:8000/api';
$adminEmail = 'edit_pkg_admin_' . time() . '@example.com';
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

    if ($method === 'POST')
        curl_setopt($ch, CURLOPT_POST, true);
    if ($method === 'PUT')
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

echo "=== Testing Edit Umrah Package ===\n\n";

// 1. Register Admin
echo "1. Registering Admin...\n";
$res = callApi($baseUrl . '/register', 'POST', [
    'full_name' => 'Package Editor',
    'email' => $adminEmail,
    'password' => $password,
    'phone_number' => '5544332211',
    'role' => 'ADMIN'
]);
if ($res['code'] !== 201)
    exit("FAILED: Admin reg failed.\n");
$token = $res['body']['token'];
echo "SUCCESS: Admin registered.\n\n";

// 2. Create Initial Package
echo "2. creating Initial Package...\n";
$res = callApi($baseUrl . '/packages', 'POST', [
    'package_name' => 'Initial Package',
    'price' => 1000.00,
    'duration_days' => 10,
    'description' => 'Original Description',
    'services' => 'Basic Service',
    'is_active' => true
], $token);

if ($res['code'] !== 201)
    exit("FAILED: Package create failed.\n");
$pkg = $res['body']['package'];
$pkgId = $pkg['package_id'];
echo "SUCCESS: Package created (ID: $pkgId).\n\n";

// 3. Update Package
echo "3. Updating Package (Name & Price)...\n";
$res = callApi($baseUrl . "/packages/$pkgId", 'PUT', [
    'package_name' => 'Updated Package Name',
    'price' => 2000.50
], $token);

echo "Response Code: " . $res['code'] . "\n";
$updatedPkg = $res['body']['package'];

if (
    $res['code'] === 200 &&
    $updatedPkg['package_name'] === 'Updated Package Name' &&
    $updatedPkg['price'] == 2000.50
) {
    echo "SUCCESS: Package updated correctly.\n";
    print_r($updatedPkg);
} else {
    echo "FAILED: Package update failed.\n";
    print_r($res['body']);
    exit(1);
}

echo "\nDone.\n";
