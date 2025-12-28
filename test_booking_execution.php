<?php

$baseUrl = 'http://127.0.0.1:8002/api';
$runId = time();
$adminEmail = 'book_admin_' . $runId . '@example.com';
$pilgrimEmail = 'book_pil_' . $runId . '@example.com';
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

echo "=== Testing Booking Execution ===\n\n";

// 1. Register Users
echo "1. Registering Users...\n";
$adm = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Adm', 'email' => $adminEmail, 'password' => $password, 'phone_number' => '1', 'role' => 'ADMIN']);
$adminToken = $adm['body']['token'];

$pil = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Pil', 'email' => $pilgrimEmail, 'password' => $password, 'phone_number' => '2', 'role' => 'PILGRIM']);
$pilgrimToken = $pil['body']['token'];
echo "Users Registered.\n\n";

// 2. Setup (Package & Trip)
echo "2. Setting up Trip...\n";
// Package
$pkg = callApi($baseUrl . '/packages', 'POST', ['package_name' => 'Bk Pkg', 'price' => 1000, 'duration_days' => 5, 'is_active' => true], $adminToken);
$pkgId = $pkg['body']['package']['package_id'];

// Trip
$trip = callApi($baseUrl . '/trips', 'POST', [
    'package_id' => $pkgId,
    'trip_name' => 'Booking Trip',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+5 days')),
    'status' => 'PLANNED'
], $adminToken);
$tripId = $trip['body']['trip']['trip_id'];
echo "Trip Created (ID: $tripId).\n\n";

// 3. Execute Booking
echo "3. Pilgrim Book Trip...\n";
$bk = callApi($baseUrl . '/bookings', 'POST', [
    'trip_id' => $tripId,
    'pay_method' => 'Credit Card',
    'request_notes' => 'Vegetarian meal'
], $pilgrimToken);

if ($bk['code'] === 201) {
    echo "SUCCESS: Booking created.\n";
    $b = $bk['body']['booking'];
    echo "Ref: " . $b['booking_ref'] . "\n";
    echo "Status: " . $b['status'] . "\n";
    echo "Total Price: " . $b['total_price'] . "\n";

    if ($b['status'] === 'PENDING' && !empty($b['booking_ref']) && $b['total_price'] == 1000) {
        echo "VERIFICATION PASSED.\n";
    } else {
        echo "VERIFICATION FAILED: Data check.\n";
    }
} else {
    echo "FAILED: Booking creation.\n";
    print_r($bk['body']);
}

echo "\nDone.\n";
