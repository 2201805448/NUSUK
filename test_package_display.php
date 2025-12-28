<?php

$baseUrl = 'http://127.0.0.1:8002/api';
$runId = time();
$adminEmail = 'pkg_admin_' . $runId . '@example.com';
$pilgrimEmail = 'pilgrim_' . $runId . '@example.com';
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

echo "=== Testing Available Packages Display ===\n\n";

// 1. Register Users
echo "1. Registering Users...\n";
$adm = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Adm', 'email' => $adminEmail, 'password' => $password, 'phone_number' => '1', 'role' => 'ADMIN']);
$adminToken = $adm['body']['token'];

$pil = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Pil', 'email' => $pilgrimEmail, 'password' => $password, 'phone_number' => '2', 'role' => 'PILGRIM']);
$pilgrimToken = $pil['body']['token'];
echo "Users Registered.\n\n";

// 2. Setup Packages (Admin)
echo "2. Setting up Packages...\n";
callApi($baseUrl . '/packages', 'POST', ['package_name' => 'Active Pkg 1', 'price' => 100, 'duration_days' => 5, 'is_active' => true], $adminToken);
callApi($baseUrl . '/packages', 'POST', ['package_name' => 'Active Pkg 2', 'price' => 200, 'duration_days' => 10, 'is_active' => true], $adminToken);
callApi($baseUrl . '/packages', 'POST', ['package_name' => 'Inactive Pkg', 'price' => 300, 'duration_days' => 15, 'is_active' => false], $adminToken);
echo "Packages Created (2 Active, 1 Inactive).\n\n";

// 3. Pilgrim Views All Packages
echo "3. Pilgrim Views All Packages...\n";
$allRes = callApi($baseUrl . '/packages', 'GET', [], $pilgrimToken);
$allCount = count($allRes['body']);
echo "Total Packages Visible: $allCount (Expected >= 3)\n";
if ($allCount >= 3)
    echo "PASS\n";
else
    echo "FAIL\n";

// 4. Pilgrim Views Active Only
echo "\n4. Pilgrim Views Active Packages (Filter)...\n";
$activeRes = callApi($baseUrl . '/packages', 'GET', ['is_active' => '1'], $pilgrimToken);
$activeCount = count($activeRes['body']);

// Verify all returned are active
$allActive = true;
foreach ($activeRes['body'] as $p) {
    if (!$p['is_active'])
        $allActive = false;
}

echo "Active Packages Returned: $activeCount\n";
echo "All Returned are Active: " . ($allActive ? 'YES' : 'NO') . "\n";

if ($allActive && $activeCount >= 2)
    echo "PASS\n";
else
    echo "FAIL\n";

echo "\nDone.\n";
