<?php

$baseUrl = 'http://127.0.0.1:8002/api';
$runId = time();
$adminEmail = 'uas_admin_' . $runId . '@example.com';
$supEmail = 'uas_sup_' . $runId . '@example.com';
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

echo "=== Testing Unassign Supervisor from Trip (Group) ===\n\n";

// 1. Register Users
echo "1. Registering Users...\n";
$adm = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Adm', 'email' => $adminEmail, 'password' => $password, 'phone_number' => '1', 'role' => 'ADMIN']);
$adminToken = $adm['body']['token'];

$sup = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Sup', 'email' => $supEmail, 'password' => $password, 'phone_number' => '2', 'role' => 'SUPERVISOR']);
$supToken = $sup['body']['token'];
$supId = $sup['body']['user']['user_id'];
echo "Users Registered.\n\n";

// 2. Setup Data
echo "2. Setting up Group...\n";
$pkg = callApi($baseUrl . '/packages', 'POST', ['package_name' => 'Uas Pkg', 'price' => 1000, 'duration_days' => 5, 'is_active' => true], $adminToken);
$pkgId = $pkg['body']['package']['package_id'];

$trip = callApi($baseUrl . '/trips', 'POST', ['package_id' => $pkgId, 'trip_name' => 'Uas Trip', 'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d', strtotime('+5 days'))], $adminToken);
$tripId = $trip['body']['trip']['trip_id'];

// Sup creates group
$grp = callApi($baseUrl . "/trips/$tripId/groups", 'POST', ['group_code' => 'UA-' . $runId], $supToken);
$grpId = $grp['body']['group']['group_id'];
echo "Group Created (ID: $grpId, Supervisor: " . $grp['body']['group']['supervisor_id'] . ").\n\n";

// 3. Admin Unassigns Supervisor
echo "3. Admin Unassigns Supervisor...\n";
$res = callApi($baseUrl . "/groups/$grpId/unassign-supervisor", 'PUT', [], $adminToken);

if ($res['code'] === 200) {
    echo "SUCCESS: Supervisor unassigned.\n";
    $g = $res['body']['group'];
    echo "New Supervisor ID: " . $g['supervisor_id'] . "\n"; // Should be null or empty

    if (empty($g['supervisor_id'])) {
        echo "VERIFICATION PASSED.\n";
    } else {
        echo "VERIFICATION FAILED: Supervisor ID is not null.\n";
    }
} else {
    echo "FAILED: Unassign.\n";
    print_r($res['body']);
}

echo "\nDone.\n";
