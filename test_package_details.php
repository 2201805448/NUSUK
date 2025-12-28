<?php

$baseUrl = 'http://127.0.0.1:8002/api';
$runId = time();
$adminEmail = 'detail_admin_' . $runId . '@example.com';
$pilgrimEmail = 'detail_pil_' . $runId . '@example.com';
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
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if ($method !== 'GET' && !empty($data)) { // Fix: don't attach body for GET
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    curl_setopt($ch, CURLOPT_URL, $url);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

echo "=== Testing Package Details Display ===\n\n";

// 1. Register Users
echo "1. Registering Users...\n";
$adm = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Adm', 'email' => $adminEmail, 'password' => $password, 'phone_number' => '1', 'role' => 'ADMIN']);
$adminToken = $adm['body']['token'];

$pil = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Pil', 'email' => $pilgrimEmail, 'password' => $password, 'phone_number' => '2', 'role' => 'PILGRIM']);
$pilgrimToken = $pil['body']['token'];
echo "Users Registered.\n\n";

// 2. Setup Package (Admin)
echo "2. Setting up Package...\n";
$pkg = callApi($baseUrl . '/packages', 'POST', [
    'package_name' => 'Detailed Pkg',
    'price' => 500,
    'duration_days' => 10,
    'description' => 'Full Description Here',
    'is_active' => true
], $adminToken);
$pkgId = $pkg['body']['package']['package_id'];
echo "Package Created (ID: $pkgId).\n\n";

// 3. Pilgrim Views Specific Package
echo "3. Pilgrim Views Package Details...\n";
$res = callApi($baseUrl . "/packages/$pkgId", 'GET', [], $pilgrimToken);

if ($res['code'] === 200) {
    echo "SUCCESS: Package details retrieved.\n";
    $p = $res['body'];
    echo "Name: " . $p['package_name'] . "\n";
    echo "Desc: " . $p['description'] . "\n";

    if ($p['package_name'] === 'Detailed Pkg') {
        echo "VERIFICATION PASSED.\n";
    } else {
        echo "VERIFICATION FAILED: Data mismatch.\n";
    }
} else {
    echo "FAILED: Could not retrieve package.\n";
    print_r($res['body']);
}

echo "\nDone.\n";
