<?php

$baseUrl = 'http://127.0.0.1:8002/api';
$run_id = time();
$adminEmail = 'report_admin_' . $run_id . '@example.com';
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
    if ($method === 'GET' && !empty($data)) {
        $url .= '?' . http_build_query($data);
    } elseif ($method !== 'GET' && !empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_URL, $url);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

echo "=== Testing Trip Status Reports ===\n\n";

// 1. Register Admin
$res = callApi($baseUrl . '/register', 'POST', [
    'full_name' => 'Rep Admin',
    'email' => $adminEmail,
    'password' => $password,
    'phone_number' => '121',
    'role' => 'ADMIN'
]);
if ($res['code'] !== 201)
    die("Admin Reg Failed");
$token = $res['body']['token'];
echo "Admin Registered.\n";

// 2. Setup Data
// Package
$pRes = callApi($baseUrl . '/packages', 'POST', ['package_name' => 'Rep Pkg', 'price' => 1, 'duration_days' => 1, 'services' => '-', 'is_active' => true], $token);
$pkgId = $pRes['body']['package']['package_id'];

// Create Trips
$today = date('Y-m-d');
$nextMonth = date('Y-m-d', strtotime('+1 month'));

// Trip 1: PLANNED, Today
callApi($baseUrl . '/trips', 'POST', ['package_id' => $pkgId, 'trip_name' => 'T1 Planned Today', 'start_date' => $today, 'end_date' => $today, 'status' => 'PLANNED'], $token);

// Trip 2: CANCELLED, Today
callApi($baseUrl . '/trips', 'POST', ['package_id' => $pkgId, 'trip_name' => 'T2 Cancelled Today', 'start_date' => $today, 'end_date' => $today, 'status' => 'CANCELLED'], $token);

// Trip 3: PLANNED, Next Month
callApi($baseUrl . '/trips', 'POST', ['package_id' => $pkgId, 'trip_name' => 'T3 Planned Next', 'start_date' => $nextMonth, 'end_date' => $nextMonth, 'status' => 'PLANNED'], $token);

echo "Data Setup: 2 Trips Today (1 P, 1 C), 1 Trip Next Month (P).\n\n";

// 3. Test Filter: Status = CANCELLED
echo "3. Testing Filter: Status = CANCELLED...\n";
$rep1 = callApi($baseUrl . '/reports/trips', 'GET', ['status' => 'CANCELLED'], $token);
echo "Count: " . $rep1['body']['count'] . " (Expected: >= 1)\n";
if ($rep1['body']['count'] >= 1)
    echo "PASS\n";
else
    echo "FAIL\n";

// 4. Test Filter: Date = Today (start_date range)
echo "\n4. Testing Filter: Date Range (Today)...\n";
$rep2 = callApi($baseUrl . '/reports/trips', 'GET', ['date_from' => $today, 'date_to' => $today], $token);
echo "Count: " . $rep2['body']['count'] . " (Expected: >= 2)\n"; // T1 and T2
if ($rep2['body']['count'] >= 2)
    echo "PASS\n";
else
    echo "FAIL\n";

// 5. Test Filter: Date + Status (Next Month + PLANNED)
echo "\n5. Testing Filter: Next Month + PLANNED...\n";
$rep3 = callApi($baseUrl . '/reports/trips', 'GET', ['status' => 'PLANNED', 'date_from' => $nextMonth], $token);
echo "Count: " . $rep3['body']['count'] . " (Expected: >= 1)\n";
if ($rep3['body']['count'] >= 1)
    echo "PASS\n";
else
    echo "FAIL\n";

echo "\nDone.\n";
