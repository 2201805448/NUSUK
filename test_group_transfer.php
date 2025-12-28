<?php

$baseUrl = 'http://127.0.0.1:8002/api';
$runId = time();
$adminEmail = 'trf_admin_' . $runId . '@example.com';
$supEmail = 'trf_sup_' . $runId . '@example.com';
$pilEmail = 'trf_pil_' . $runId . '@example.com';
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

echo "=== Testing Transfer Pilgrim Between Groups ===\n\n";

// 1. Register Users
echo "1. Registering Users...\n";
$adm = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Adm', 'email' => $adminEmail, 'password' => $password, 'phone_number' => '1', 'role' => 'ADMIN']);
$adminToken = $adm['body']['token'];

$sup = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Sup', 'email' => $supEmail, 'password' => $password, 'phone_number' => '2', 'role' => 'SUPERVISOR']);
$supToken = $sup['body']['token'];

$pil = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Pil', 'email' => $pilEmail, 'password' => $password, 'phone_number' => '3', 'role' => 'PILGRIM']);
$pilId = $pil['body']['user']['user_id'];
echo "Users Registered.\n\n";

// 2. Setup Data (Package, Trip, Group A, Group B)
echo "2. Setting up Groups...\n";
$pkg = callApi($baseUrl . '/packages', 'POST', ['package_name' => 'Trf Pkg', 'price' => 1000, 'duration_days' => 5, 'is_active' => true], $adminToken);
$pkgId = $pkg['body']['package']['package_id'];

$trip = callApi($baseUrl . '/trips', 'POST', ['package_id' => $pkgId, 'trip_name' => 'Trf Trip', 'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d', strtotime('+5 days'))], $adminToken);
$tripId = $trip['body']['trip']['trip_id'];

// Group A
$grpA = callApi($baseUrl . "/trips/$tripId/groups", 'POST', ['group_code' => 'A-' . $runId], $supToken);
$grpAId = $grpA['body']['group']['group_id'];

// Group B
$grpB = callApi($baseUrl . "/trips/$tripId/groups", 'POST', ['group_code' => 'B-' . $runId], $supToken);
$grpBId = $grpB['body']['group']['group_id'];
echo "Groups Created (A: $grpAId, B: $grpBId).\n\n";

// 3. Add Pilgrim to Group A
echo "3. Adding Pilgrim to Group A...\n";
callApi($baseUrl . "/groups/$grpAId/members", 'POST', ['user_id' => $pilId], $supToken);
echo "Pilgrim added to A.\n";

// 4. Transfer to Group B
echo "4. Transferring Pilgrim to Group B...\n";
$res = callApi($baseUrl . "/groups/$grpAId/transfer", 'POST', ['target_group_id' => $grpBId, 'user_id' => $pilId], $supToken);

if ($res['code'] === 200) {
    echo "SUCCESS: Transfer executed.\n";
    $m = $res['body']['member'];
    echo "New Group ID: " . $m['group_id'] . "\n";

    if ($m['group_id'] == $grpBId) {
        echo "VERIFICATION PASSED.\n";
    } else {
        echo "VERIFICATION FAILED: Group mismatch.\n";
    }
} else {
    echo "FAILED: Transfer.\n";
    print_r($res['body']);
}

echo "\nDone.\n";
