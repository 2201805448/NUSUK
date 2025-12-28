<?php

$baseUrl = 'http://127.0.0.1:8002/api';
$runId = time();
$adminEmail = 'lst_admin_' . $runId . '@example.com';
$supEmail = 'lst_sup_' . $runId . '@example.com';
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

echo "=== Testing Display Assigned Supervisors List ===\n\n";

// 1. Register Users
echo "1. Registering Users...\n";
$adm = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Adm', 'email' => $adminEmail, 'password' => $password, 'phone_number' => '1', 'role' => 'ADMIN']);
$adminToken = $adm['body']['token'];

$sup = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Sup', 'email' => $supEmail, 'password' => $password, 'phone_number' => '2', 'role' => 'SUPERVISOR']);
$supToken = $sup['body']['token'];
$supId = $sup['body']['user']['user_id'];
echo "Users Registered.\n\n";

// 2. Setup Data
echo "2. Setting up Groups...\n";
$pkg = callApi($baseUrl . '/packages', 'POST', ['package_name' => 'Lst Pkg', 'price' => 1000, 'duration_days' => 5, 'is_active' => true], $adminToken);
$pkgId = $pkg['body']['package']['package_id'];

$trip = callApi($baseUrl . '/trips', 'POST', ['package_id' => $pkgId, 'trip_name' => 'Lst Trip', 'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d', strtotime('+5 days'))], $adminToken);
$tripId = $trip['body']['trip']['trip_id'];

// Create Group (Assigned to Sup)
$grp = callApi($baseUrl . "/trips/$tripId/groups", 'POST', ['group_code' => 'L-' . $runId], $supToken);
$grpId = $grp['body']['group']['group_id'];
echo "Group Created.\n\n";

// 3. List Groups for Trip (with Supervisors)
echo "3. Listing Groups for Trip (GET /trips/$tripId/groups)...\n";
$res = callApi($baseUrl . "/trips/$tripId/groups", 'GET', [], $adminToken);

if ($res['code'] === 200) {
    echo "SUCCESS: Groups retrieved.\n";
    $groups = $res['body'];
    $found = false;
    foreach ($groups as $g) {
        if ($g['group_id'] == $grpId) {
            $found = true;
            echo "Found Group ID: " . $g['group_id'] . "\n";
            echo "Supervisor: ";
            if (isset($g['supervisor'])) {
                echo $g['supervisor']['full_name'] . " (ID: " . $g['supervisor']['user_id'] . ")\n";
                if ($g['supervisor']['user_id'] == $supId) {
                    echo "VERIFICATION PASSED.\n";
                } else {
                    echo "VERIFICATION FAILED: Supervisor ID mismatch.\n";
                }
            } else {
                echo "MISSING (Eager load failed)\n";
                echo "VERIFICATION FAILED.\n";
            }
        }
    }
    if (!$found)
        echo "VERIFICATION FAILED: Group not found in list.\n";
} else {
    echo "FAILED: List.\n";
    print_r($res['body']);
}

echo "\nDone.\n";
