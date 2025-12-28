<?php

$baseUrl = 'http://127.0.0.1:8002/api';
$runId = time();
$adminEmail = 'card_admin_' . $runId . '@example.com';
$supEmail = 'card_sup_' . $runId . '@example.com';
$pilEmail = 'card_pil_' . $runId . '@example.com';
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

echo "=== Testing Pilgrim Card ===\n\n";

// 1. Register Users
echo "1. Registering Users...\n";
$adm = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Adm', 'email' => $adminEmail, 'password' => $password, 'phone_number' => '1', 'role' => 'ADMIN']);
$adminToken = $adm['body']['token'];

$sup = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Sup', 'email' => $supEmail, 'password' => $password, 'phone_number' => '2', 'role' => 'SUPERVISOR']);
$supToken = $sup['body']['token'];

$pil = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Pil', 'email' => $pilEmail, 'password' => $password, 'phone_number' => '3', 'role' => 'PILGRIM']);
$pilId = $pil['body']['user']['user_id'];
$pilToken = $pil['body']['token'];

// 2. Setup (Trip, Group, Housing)
$pkg = callApi($baseUrl . '/packages', 'POST', ['package_name' => 'Card Pkg', 'price' => 1000, 'duration_days' => 5, 'is_active' => true], $adminToken);
$pkgId = $pkg['body']['package']['package_id'];

$trip = callApi($baseUrl . '/trips', 'POST', ['package_id' => $pkgId, 'trip_name' => 'Card Trip', 'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d', strtotime('+5 days'))], $adminToken);
$tripId = $trip['body']['trip']['trip_id'];

$grp = callApi($baseUrl . "/trips/$tripId/groups", 'POST', ['group_code' => 'C-' . $runId], $supToken);
$grpId = $grp['body']['group']['group_id'];

// Add Pilgrim to Group
$addRes = callApi($baseUrl . "/groups/$grpId/members", 'POST', ['user_id' => $pilId], $supToken);
if ($addRes['code'] !== 201) {
    die("Failed to add member: " . print_r($addRes['body'], true));
}
$realPilgrimId = $addRes['body']['member']['pilgrim_id'];
// Note: Direct DB access works here only if run on server. If API-only test desired, fetch from member response response. 
// I'll assume direct DB is fine for test environment now, or I'd rely on 'member' response.

// 3. Housing Assignment
$acc = callApi($baseUrl . '/accommodations', 'POST', ['hotel_name' => 'Card Hotel', 'city' => 'Makkah', 'room_type' => 'Standard', 'capacity' => 100], $adminToken);
$accId = $acc['body']['accommodation']['accommodation_id'];
callApi($baseUrl . "/trips/$tripId/hotels", 'POST', ['accommodation_id' => $accId], $adminToken);

$room = callApi($baseUrl . '/rooms', 'POST', ['accommodation_id' => $accId, 'room_number' => '303', 'floor' => 3, 'room_type' => 'Twin', 'status' => 'AVAILABLE'], $adminToken);
$roomId = $room['body']['room']['id'];

$assignRes = callApi($baseUrl . '/room-assignments', 'POST', [
    'pilgrim_id' => $realPilgrimId,
    'accommodation_id' => $accId,
    'room_id' => $roomId,
    'check_in' => date('Y-m-d H:i:s'),
    'check_out' => date('Y-m-d H:i:s', strtotime('+5 days')),
    'status' => 'CONFIRMED'
], $supToken);

if ($assignRes['code'] !== 201) {
    die("Failed to assign room: " . print_r($assignRes['body'], true));
}

echo "Setup Complete.\n\n";

// 4. Retrieve Pilgrim Card
echo "4. Retrieving Pilgrim Card...\n";
$cardRes = callApi($baseUrl . '/pilgrim/card', 'GET', [], $pilToken);

if ($cardRes['code'] === 200) {
    echo "SUCCESS: Card retrieved.\n";
    $card = $cardRes['body']['card'];
    // Verification
    echo "Name: " . $card['full_name'] . "\n";
    echo "Group: " . ($card['group']['group_code'] ?? 'N/A') . "\n";
    echo "Hotel: " . ($card['housing']['hotel_name'] ?? 'N/A') . "\n";
    echo "Room: " . ($card['housing']['room_number'] ?? 'N/A') . "\n";
    echo "QR Content: " . ($card['qr_code_content'] ?? 'N/A') . "\n";

    if ($card['group']['group_code'] === 'C-' . $runId && isset($card['housing']['room_number']) && $card['housing']['room_number'] === '303') {
        echo "VERIFICATION PASSED.\n";
    } else {
        echo "VERIFICATION FAILED: Data mismatch.\n";
        print_r($card);
    }
} else {
    echo "FAILED: Get Card.\n";
    print_r($cardRes['body']);
}

echo "\nDone.\n";
