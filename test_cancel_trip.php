<?php

$baseUrl = 'http://127.0.0.1:8002/api';
$run_id = time();
$adminEmail = 'cancel_admin_' . $run_id . '@example.com';
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
    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

echo "=== Testing Trip Cancellation Status ===\n\n";

// 1. Register Admin
echo "1. Registering Admin...\n";
$res = callApi($baseUrl . '/register', 'POST', [
    'full_name' => 'Cancel Admin',
    'email' => $adminEmail,
    'password' => $password,
    'phone_number' => '1122334455',
    'role' => 'ADMIN'
]);
if ($res['code'] !== 201)
    die("Registry Failed\n");
$token = $res['body']['token'];
echo "Admin Registered.\n";

// 2. Setup (Package + Trip)
echo "2. Setting up Trip...\n";
$pRes = callApi($baseUrl . '/packages', 'POST', [
    'package_name' => 'Cancel Test Pkg ' . $run_id,
    'price' => 1000,
    'duration_days' => 5,
    'description' => 'Test',
    'services' => 'All',
    'mod_policy' => 'Flex',
    'cancel_policy' => 'Strict',
    'is_active' => true
], $token);
$pkgId = $pRes['body']['package']['package_id'];

$tRes = callApi($baseUrl . '/trips', 'POST', [
    'package_id' => $pkgId,
    'trip_name' => 'Trip to Cancel',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+5 days'))
], $token);
$tripId = $tRes['body']['trip']['trip_id'];
echo "Trip Created (ID: $tripId) with Status: " . $tRes['body']['trip']['status'] . "\n\n";

// 3. Cancel Trip
echo "3. Cancelling Trip...\n";
$cancelRes = callApi($baseUrl . "/trips/$tripId/cancel", 'PATCH', [], $token);

if ($cancelRes['code'] === 200 && $cancelRes['body']['trip']['status'] === 'CANCELLED') {
    echo "SUCCESS: Trip cancelled.\n";
    print_r($cancelRes['body']);
} else {
    echo "FAILED: Trip cancellation.\n";
    print_r($cancelRes['body']);
}

echo "\nDone.\n";
