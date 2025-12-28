<?php

$baseUrl = 'http://127.0.0.1:8002/api';
$runId = time();
$adminEmail = 'int_bk_admin_' . $runId . '@example.com';
$pilgrimEmail = 'int_bk_pil_' . $runId . '@example.com';
$password = 'password123';

$results = [];

function recordResult($step, $success, $msg = '')
{
    global $results;
    $status = $success ? 'PASS' : 'FAIL';
    echo "[$status] $step: $msg\n";
    if (!$success)
        exit(1);
}

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
    if ($method !== 'GET' && !empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'GET' && !empty($data)) {
        $url .= '?' . http_build_query($data);
    }
    curl_setopt($ch, CURLOPT_URL, $url);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

echo "=== Integration Test: Pilgrim Booking Lifecycle ===\n\n";

// --- Step 1: Setup Users ---
echo "--- Step 1: Setup Users ---\n";
// Admin
$adm = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Adm', 'email' => $adminEmail, 'password' => $password, 'phone_number' => '1', 'role' => 'ADMIN']);
$adminToken = $adm['body']['token'];
recordResult('Register Admin', $adm['code'] === 201);

// Pilgrim
$pil = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Pil', 'email' => $pilgrimEmail, 'password' => $password, 'phone_number' => '2', 'role' => 'PILGRIM']);
$pilgrimToken = $pil['body']['token'];
recordResult('Register Pilgrim', $pil['code'] === 201);


// --- Step 2: Setup Data (Package & Trip) ---
echo "\n--- Step 2: Setup Package & Trip ---\n";
$pkg = callApi($baseUrl . '/packages', 'POST', ['package_name' => 'Int Pkg', 'price' => 500, 'duration_days' => 5, 'is_active' => true], $adminToken);
$pkgId = $pkg['body']['package']['package_id'];
recordResult('Create Package', $pkg['code'] === 201, "ID: $pkgId");

$trip = callApi($baseUrl . '/trips', 'POST', ['package_id' => $pkgId, 'trip_name' => 'Int Trip', 'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d'), 'status' => 'PLANNED'], $adminToken);
$tripId = $trip['body']['trip']['trip_id'];
recordResult('Create Trip', $trip['code'] === 201, "ID: $tripId");


// --- Step 3: Available Packages Display ---
echo "\n--- Step 3: View Packages ---\n";
$pkgs = callApi($baseUrl . '/packages', 'GET', ['is_active' => 1], $pilgrimToken);
$found = false;
foreach ($pkgs['body'] as $p) {
    if ($p['package_id'] == $pkgId)
        $found = true;
}
recordResult('View Packages', $found, "Found created package in list");


// --- Step 4: Package Details Display ---
echo "\n--- Step 4: View Package Details ---\n";
$detail = callApi($baseUrl . "/packages/$pkgId", 'GET', [], $pilgrimToken);
recordResult('View Details', $detail['body']['package_name'] === 'Int Pkg');


// --- Step 5: Execute Booking ---
echo "\n--- Step 5: Execute Booking ---\n";
$bk = callApi($baseUrl . '/bookings', 'POST', ['trip_id' => $tripId], $pilgrimToken);
$bkId = $bk['body']['booking']['booking_id'];
recordResult('Execute Booking', $bk['code'] === 201, "Booking ID: $bkId, Status: " . $bk['body']['booking']['status']);


// --- Step 6: Modify Booking ---
echo "\n--- Step 6: Request Modification ---\n";
$mod = callApi($baseUrl . "/bookings/$bkId/request-modification", 'POST', [
    'request_type' => 'CHANGE_DATE',
    'request_data' => ['new_date' => '2025-01-01']
], $pilgrimToken);
recordResult('Request Modification', $mod['body']['modification']['status'] === 'PENDING', "Type: " . $mod['body']['modification']['request_type']);


// --- Step 7: Cancel Booking ---
echo "\n--- Step 7: Request Cancellation ---\n";
$cancel = callApi($baseUrl . "/bookings/$bkId/request-cancellation", 'POST', ['reason' => 'Test'], $pilgrimToken);
recordResult('Request Cancellation', $cancel['body']['modification']['request_type'] === 'CANCELLATION', "Status: " . $cancel['body']['modification']['status']);


echo "\n=== Integration Test Complete ===\n";
echo "All steps passed successfully.\n";
