<?php

$baseUrl = 'http://127.0.0.1:8002/api';
$adminEmail = 'stage_admin_' . time() . '@example.com';
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

echo "=== Testing Trip Stages Definition ===\n\n";

// 1. Register Admin
echo "1. Registering Admin...\n";
$res = callApi($baseUrl . '/register', 'POST', [
    'full_name' => 'Stage Admin',
    'email' => $adminEmail,
    'password' => $password,
    'phone_number' => '1122334455',
    'role' => 'ADMIN'
]);

if ($res['code'] !== 201) {
    echo "FAILED: Register. " . print_r($res['body'], true);
    exit;
}
$token = $res['body']['token'];
echo "Admn Registered.\n";

// 2. Create Trip (Needs Package)
echo "2. Creating Trip...\n";
// Create Package
$pkgRes = callApi($baseUrl . '/packages', 'POST', [
    'package_name' => 'Stage Test Package',
    'price' => 1000,
    'duration_days' => 5,
    'description' => 'Test',
    'services' => 'All',
    'mod_policy' => 'Flexible',
    'cancel_policy' => 'Strict',
    'is_active' => true
], $token);

if ($pkgRes['code'] !== 201) {
    echo "FAILED: Package Creation. " . print_r($pkgRes['body'], true);
    exit;
}
$pkgId = $pkgRes['body']['package']['package_id'];

// Create Trip
$tripRes = callApi($baseUrl . '/trips', 'POST', [
    'package_id' => $pkgId,
    'trip_name' => 'Stage Test Trip ' . date('Y-m-d'),
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+5 days'))
], $token);

if ($tripRes['code'] !== 201) {
    echo "FAILED: Trip Creation. " . print_r($tripRes['body'], true);
    exit;
}
$tripId = $tripRes['body']['trip']['trip_id'];
echo "Trip Created (ID: $tripId).\n\n";

// 3. Define Stage 1: Transport (Arrival)
echo "3. Defining Stage 1 (Transport - Arrival)...\n";
$arrTime = date('Y-m-d H:i:00', strtotime('+1 hour'));
$endTime = date('Y-m-d H:i:00', strtotime('+3 hours'));

$stage1Res = callApi($baseUrl . "/trips/$tripId/transports", 'POST', [
    'transport_type' => 'Bus',
    'route_from' => 'Airport',
    'route_to' => 'Hotel',
    'departure_time' => $arrTime,
    'arrival_time' => $endTime, // Period defined
], $token);

if ($stage1Res['code'] === 201 || $stage1Res['code'] === 200) {
    echo "SUCCESS: Transport Stage Added.\n";
    print_r($stage1Res['body']);
} else {
    echo "FAILED: Could not add transport stage.\n";
    print_r($stage1Res['body']);
}

// 4. Define Stage 2: Activity (Ziyarat)
echo "\n4. Defining Stage 2 (Activity - Ziyarat)...\n";
$actDate = date('Y-m-d', strtotime('+1 day'));
$actTime = '09:00';
$actEndTime = '12:00';

$stage2Res = callApi($baseUrl . "/trips/$tripId/activities", 'POST', [
    'activity_type' => 'Ziyarat',
    'location' => 'Quba Mosque',
    'activity_date' => $actDate,
    'activity_time' => $actTime,
    'end_time' => $actEndTime, // Period defined (New field)
    'status' => 'SCHEDULED'
], $token);

if ($stage2Res['code'] === 201 || $stage2Res['code'] === 200) {
    echo "SUCCESS: Activity Stage Added.\n";
    print_r($stage2Res['body']);
} else {
    echo "FAILED: Could not add activity stage.\n";
    print_r($stage2Res['body']);
}

echo "\nDone.\n";
