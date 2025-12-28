<?php

$baseUrl = 'http://127.0.0.1:8002/api';
$adminEmail = 'edit_admin_' . time() . '@example.com';
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

echo "=== Testing Trip Edit Functionality ===\n\n";

// 1. Register Admin
echo "1. Registering Admin...\n";
$res = callApi($baseUrl . '/register', 'POST', [
    'full_name' => 'Edit Admin',
    'email' => $adminEmail,
    'password' => $password,
    'phone_number' => '1122334455',
    'role' => 'ADMIN'
]);
if ($res['code'] !== 201)
    die("Registry Failed\n");
$token = $res['body']['token'];
echo "Admin Registered.\n";

// 2. Setup (Package + Trip + Stages)
echo "2. Setting up Trip and Stages...\n";
// Package
$pRes = callApi($baseUrl . '/packages', 'POST', [
    'package_name' => 'Edit Pkg',
    'price' => 1000,
    'duration_days' => 5,
    'description' => 'Test',
    'services' => 'All',
    'mod_policy' => 'Flex',
    'cancel_policy' => 'Strict',
    'is_active' => true
], $token);
$pkgId = $pRes['body']['package']['package_id'];

// Trip
$tRes = callApi($baseUrl . '/trips', 'POST', [
    'package_id' => $pkgId,
    'trip_name' => 'Original Name',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+5 days'))
], $token);
$tripId = $tRes['body']['trip']['trip_id'];

// Transport Stage
$trRes = callApi($baseUrl . "/trips/$tripId/transports", 'POST', [
    'transport_type' => 'Bus',
    'route_from' => 'A',
    'route_to' => 'B',
    'departure_time' => date('Y-m-d H:i:00')
], $token);
$transId = $trRes['body']['transport']['transport_id'];

// Activity Stage
$acRes = callApi($baseUrl . "/trips/$tripId/activities", 'POST', [
    'activity_type' => 'Visit',
    'location' => 'Place A',
    'activity_date' => date('Y-m-d'),
    'activity_time' => '10:00'
], $token);
$actId = $acRes['body']['activity']['activity_id'];

echo "Setup Complete (Trip: $tripId, Trans: $transId, Act: $actId).\n\n";

// 3. Update Trip Details
echo "3. Updating Trip Details...\n";
$upTripRes = callApi($baseUrl . "/trips/$tripId", 'PUT', [
    'trip_name' => 'Updated Name',
    'notes' => 'Updated Notes'
], $token);

if ($upTripRes['code'] === 200 && $upTripRes['body']['trip']['trip_name'] === 'Updated Name') {
    echo "SUCCESS: Trip updated.\n";
} else {
    echo "FAILED: Trip update.\n";
    print_r($upTripRes['body']);
}

// 4. Update Transport Stage
echo "4. Updating Transport Stage...\n";
$upTransRes = callApi($baseUrl . "/transports/$transId", 'PUT', [
    'route_to' => 'New Dest C'
], $token);

if ($upTransRes['code'] === 200 && $upTransRes['body']['transport']['route_to'] === 'New Dest C') {
    echo "SUCCESS: Transport updated.\n";
} else {
    echo "FAILED: Transport update.\n";
    print_r($upTransRes['body']);
}

// 5. Update Activity Stage
echo "5. Updating Activity Stage...\n";
$upActRes = callApi($baseUrl . "/activities/$actId", 'PUT', [
    'location' => 'New Place B'
], $token);

if ($upActRes['code'] === 200 && $upActRes['body']['activity']['location'] === 'New Place B') {
    echo "SUCCESS: Activity updated.\n";
} else {
    echo "FAILED: Activity update.\n";
    print_r($upActRes['body']);
}

echo "\nDone.\n";
