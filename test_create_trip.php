<?php

$baseUrl = 'http://127.0.0.1:8001/api';
$adminEmail = 'trip_admin_' . time() . '@example.com';
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

echo "=== Testing Create Trip Functionality ===\n\n";

// 1. Register Admin
echo "1. Registering Admin...\n";
$res = callApi($baseUrl . '/register', 'POST', [
    'full_name' => 'Trip Admin',
    'email' => $adminEmail,
    'password' => $password,
    'phone_number' => '1122334455',
    'role' => 'ADMIN'
]);

if ($res['code'] !== 201) {
    echo "FAILED: Could not register admin.\n";
    print_r($res['body']);
    exit(1);
}
$token = $res['body']['token'];
echo "SUCCESS: Admin registered.\n\n";

// 2. Create a Package (Required for Trip)
echo "2. Creating a Package...\n";
$packageData = [
    'package_name' => 'Trip Test Package',
    'price' => 3000.00,
    'duration_days' => 10,
    'description' => 'Package for Trip Test',
    'services' => 'Transport',
    'mod_policy' => 'Flexible',
    'cancel_policy' => 'Strict',
    'is_active' => true
];

$res = callApi($baseUrl . '/packages', 'POST', $packageData, $token);
if ($res['code'] !== 201) {
    echo "FAILED: Could not create package.\n";
    print_r($res['body']);
    exit(1);
}
$packageId = $res['body']['package']['package_id'];
echo "SUCCESS: Package created (ID: $packageId).\n\n";

// 3. Create a Trip
echo "3. Creating a Trip...\n";
$tripName = "Hajj Group A " . date('Y');
$startDate = date('Y-m-d', strtotime('+10 days'));
$endDate = date('Y-m-d', strtotime('+20 days'));

$tripData = [
    'package_id' => $packageId,
    'trip_name' => $tripName,
    'start_date' => $startDate,
    'end_date' => $endDate,
    'status' => 'PLANNED',
    'capacity' => 50,
    'notes' => 'First group trip for testing'
];

$res = callApi($baseUrl . '/trips', 'POST', $tripData, $token);

echo "Response Code: " . $res['code'] . "\n";
if ($res['code'] === 201) {
    echo "SUCCESS: Trip created successfully.\n";
    print_r($res['body']);
} else {
    echo "FAILED: Could not create trip.\n";
    print_r($res['body']);
    exit(1);
}

echo "\nDone.\n";
