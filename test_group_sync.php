<?php

$baseUrl = 'http://127.0.0.1:8000/api';
$timestamp = time();
$adminEmail = 'admin_' . $timestamp . '@example.com';
$pilgrimEmail = 'pilgrim_' . $timestamp . '@example.com';
$password = 'password123';

// Helper function for cURL
function callApi($url, $method = 'GET', $data = [], $token = null)
{
    echo "Calling $method $url...\n";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

// 1. Register Admin
echo "1. Registering Admin...\n";
$res = callApi($baseUrl . '/register', 'POST', [
    'full_name' => 'Admin User',
    'email' => $adminEmail,
    'phone_number' => '9999999999',
    'password' => $password,
    'role' => 'ADMIN'
]);

if ($res['code'] !== 201) {
    die("Failed to register admin: " . json_encode($res['body']) . "\n");
}
$adminId = $res['body']['user']['user_id'];
$token = $res['body']['token'];
echo "Admin registered with ID: $adminId\n";

// 2. Register Pilgrim User
echo "\n2. Registering Pilgrim User...\n";
$res = callApi($baseUrl . '/register', 'POST', [
    'full_name' => 'Pilgrim User',
    'email' => $pilgrimEmail,
    'phone_number' => '8888888888',
    'password' => $password,
    'role' => 'USER'
]);
if ($res['code'] !== 201) {
    die("Failed to register pilgrim: " . json_encode($res['body']) . "\n");
}
$pilgrimUserId = $res['body']['user']['user_id'];
echo "Pilgrim registered with ID: $pilgrimUserId\n";

// 3. Create Trip
echo "\n3. Creating Trip...\n";
$res = callApi($baseUrl . '/trips', 'POST', [
    'trip_name' => 'Test Trip ' . $timestamp,
    'start_date' => date('Y-m-d', strtotime('+1 day')),
    'end_date' => date('Y-m-d', strtotime('+10 days')),
    'price' => 5000,
    'status' => 'UPCOMING',
    'package_id' => 1 // Assuming package 1 exists or is nullable? Let's hope validation allows or we might fail here.
    // If package_id is required and fails, I might need to create one.
    // Checking previous file lists... TripController store validation? Not fully checked.
    // Let's assume basic trip creation works or try simpler.
], $token);

if ($res['code'] !== 201) {
    // If package_id is the issue, let's try creating a trip without it if validation allows, or create package first.
    // For now, let's proceed and see output.
    echo "Trip creation failed, checking if validation error...\n";
    // echo json_encode($res['body']) . "\n";
    // Attempting to create group global if trip creation fails, but storeGroup requires trip_id.
    // Let's try to get existing trips first.
    $tripsRes = callApi($baseUrl . '/trips', 'GET', [], $token);
    if (!empty($tripsRes['body']) && count($tripsRes['body']) > 0) {
        $tripId = $tripsRes['body'][0]['trip_id'];
        echo "Using existing trip ID: $tripId\n";
    } else {
        die("Could not create or find a trip.\n");
    }
} else {
    $tripId = $res['body']['trip']['trip_id'];
    echo "Trip created with ID: $tripId\n";
}

// 4. Create Group
echo "\n4. Creating Group...\n";
$res = callApi($baseUrl . '/trips/' . $tripId . '/groups', 'POST', [
    'group_code' => 'GRP-' . $timestamp,
    'group_status' => 'ACTIVE'
], $token);

if ($res['code'] !== 201) {
    die("Failed to create group: " . json_encode($res['body']) . "\n");
}
$groupId = $res['body']['group']['group_id'];
echo "Group created with ID: $groupId\n";

// 5. Update Group with Pilgrim Sync
echo "\n5. Updating Group to Sync Pilgrim...\n";
$res = callApi($baseUrl . '/groups/' . $groupId, 'PUT', [
    'group_code' => 'GRP-' . $timestamp . '-UPDATED',
    'pilgrim_ids' => [$pilgrimUserId]
], $token);

echo "Response Code: " . $res['code'] . "\n";
echo "Response Body: " . json_encode($res['body'], JSON_PRETTY_PRINT) . "\n";

if ($res['code'] === 200) {
    // Verify pilgrim is in the response or fetch group again
    $group = $res['body']['group'];
    $pilgrims = $group['pilgrims'] ?? [];
    $found = false;
    foreach ($pilgrims as $p) {
        if ($p['user_id'] == $pilgrimUserId) { // Pilgrim relation usually has nested User?
            // Based on my code: $group->load('pilgrims.user')
            // Pilgrim model belongsTo User. So pilgrim->user->user_id
            // The response structure depends on serialization.
            // Let's check the output manually.
            $found = true;
            break;
        }
        // Also check if p['user']['user_id'] exists
        if (isset($p['user']) && $p['user']['user_id'] == $pilgrimUserId) {
            $found = true;
            break;
        }
    }

    if ($found) {
        echo "SUCCESS: Pilgrim synced correctly.\n";
    } else {
        echo "WARNING: Pilgrim not found in response. Checking via GET...\n";
        $getRes = callApi($baseUrl . '/groups/' . $groupId . '/pilgrims', 'GET', [], $token);
        // Note: endpoint might be /groups/{id}/pilgrims or manual check
        // My GroupController has listPilgrims at /groups/{id}/pilgrims
    }
} else {
    echo "FAILURE: Update failed.\n";
}

