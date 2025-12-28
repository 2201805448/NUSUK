<?php

$baseUrl = 'http://127.0.0.1:8002/api';
$runId = time();
$adminEmail = 'mod_admin_' . $runId . '@example.com';
$supEmail = 'mod_sup_' . $runId . '@example.com';
$pilEmail = 'mod_pil_' . $runId . '@example.com';
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

echo "=== Testing Modifying Housing Data ===\n\n";

// 1. Register Users
echo "1. Registering Users...\n";
$adm = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Adm', 'email' => $adminEmail, 'password' => $password, 'phone_number' => '1', 'role' => 'ADMIN']);
$adminToken = $adm['body']['token'];

$sup = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Sup', 'email' => $supEmail, 'password' => $password, 'phone_number' => '2', 'role' => 'SUPERVISOR']);
$supToken = $sup['body']['token'];

$pil = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Pil', 'email' => $pilEmail, 'password' => $password, 'phone_number' => '3', 'role' => 'PILGRIM']);
$pilId = $pil['body']['user']['user_id'];

// Setup (Trip, Group, Housing)
$pkg = callApi($baseUrl . '/packages', 'POST', ['package_name' => 'Mod Pkg', 'price' => 1000, 'duration_days' => 5, 'is_active' => true], $adminToken);
$pkgId = $pkg['body']['package']['package_id'];

$trip = callApi($baseUrl . '/trips', 'POST', ['package_id' => $pkgId, 'trip_name' => 'Mod Trip', 'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d', strtotime('+5 days'))], $adminToken);
$tripId = $trip['body']['trip']['trip_id'];

$grp = callApi($baseUrl . "/trips/$tripId/groups", 'POST', ['group_code' => 'M-' . $runId], $supToken);
$grpId = $grp['body']['group']['group_id'];

// Add Pilgrim to Group
$addRes = callApi($baseUrl . "/groups/$grpId/members", 'POST', ['user_id' => $pilId], $supToken);
$realPilgrimId = $addRes['body']['member']['pilgrim_id'];

// Housing Setup
$acc = callApi($baseUrl . '/accommodations', 'POST', ['hotel_name' => 'Mod Hotel', 'city' => 'Makkah', 'room_type' => 'Standard', 'capacity' => 100], $adminToken);
$accId = $acc['body']['accommodation']['accommodation_id'];
callApi($baseUrl . "/trips/$tripId/hotels", 'POST', ['accommodation_id' => $accId], $adminToken);

$room1 = callApi($baseUrl . '/rooms', 'POST', ['accommodation_id' => $accId, 'room_number' => '401', 'floor' => 4, 'room_type' => 'Twin', 'status' => 'AVAILABLE'], $adminToken);
$room1Id = $room1['body']['room']['id'];

$room2 = callApi($baseUrl . '/rooms', 'POST', ['accommodation_id' => $accId, 'room_number' => '402', 'floor' => 4, 'room_type' => 'Single', 'status' => 'AVAILABLE'], $adminToken);
$room2Id = $room2['body']['room']['id'];

// Initial Assignment
$assignRes = callApi($baseUrl . '/room-assignments', 'POST', [
    'pilgrim_id' => $realPilgrimId,
    'accommodation_id' => $accId,
    'room_id' => $room1Id,
    'check_in' => date('Y-m-d H:i:s'),
    'check_out' => date('Y-m-d H:i:s', strtotime('+5 days')),
    'status' => 'CONFIRMED'
], $supToken);

if ($assignRes['code'] !== 201) {
    die("Failed initial assignment");
}
$assignId = $assignRes['body']['assignment']['assignment_id'];
echo "Initial Assignment (Room 401, ID: $assignId) Created.\n\n";

// 2. Modify Assignment (Change to Room 402)
echo "2. Modifying Assignment (Change to Room 402)...\n";
$updateRes = callApi($baseUrl . "/room-assignments/$assignId", 'PUT', [
    'room_id' => $room2Id
], $supToken);

if ($updateRes['code'] === 200) {
    echo "SUCCESS: Assignment updated.\n";
    $updated = $updateRes['body']['assignment'];
    echo "New Room ID: " . $updated['room_id'] . "\n";

    if ($updated['room_id'] == $room2Id) {
        echo "VERIFICATION PASSED.\n";
    } else {
        echo "VERIFICATION FAILED: Room ID mismatch.\n";
    }
} else {
    echo "FAILED: Update Assignment.\n";
    print_r($updateRes['body']);
}

echo "\nDone.\n";
