<?php

$baseUrl = 'http://127.0.0.1:8002/api';
$runId = time();
$adminEmail = 'rmv_admin_' . $runId . '@example.com';
$supEmail = 'rmv_sup_' . $runId . '@example.com';
$pilEmail = 'rmv_pil_' . $runId . '@example.com';
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

echo "=== Testing Remove Pilgrim from Group ===\n\n";

// 1. Register Users
echo "1. Registering Users...\n";
$adm = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Adm', 'email' => $adminEmail, 'password' => $password, 'phone_number' => '1', 'role' => 'ADMIN']);
$adminToken = $adm['body']['token'];

$sup = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Sup', 'email' => $supEmail, 'password' => $password, 'phone_number' => '2', 'role' => 'SUPERVISOR']);
$supToken = $sup['body']['token'];

$pil = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Pil', 'email' => $pilEmail, 'password' => $password, 'phone_number' => '3', 'role' => 'PILGRIM']);
$pilId = $pil['body']['user']['user_id'];
echo "Users Registered.\n\n";

// 2. Setup Data
echo "2. Setting up Group...\n";
$pkg = callApi($baseUrl . '/packages', 'POST', ['package_name' => 'Rmv Pkg', 'price' => 1000, 'duration_days' => 5, 'is_active' => true], $adminToken);
$pkgId = $pkg['body']['package']['package_id'];

$trip = callApi($baseUrl . '/trips', 'POST', ['package_id' => $pkgId, 'trip_name' => 'Rmv Trip', 'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d', strtotime('+5 days'))], $adminToken);
$tripId = $trip['body']['trip']['trip_id'];

$grp = callApi($baseUrl . "/trips/$tripId/groups", 'POST', ['group_code' => 'R-' . $runId], $supToken);
$grpId = $grp['body']['group']['group_id'];
echo "Group Created (ID: $grpId).\n\n";

// 3. Add Pilgrim to Group
echo "3. Adding Pilgrim to Group...\n";
callApi($baseUrl . "/groups/$grpId/members", 'POST', ['user_id' => $pilId], $supToken);
echo "Pilgrim added.\n";

// 4. Remove Pilgrim
echo "4. Removing Pilgrim...\n";
$res = callApi($baseUrl . "/groups/$grpId/remove", 'POST', ['user_id' => $pilId], $supToken);

if ($res['code'] === 200) {
    echo "SUCCESS: Pilgrim removed.\n";

    // Verify status is REMOVED by checking details
    // Need to use admin or sup token to view Group Details.
    // However, show() loads members. Let's see if it returns REMOVED members or if I should filter?
    // Usually 'members' relation returns all unless filtered.

    // Let's check DB directly via API? 
    // Or just trust the 200 OK for now and maybe verifying via another API call if needed.
    // I will call Group Details to verify status.

    $g = callApi($baseUrl . "/groups/$grpId", 'GET', [], $supToken);
    $members = $g['body']['members'];
    $status = 'UNKNOWN';
    foreach ($members as $m) {
        if ($m['pilgrim']['user_id'] == $pilId) {
            $status = $m['member_status'];
        }
    }

    echo "Member Status: $status\n";

    if ($status === 'REMOVED') {
        echo "VERIFICATION PASSED.\n";
    } else {
        echo "VERIFICATION FAILED: Status mismatch ($status).\n";
    }

} else {
    echo "FAILED: Remove.\n";
    print_r($res['body']);
}

echo "\nDone.\n";
