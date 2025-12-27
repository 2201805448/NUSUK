<?php

$baseUrl = 'http://127.0.0.1:8000/api';
$token = null;

// Helper Functions
function http_post($url, $data, $token = null)
{
    global $baseUrl;
    $ch = curl_init($baseUrl . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    if ($token)
        $headers[] = "Authorization: Bearer $token";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'data' => json_decode($response, true)];
}

function http_get($url, $token = null)
{
    global $baseUrl;
    $ch = curl_init($baseUrl . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = ['Accept: application/json'];
    if ($token)
        $headers[] = "Authorization: Bearer $token";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'data' => json_decode($response, true)];
}

echo "--- Test Start: Driver Management ---\n";

// 1. Authenticate
$adminEmail = 'admin_driver_' . time() . '@example.com';
$authRes = http_post('/register', [
    'full_name' => 'Admin Driver',
    'email' => $adminEmail,
    'password' => 'password123',
    'phone_number' => '1112223333',
    'role' => 'ADMIN'
]);
if ($authRes['code'] == 201) {
    $token = $authRes['data']['token'];
    echo "1. Registered Admin ($adminEmail)\n";
} else {
    echo "FAILED: Register\n";
    print_r($authRes['data']);
    exit(1);
}

// 2. Create Driver
echo "\n2. Creating Driver...\n";
$driverRes = http_post('/drivers', [
    'name' => 'Mohammed Ali',
    'license_number' => 'DL' . time(),
    'phone_number' => '0555123456',
    'status' => 'ACTIVE'
], $token);

if ($driverRes['code'] == 201) {
    $driverId = $driverRes['data']['driver']['driver_id'];
    // Note: Laravel defaults to 'id' in JSON usually if not configured otherwise in resource, 
    // but the model has $primaryKey = 'driver_id'. Let's check what comes back.
    if (!$driverId && isset($driverRes['data']['driver']['id'])) {
        $driverId = $driverRes['data']['driver']['id'];
    } elseif (!$driverId && isset($driverRes['data']['driver']['driver_id'])) {
        $driverId = $driverRes['data']['driver']['driver_id'];
    }

    echo "Driver Created: ID $driverId\n";
} else {
    echo "FAILED: Create Driver\n";
    print_r($driverRes['data']);
    exit(1);
}

// 3. Create Trip (Helper)
$pkgRes = http_post('/packages', ['package_name' => 'D Pkg', 'price' => 1, 'duration_days' => 1, 'is_active' => true], $token);
$pkgId = $pkgRes['data']['package']['package_id'] ?? $pkgRes['data']['package']['id'];

$tripRes = http_post('/trips', [
    'package_id' => $pkgId,
    'trip_name' => 'Driver Trip',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+1 day')),
    'status' => 'PLANNED'
], $token);
$tripId = $tripRes['data']['trip']['trip_id'] ?? $tripRes['data']['trip']['id'];
echo "\n3. Created Trip: ID $tripId\n";

// 4. Create Transport with Driver
echo "\n4. Adding Transport with Driver...\n";
$transRes = http_post('/transports', [
    'trip_id' => $tripId,
    'driver_id' => $driverId,
    'transport_type' => 'Bus',
    'route_from' => 'A',
    'route_to' => 'B',
    'departure_time' => date('Y-m-d H:i:s'),
    'notes' => 'With Driver'
], $token);

if ($transRes['code'] == 201) {
    echo "Transport Created.\n";
    $assignedDriverId = $transRes['data']['transport']['driver_id'];
    if ($assignedDriverId == $driverId) {
        echo "SUCCESS: Driver correctly assigned to transport.\n";
    } else {
        echo "FAILURE: Driver ID mismatch. Expected $driverId, got $assignedDriverId\n";
    }
} else {
    echo "FAILED: Create Transport\n";
    print_r($transRes['data']);
    exit(1);
}

// 5. List Drivers
$listRes = http_get('/drivers', $token);
if (count($listRes['data']) >= 1) {
    echo "\n5. List Drivers: Found " . count($listRes['data']) . " driver(s).\n";
    echo "\nTEST PASSED!\n";
} else {
    echo "FAILED: List Drivers empty.\n";
}
