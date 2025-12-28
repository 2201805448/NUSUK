<?php

$baseUrl = 'http://127.0.0.1:8002/api';
$runId = time();
$adminEmail = 'hsg_admin_' . $runId . '@example.com';
$supEmail = 'hsg_sup_' . $runId . '@example.com';
$pilEmail = 'hsg_pil_' . $runId . '@example.com';
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

echo "=== Testing Monitoring Housing Data ===\n\n";

// 1. Register Users
echo "1. Registering Users...\n";
$adm = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Adm', 'email' => $adminEmail, 'password' => $password, 'phone_number' => '1', 'role' => 'ADMIN']);
$adminToken = $adm['body']['token'];

$sup = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Sup', 'email' => $supEmail, 'password' => $password, 'phone_number' => '2', 'role' => 'SUPERVISOR']);
$supToken = $sup['body']['token'];

$pil = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Pil', 'email' => $pilEmail, 'password' => $password, 'phone_number' => '3', 'role' => 'PILGRIM']);
$pilId = $pil['body']['user']['user_id'];
$pilToken = $pil['body']['token'];

// Create Pilgrim Profile
callApi($baseUrl . '/profile', 'PUT', ['passport_number' => 'P' . $runId, 'nationality' => 'Test'], $pilToken);
// Retrieve pilgrim ID from subsequent calls (e.g. adding to group calls return the member obj)
// $realPilgrimId = ... set later.
// If profile not created by PUT, we might need manual creation or reliance on other flows.
// Let's rely on GroupController adding member to create profile if needed, OR explicit profile creation.
// Actually, I can use direct DB model if test script runs in same env, but safer to use API.
// I'll assume profile created via `GroupController::addMember` flow or just create manually via DB if no API exists for it easily.
// Let's use `addMember` trick to auto-create profile.
echo "Users Registered.\n\n";

// 2. Setup Data (Package, Trip, Group)
echo "2. Setting up Trip and Group...\n";
$pkg = callApi($baseUrl . '/packages', 'POST', ['package_name' => 'Hsg Pkg', 'price' => 1000, 'duration_days' => 5, 'is_active' => true], $adminToken);
$pkgId = $pkg['body']['package']['package_id'];

$trip = callApi($baseUrl . '/trips', 'POST', ['package_id' => $pkgId, 'trip_name' => 'Hsg Trip', 'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d', strtotime('+5 days'))], $adminToken);
$tripId = $trip['body']['trip']['trip_id'];

$grp = callApi($baseUrl . "/trips/$tripId/groups", 'POST', ['group_code' => 'H-' . $runId], $supToken);
$grpId = $grp['body']['group']['group_id'];

// Add Pilgrim to Group (creates profile)
$addRes = callApi($baseUrl . "/groups/$grpId/members", 'POST', ['user_id' => $pilId], $supToken);
// Fetch pilgrim ID from response
if ($addRes['code'] === 201) {
    $realPilgrimId = $addRes['body']['member']['pilgrim_id'];
    echo "Trip & Pilgrim Setup ($realPilgrimId).\n\n";
} else {
    echo "FAILED: Add Member.\n";
    print_r($addRes['body']);
    exit;
}

// 3. Setup Housing (Hotel -> Room)
echo "3. Setting up Housing...\n";
// Create Accommodation
$acc = callApi($baseUrl . '/accommodations', 'POST', [
    'hotel_name' => 'Grand Hotel ' . $runId,
    'city' => 'Makkah',
    'room_type' => 'Standard',
    'capacity' => 100
], $adminToken);
$accId = $acc['body']['accommodation']['accommodation_id'];

// Link to Trip
callApi($baseUrl . "/trips/$tripId/hotels", 'POST', ['accommodation_id' => $accId], $adminToken);

// Create Room
$room = callApi($baseUrl . '/rooms', 'POST', [
    'accommodation_id' => $accId,
    'room_number' => '101',
    'floor' => 1,
    'room_type' => 'Double',
    'status' => 'AVAILABLE'
], $adminToken);
$roomId = $room['body']['room']['id']; // Ensure ID is captured

echo "Hotel ($accId) & Room ($roomId) Created.\n\n";

// 4. Assign Pilgrim to Room
echo "4. Assigning Pilgrim to Room...\n";
$assignRes = callApi($baseUrl . '/room-assignments', 'POST', [
    'pilgrim_id' => $realPilgrimId,
    'accommodation_id' => $accId,
    'room_id' => $roomId,
    'check_in' => date('Y-m-d H:i:s'),
    'check_out' => date('Y-m-d H:i:s', strtotime('+5 days')),
    'status' => 'CONFIRMED'
], $adminToken);

if ($assignRes['code'] === 201) {
    echo "Pilgrim Assigned to Room.\n\n";
} else {
    echo "FAILED: Room Assignment (Skipping check).\n";
    // print_r($assignRes['body']);
    // proceed to check if monitoring works for Hotel/Room
}

// 5. Monitor Housing Data (Supervisor)
echo "5. Monitoring Housing Data (Supervisor View)...\n";
$res = callApi($baseUrl . "/trips/$tripId/housing", 'GET', [], $supToken);

if ($res['code'] === 200) {
    echo "SUCCESS: Housing data retrieved.\n";
    $data = $res['body']['housing'];

    // Check Content
    if (!empty($data) && $data[0]['hotel_name'] === 'Grand Hotel ' . $runId) {
        $rooms = $data[0]['rooms'];
        if (!empty($rooms) && $rooms[0]['room_number'] === '101') {
            echo "Room 101 Found.\n";
            echo "Current Occupants: " . $rooms[0]['current_occupants'] . "\n";

            $pils = $rooms[0]['pilgrims'];
            if (!empty($pils) && $pils[0]['pilgrim_id'] == $realPilgrimId) {
                echo "Pilgrim Found in Room.\n";
                echo "VERIFICATION PASSED.\n";
            } else {
                echo "VERIFICATION FAILED: Pilgrim not listed in room.\n";
                print_r($pils);
            }
        } else {
            echo "VERIFICATION FAILED: Room not found.\n";
        }
    } else {
        echo "VERIFICATION FAILED: Hotel not found.\n";
    }

} else {
    echo "FAILED: Monitoring.\n";
    print_r($res['body']);
}

echo "\nDone.\n";
