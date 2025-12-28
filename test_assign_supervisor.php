<?php

$baseUrl = 'http://127.0.0.1:8002/api';
$runId = time();
$adminEmail = 'asn_admin_' . $runId . '@example.com';
$sup1Email = 'asn_sup1_' . $runId . '@example.com';
$sup2Email = 'asn_sup2_' . $runId . '@example.com';
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

echo "=== Testing Assign Supervisor to Group ===\n\n";

// 1. Register Users
echo "1. Registering Users...\n";
$adm = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Adm', 'email' => $adminEmail, 'password' => $password, 'phone_number' => '1', 'role' => 'ADMIN']);
$adminToken = $adm['body']['token'];

$sup1 = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Sup1', 'email' => $sup1Email, 'password' => $password, 'phone_number' => '2', 'role' => 'SUPERVISOR']);
$sup1Token = $sup1['body']['token'];
$sup1Id = $sup1['body']['user']['user_id'];

$sup2 = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Sup2', 'email' => $sup2Email, 'password' => $password, 'phone_number' => '3', 'role' => 'SUPERVISOR']);
$sup2Id = $sup2['body']['user']['user_id'];
echo "Users Registered.\n\n";

// 2. Setup Data
echo "2. Setting up Group (Assigned to Sup1)...\n";
$pkg = callApi($baseUrl . '/packages', 'POST', ['package_name' => 'Asn Pkg', 'price' => 1000, 'duration_days' => 5, 'is_active' => true], $adminToken);
$pkgId = $pkg['body']['package']['package_id'];

$trip = callApi($baseUrl . '/trips', 'POST', ['package_id' => $pkgId, 'trip_name' => 'Asn Trip', 'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d', strtotime('+5 days'))], $adminToken);
$tripId = $trip['body']['trip']['trip_id'];

// Sup1 creates group
$grp = callApi($baseUrl . "/trips/$tripId/groups", 'POST', ['group_code' => 'S1-' . $runId], $sup1Token);
$grpId = $grp['body']['group']['group_id'];
$initialSupId = $grp['body']['group']['supervisor_id'];
echo "Group Created (ID: $grpId, Supervisor: $initialSupId).\n\n";

// 3. Admin Assigns Sup2
echo "3. Admin Assigns Supervisor (Sup2 ID: $sup2Id)...\n";
$res = callApi($baseUrl . "/groups/$grpId/assign-supervisor", 'PUT', ['supervisor_id' => $sup2Id], $adminToken);

if ($res['code'] === 200) {
    echo "SUCCESS: Supervisor assigned.\n";
    $g = $res['body']['group'];
    echo "New Supervisor ID: " . $g['supervisor_id'] . "\n";

    if ($g['supervisor_id'] == $sup2Id) {
        echo "VERIFICATION PASSED.\n";
    } else {
        echo "VERIFICATION FAILED: ID mismatch.\n";
    }
} else {
    echo "FAILED: Assignment.\n";
    print_r($res['body']);
}

echo "\nDone.\n";
