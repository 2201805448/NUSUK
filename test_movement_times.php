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

echo "--- Test Start: Movement Times Management ---\n";

// 1. Authenticate
$adminEmail = 'admin_times_' . time() . '@example.com';
$authRes = http_post('/register', [
    'full_name' => 'Admin Times',
    'email' => $adminEmail,
    'password' => 'password123',
    'phone_number' => '1212121212',
    'role' => 'ADMIN'
]);
if ($authRes['code'] == 201)
    $token = $authRes['data']['token'];
else {
    echo "FAILED: Register\n";
    exit(1);
}

// 2. Create Trip (Helper)
$pkgRes = http_post('/packages', ['package_name' => 'T Pkg', 'price' => 1, 'duration_days' => 1, 'is_active' => true], $token);
$pkgId = $pkgRes['data']['package']['package_id'] ?? $pkgRes['data']['package']['id'];

$tripRes = http_post('/trips', [
    'package_id' => $pkgId,
    'trip_name' => 'Time Trip',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+1 day')),
    'status' => 'PLANNED'
], $token);
$tripId = $tripRes['data']['trip']['trip_id'] ?? $tripRes['data']['trip']['id'];
echo "1. Created Trip: ID $tripId\n";

// 3. Create Transport with Arrival Time (Valid)
echo "\n2. Adding Transport with Valid Time (Arrival > Departure)...\n";
$dep = date('Y-m-d H:i:s');
$arr = date('Y-m-d H:i:s', strtotime('+2 hours'));

$transRes = http_post('/transports', [
    'trip_id' => $tripId,
    'transport_type' => 'Train',
    'route_from' => 'A',
    'route_to' => 'B',
    'departure_time' => $dep,
    'arrival_time' => $arr, // Valid
    'notes' => 'On Time'
], $token);

if ($transRes['code'] == 201) {
    echo "SUCCESS: Transport Created.\n";
    $t = $transRes['data']['transport'];
    echo "  Dep: {$t['departure_time']}\n";
    echo "  Arr: {$t['arrival_time']}\n";
} else {
    echo "FAILED: Valid transport rejected.\n";
    print_r($transRes['data']);
    exit(1);
}

// 4. Create Transport with Invalid Arrival Time (Arrival < Departure)
echo "\n3. Testing Invalid Time (Arrival < Departure)...\n";
$depInv = date('Y-m-d H:i:s');
$arrInv = date('Y-m-d H:i:s', strtotime('-1 minute')); // Invalid

$invRes = http_post('/transports', [
    'trip_id' => $tripId,
    'transport_type' => 'Bus',
    'route_from' => 'A',
    'route_to' => 'B',
    'departure_time' => $depInv,
    'arrival_time' => $arrInv, // Invalid
], $token);

if ($invRes['code'] == 422) { // Unprocessable Entity (Validation Error)
    echo "SUCCESS: Invalid time correctly rejected.\n";
    // print_r($invRes['data']);
} else {
    echo "FAILURE: Invalid time accepted (Code: {$invRes['code']}).\n";
    print_r($invRes['data']);
}

echo "\nTEST PASSED!\n";
