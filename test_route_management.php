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

echo "--- Test Start: Transport Route Management ---\n";

// 1. Authenticate
$adminEmail = 'admin_route_' . time() . '@example.com';
$authRes = http_post('/register', [
    'full_name' => 'Admin Route',
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

// 2. Create Route
echo "\n2. Creating Route...\n";
$routeRes = http_post('/routes', [
    'route_name' => 'Jeddah Apt -> Makkah Hotel',
    'start_location' => 'Jeddah International Airport',
    'end_location' => 'Makkah Clock Tower Hotel',
    'distance_km' => 95.5,
    'estimated_duration_mins' => 75
], $token);

if ($routeRes['code'] == 201) {
    $routeId = $routeRes['data']['route']['id'];
    echo "Route Created: ID $routeId\n";
    echo "  From: " . $routeRes['data']['route']['start_location'] . "\n";
    echo "  To:   " . $routeRes['data']['route']['end_location'] . "\n";
} else {
    echo "FAILED: Create Route\n";
    print_r($routeRes['data']);
    exit(1);
}

// 3. Create Trip (Helper)
$pkgRes = http_post('/packages', ['package_name' => 'R Pkg', 'price' => 1, 'duration_days' => 1, 'is_active' => true], $token);
$pkgId = $pkgRes['data']['package']['package_id'] ?? $pkgRes['data']['package']['id'];

$tripRes = http_post('/trips', [
    'package_id' => $pkgId,
    'trip_name' => 'Route Trip',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+1 day')),
    'status' => 'PLANNED'
], $token);
$tripId = $tripRes['data']['trip']['trip_id'] ?? $tripRes['data']['trip']['id'];
echo "\n3. Created Trip: ID $tripId\n";

// 4. Create Transport using Route ID
// Note: We omit route_from and route_to to test auto-filling
echo "\n4. Adding Transport using Route ID (expecting auto-fill)...\n";
$transRes = http_post('/transports', [
    'trip_id' => $tripId,
    'route_id' => $routeId,
    'transport_type' => 'Bus',
    // route_from & route_to OMITTED purposely
    'departure_time' => date('Y-m-d H:i:s'),
    'notes' => 'Using standard route'
], $token);

if ($transRes['code'] == 201) {
    echo "Transport Created.\n";
    $t = $transRes['data']['transport'];
    // Verification
    $expectedFrom = 'Jeddah International Airport';
    $expectedTo = 'Makkah Clock Tower Hotel';

    echo "  Route From: {$t['route_from']}\n";
    echo "  Route To:   {$t['route_to']}\n";

    if ($t['route_from'] === $expectedFrom && $t['route_to'] === $expectedTo) {
        echo "SUCCESS: Auto-fill worked correctly.\n";
    } else {
        echo "FAILURE: Auto-fill mismatch.\n";
    }

} else {
    echo "FAILED: Create Transport\n";
    print_r($transRes['data']);
    exit(1);
}

// 5. List Routes
$listRes = http_get('/routes', $token);
if (count($listRes['data']) >= 1) {
    echo "\n5. List Routes: Found " . count($listRes['data']) . " route(s).\n";
    echo "\nTEST PASSED!\n";
} else {
    echo "FAILED: List Routes empty.\n";
}
