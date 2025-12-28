<?php

$baseUrl = 'http://127.0.0.1:8002/api';
$runId = time();
$adminEmail = 'mod_admin_' . $runId . '@example.com';
$pilgrimEmail = 'mod_pil_' . $runId . '@example.com';
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
    curl_setopt($ch, CURLOPT_URL, $url);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

echo "=== Testing Booking Modification Request ===\n\n";

// 1. Register Users
echo "1. Registering Users...\n";
$adm = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Adm', 'email' => $adminEmail, 'password' => $password, 'phone_number' => '1', 'role' => 'ADMIN']);
$adminToken = $adm['body']['token'];

$pil = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Pil', 'email' => $pilgrimEmail, 'password' => $password, 'phone_number' => '2', 'role' => 'PILGRIM']);
$pilgrimToken = $pil['body']['token'];
echo "Users Registered.\n\n";

// 2. Setup Booking
echo "2. Setting up Booking...\n";
$pkg = callApi($baseUrl . '/packages', 'POST', ['package_name' => 'Mod Pkg', 'price' => 1000, 'duration_days' => 5, 'is_active' => true], $adminToken);
$pkgId = $pkg['body']['package']['package_id'];

$trip = callApi($baseUrl . '/trips', 'POST', ['package_id' => $pkgId, 'trip_name' => 'Mod Trip', 'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d', strtotime('+5 days')), 'status' => 'PLANNED'], $adminToken);
$tripId = $trip['body']['trip']['trip_id'];

$bk = callApi($baseUrl . '/bookings', 'POST', ['trip_id' => $tripId], $pilgrimToken);
$bkId = $bk['body']['booking']['booking_id'];
echo "Booking Created (ID: $bkId).\n\n";

// 3. Request Modification
echo "3. Requesting Modification (Change Companions)...\n";
$modData = [
    'request_type' => 'CHANGE_COMPANIONS',
    'request_data' => [
        'remove_guest' => 'John Doe',
        'add_guest' => [
            'name' => 'Jane Smith',
            'passport' => 'X12345'
        ]
    ]
];

$res = callApi($baseUrl . "/bookings/$bkId/request-modification", 'POST', $modData, $pilgrimToken);

if ($res['code'] === 201) {
    echo "SUCCESS: Request submitted.\n";
    $m = $res['body']['modification'];
    echo "ID: " . $m['modification_id'] . "\n";
    echo "Status: " . $m['status'] . "\n";
    echo "Type: " . $m['request_type'] . "\n";

    if ($m['status'] === 'PENDING' && $m['request_type'] === 'CHANGE_COMPANIONS') {
        echo "VERIFICATION PASSED.\n";
    } else {
        echo "VERIFICATION FAILED: Status mismatch.\n";
    }
} else {
    echo "FAILED: Request submission.\n";
    print_r($res['body']);
}

echo "\nDone.\n";
