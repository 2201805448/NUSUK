<?php

$baseUrl = 'http://127.0.0.1:8002/api';
$runId = time();
$adminEmail = 'assign_admin_' . $runId . '@example.com';
$supEmail = 'assign_sup_' . $runId . '@example.com';
$pilEmail = 'assign_pil_' . $runId . '@example.com';
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
    if (!empty($data) && $method !== 'GET') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    curl_setopt($ch, CURLOPT_URL, $url);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

echo "=== Testing Room Assignment (Supervisor) ===\n\n";

// 1. Register Users
echo "1. Registering Users...\n";
$adm = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Adm', 'email' => $adminEmail, 'password' => $password, 'phone_number' => '1', 'role' => 'ADMIN']);
$adminToken = $adm['body']['token'];

$sup = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Sup', 'email' => $supEmail, 'password' => $password, 'phone_number' => '2', 'role' => 'SUPERVISOR']);
$supToken = $sup['body']['token'];

$pil = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Pil', 'email' => $pilEmail, 'password' => $password, 'phone_number' => '3', 'role' => 'PILGRIM']);
$pilId = $pil['body']['user']['user_id'];
$pilToken = $pil['body']['token'];

// Create Pilgrim Profile via Group add (simulated workflow)
$pkg = callApi($baseUrl . '/packages', 'POST', ['package_name' => 'Assign Pkg', 'price' => 1000, 'duration_days' => 5, 'is_active' => true], $adminToken);
$pkgId = $pkg['body']['package']['package_id'];

$trip = callApi($baseUrl . '/trips', 'POST', ['package_id' => $pkgId, 'trip_name' => 'Assign Trip', 'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d', strtotime('+5 days'))], $adminToken);
$tripId = $trip['body']['trip']['trip_id'];

$grp = callApi($baseUrl . "/trips/$tripId/groups", 'POST', ['group_code' => 'A-' . $runId], $supToken);
$grpId = $grp['body']['group']['group_id'];

$addRes = callApi($baseUrl . "/groups/$grpId/members", 'POST', ['user_id' => $pilId], $supToken);
if ($addRes['code'] !== 201) {
    die("Failed to add member to group (and create pilgrim profile).");
}
$realPilgrimId = $addRes['body']['member']['pilgrim_id'];
echo "Users & Base Data Ready ($realPilgrimId).\n\n";

// 2. Setup Housing (Admin)
echo "2. Setting up Housing...\n";
$acc = callApi($baseUrl . '/accommodations', 'POST', [
    'hotel_name' => 'Assign Hotel ' . $runId,
    'city' => 'Madinah',
    'room_type' => 'Standard',
    'capacity' => 100
], $adminToken);
$accId = $acc['body']['accommodation']['accommodation_id'];

callApi($baseUrl . "/trips/$tripId/hotels", 'POST', ['accommodation_id' => $accId], $adminToken);

$room = callApi($baseUrl . '/rooms', 'POST', [
    'accommodation_id' => $accId,
    'room_number' => '202',
    'floor' => 2,
    'room_type' => 'Twin',
    'status' => 'AVAILABLE'
], $adminToken);
$roomId = $room['body']['room']['id'];
echo "Hotel & Room ($roomId) Created.\n\n";

// 3. Assign Room as Supervisor
echo "3. Assigning Room as Supervisor...\n";
$assignRes = callApi($baseUrl . '/room-assignments', 'POST', [
    'pilgrim_id' => $realPilgrimId,
    'accommodation_id' => $accId,
    'room_id' => $roomId,
    'check_in' => date('Y-m-d H:i:s'),
    'check_out' => date('Y-m-d H:i:s', strtotime('+5 days')),
    'status' => 'CONFIRMED'
], $supToken); // Using Supervisor Token

if ($assignRes['code'] === 201) {
    echo "SUCCESS: Room assigned by Supervisor.\n";
    print_r($assignRes['body']['assignment']);
    echo "VERIFICATION PASSED.\n";
} else {
    echo "FAILED: Assignment rejected.\n";
    print_r($assignRes['body']);
}

echo "\nDone.\n";
