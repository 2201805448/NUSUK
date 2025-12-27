<?php

$baseUrl = 'http://127.0.0.1:8000/api';
$token = null;

// Helper Functions (Reused)
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

function http_put($url, $data, $token = null)
{
    global $baseUrl;
    $ch = curl_init($baseUrl . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
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

function http_delete($url, $token = null)
{
    global $baseUrl;
    $ch = curl_init($baseUrl . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    $headers = ['Accept: application/json'];
    if ($token)
        $headers[] = "Authorization: Bearer $token";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'data' => json_decode($response, true)];
}

echo "--- Test Start: Room Management ---\n";

// 1. Authenticate (Register new Admin)
$adminEmail = 'admin_room_' . time() . '@example.com';
echo "\n1. Registering Admin ($adminEmail)...\n";
$authRes = http_post('/register', [
    'full_name' => 'Admin Room',
    'email' => $adminEmail,
    'password' => 'password123',
    'phone_number' => '5555555555',
    'role' => 'ADMIN'
]);

if ($authRes['code'] != 201) {
    echo "FAILED: Authentication.\n";
    print_r($authRes['data']);
    exit(1);
}
$token = $authRes['data']['token'];
echo "Token Acquired.\n";

// 2. Create Hotel (Accommodation)
echo "\n2. Creating Hotel for Room Test...\n";
$accRes = http_post('/accommodations', [
    'hotel_name' => 'Room Test Hotel',
    'city' => 'Riyadh',
    'room_type' => 'Various', // General type
    'capacity' => 100
], $token);

if ($accRes['code'] != 201) {
    echo "FAILED: Hotel Creation.\n";
    print_r($accRes['data']);
    exit(1);
}
$accId = $accRes['data']['accommodation']['accommodation_id'];
echo "Hotel Created: ID $accId\n";

// 3. Add Rooms (Store)
echo "\n3. Adding Rooms...\n";
$room1Res = http_post('/rooms', [
    'accommodation_id' => $accId,
    'room_number' => '101',
    'floor' => 1,
    'room_type' => 'Single',
    'status' => 'AVAILABLE'
], $token);

$room2Res = http_post('/rooms', [
    'accommodation_id' => $accId,
    'room_number' => '102',
    'floor' => 1,
    'room_type' => 'Double',
    'status' => 'OCCUPIED'
], $token);

if ($room1Res['code'] == 201 && $room2Res['code'] == 201) {
    echo "Rooms 101 and 102 Created.\n";
    $room1Id = $room1Res['data']['room']['id'];
} else {
    echo "FAILED: Room Creation.\n";
    print_r($room1Res['data']);
    print_r($room2Res['data']);
    exit(1);
}

// 4. List Rooms (Index)
echo "\n4. Listing Rooms (Filtered by Hotel)...\n";
$indexRes = http_get("/rooms?accommodation_id=$accId", $token);
$rooms = $indexRes['data'];
echo "Found " . count($rooms) . " rooms.\n";
if (count($rooms) !== 2) {
    echo "FAILED: Expected 2 rooms.\n";
    exit(1);
}

// 5. Update Room (Update)
echo "\n5. Updating Room 101 Status...\n";
$updateRes = http_put("/rooms/$room1Id", [
    'status' => 'MAINTENANCE',
    'notes' => 'AC Repair'
], $token);

if ($updateRes['code'] == 200 && $updateRes['data']['room']['status'] == 'MAINTENANCE') {
    echo "Room Updated Successfully.\n";
} else {
    echo "FAILED: Update.\n";
    print_r($updateRes['data']);
}

// 6. Delete Room (Destroy)
echo "\n6. Deleting Room 101...\n";
$delRes = http_delete("/rooms/$room1Id", $token);
if ($delRes['code'] == 200) {
    echo "Room Deleted.\n";
} else {
    echo "FAILED: Delete.\n";
    print_r($delRes['data']);
}

// 7. Verify Deletion
echo "\n7. Verifying Deletion...\n";
$listFinal = http_get("/rooms?accommodation_id=$accId", $token);
if (count($listFinal['data']) == 1) {
    echo "Verified: 1 room remaining.\n";
    echo "\nTEST PASSED!\n";
} else {
    echo "FAILED: Expected 1 room, found " . count($listFinal['data']) . "\n";
}
