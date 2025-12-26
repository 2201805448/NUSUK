<?php

$baseUrl = 'http://127.0.0.1:8000/api';
$adminEmail = 'del_pkg_admin_' . time() . '@example.com';
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
    if ($method === 'DELETE')
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

echo "=== Testing Delete Umrah Package ===\n\n";

// 1. Register Admin
echo "1. Registering Admin...\n";
$res = callApi($baseUrl . '/register', 'POST', [
    'full_name' => 'Package Delete Admin',
    'email' => $adminEmail,
    'password' => $password,
    'phone_number' => '1234567890',
    'role' => 'ADMIN'
]);
if ($res['code'] !== 201)
    exit("FAILED: Admin reg failed.\n");
$token = $res['body']['token'];
echo "SUCCESS: Admin registered.\n\n";

// 2. Create Package
echo "2. Creating Package to Delete...\n";
$res = callApi($baseUrl . '/packages', 'POST', [
    'package_name' => 'To Be Deleted',
    'price' => 100.00,
    'duration_days' => 5,
    'is_active' => true
], $token);

if ($res['code'] !== 201)
    exit("FAILED: Package create failed.\n");
$pkgId = $res['body']['package']['package_id'];
echo "SUCCESS: Package created (ID: $pkgId).\n\n";

// 3. Delete Package
echo "3. Deleting Package...\n";
$res = callApi($baseUrl . "/packages/$pkgId", 'DELETE', [], $token);
echo "Response Code: " . $res['code'] . "\n";

if ($res['code'] === 200) {
    echo "SUCCESS: Package deleted response received.\n";
} else {
    echo "FAILED: Delete failed.\n";
    print_r($res['body']);
    exit(1);
}

// 4. Verify Deletion
echo "4. Verifying Deletion (Update Attempt)...\n";
$res = callApi($baseUrl . "/packages/$pkgId", 'PUT', [
    'package_name' => 'Ghost Package'
], $token);

if ($res['code'] === 404) {
    echo "SUCCESS: Package not found (404) as expected.\n";
} else {
    echo "FAILED: Package still exists! Code: " . $res['code'] . "\n";
    print_r($res['body']);
}

echo "\nDone.\n";
