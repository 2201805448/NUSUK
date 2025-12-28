<?php

$baseUrl = 'http://127.0.0.1:8002/api';
$run_id = time();
$adminEmail = 'view_admin_' . $run_id . '@example.com';
$supEmail = 'view_sup_' . $run_id . '@example.com';
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

echo "=== Testing Supervisor Trip View ===\n\n";

// 1. Register Admin & Supervisor
echo "1. Registering Users...\n";
// Admin
$res = callApi($baseUrl . '/register', 'POST', [
    'full_name' => 'View Admin',
    'email' => $adminEmail,
    'password' => $password,
    'phone_number' => '111',
    'role' => 'ADMIN'
]);
if ($res['code'] !== 201)
    die("Admin Reg Failed");
$adminToken = $res['body']['token'];

// Supervisor
$res = callApi($baseUrl . '/register', 'POST', [
    'full_name' => 'View Sup',
    'email' => $supEmail,
    'password' => $password,
    'phone_number' => '222',
    'role' => 'SUPERVISOR'
]);
if ($res['code'] !== 201)
    die("Sup Reg Failed");
$supToken = $res['body']['token'];
echo "Users Registered.\n\n";

// 2. Admin Creates Data
echo "2. Admin Setting up Data...\n";
// Package
$pRes = callApi($baseUrl . '/packages', 'POST', [
    'package_name' => 'View Pkg',
    'price' => 100,
    'duration_days' => 2,
    'services' => 'None',
    'is_active' => true
], $adminToken);
$pkgId = $pRes['body']['package']['package_id'];

// Trip
$tRes = callApi($baseUrl . '/trips', 'POST', [
    'package_id' => $pkgId,
    'trip_name' => 'Supervisor View Test',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d')
], $adminToken);
$tripId = $tRes['body']['trip']['trip_id'];

// Transport Stage
callApi($baseUrl . "/trips/$tripId/transports", 'POST', [
    'transport_type' => 'Bus',
    'route_from' => 'A',
    'route_to' => 'B',
    'departure_time' => date('Y-m-d H:i')
], $adminToken);

// Activity Stage
callApi($baseUrl . "/trips/$tripId/activities", 'POST', [
    'activity_type' => 'Visit',
    'location' => 'Place',
    'activity_date' => date('Y-m-d'),
    'activity_time' => '10:00'
], $adminToken);

echo "Data Setup Complete.\n\n";

// 3. Supervisor Views Trip
echo "3. Supervisor Accessing Trip Details...\n";
$viewRes = callApi($baseUrl . "/trips/$tripId", 'GET', [], $supToken);

if ($viewRes['code'] === 200) {
    echo "SUCCESS: Supervisor accessed trip.\n";
    $trip = $viewRes['body'];

    // Validating Content
    $hasTransports = !empty($trip['transports']);
    $hasActivities = !empty($trip['activities']);

    echo "Transports Visible: " . ($hasTransports ? 'YES' : 'NO') . "\n";
    echo "Activities Visible: " . ($hasActivities ? 'YES' : 'NO') . "\n";

    if ($hasTransports && $hasActivities) {
        echo "VERIFICATION PASSED: Full program is visible.\n";
    } else {
        echo "VERIFICATION FAILED: Missing stages.\n";
        print_r($trip);
    }

} else {
    echo "FAILED: Access Denied or Error.\n";
    print_r($viewRes['body']);
}

echo "\nDone.\n";
