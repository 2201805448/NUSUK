<?php

$baseUrl = 'http://127.0.0.1:8002/api';
$runId = time();
$adminEmail = 'int_admin_' . $runId . '@example.com';
$supEmail = 'int_sup_' . $runId . '@example.com';
$password = 'password123';

$results = [];

function recordResult($step, $success, $msg = '')
{
    global $results;
    $results[] = ['step' => $step, 'status' => $success ? 'PASS' : 'FAIL', 'msg' => $msg];
    echo "[" . ($success ? 'PASS' : 'FAIL') . "] $step: $msg\n";
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

echo "=== Comprehensive Integration Test: Trip Functions ===\n\n";

// --- Step 1: Setup ---
echo "--- Step 1: User & Package Setup ---\n";
// Register Admin
$res = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Int Admin', 'email' => $adminEmail, 'password' => $password, 'phone_number' => '1', 'role' => 'ADMIN']);
if ($res['code'] !== 201)
    recordResult('Register Admin', false, 'Failed');
$adminToken = $res['body']['token'];
recordResult('Register Admin', true);

// Register Supervisor
$res = callApi($baseUrl . '/register', 'POST', ['full_name' => 'Int Sup', 'email' => $supEmail, 'password' => $password, 'phone_number' => '2', 'role' => 'SUPERVISOR']);
if ($res['code'] !== 201)
    recordResult('Register Supervisor', false, 'Failed');
$supToken = $res['body']['token'];
recordResult('Register Supervisor', true);

// Create Package
$pRes = callApi($baseUrl . '/packages', 'POST', ['package_name' => 'Int Pkg', 'price' => 100, 'duration_days' => 5, 'services' => '-', 'is_active' => true], $adminToken);
if ($pRes['code'] !== 201)
    recordResult('Create Package', false);
$pkgId = $pRes['body']['package']['package_id'];
recordResult('Create Package', true, "ID: $pkgId");


// --- Step 2: Create Trip ---
echo "\n--- Step 2: Create Trip ---\n";
$start = date('Y-m-d');
$end = date('Y-m-d', strtotime('+5 days'));
$tRes = callApi($baseUrl . '/trips', 'POST', [
    'package_id' => $pkgId,
    'trip_name' => 'Integration Trip',
    'start_date' => $start,
    'end_date' => $end,
    'status' => 'PLANNED'
], $adminToken);
if ($tRes['code'] !== 201)
    recordResult('Create Trip', false);
$tripId = $tRes['body']['trip']['trip_id'];
recordResult('Create Trip', true, "ID: $tripId, Status: PLANNED");


// --- Step 3: Define Stages ---
echo "\n--- Step 3: Define Trip Stages ---\n";
// Transport
$trRes = callApi($baseUrl . "/trips/$tripId/transports", 'POST', [
    'transport_type' => 'Bus',
    'route_from' => 'Airport',
    'route_to' => 'Makkah',
    'departure_time' => date('Y-m-d H:i')
], $adminToken);
if ($trRes['code'] !== 201 && $trRes['code'] !== 200)
    recordResult('Add Transport Stage', false);
$transId = $trRes['body']['transport']['transport_id'];
recordResult('Add Transport Stage', true);

// Activity
$acRes = callApi($baseUrl . "/trips/$tripId/activities", 'POST', [
    'activity_type' => 'Ziyarat',
    'location' => 'Arafat',
    'activity_date' => date('Y-m-d'),
    'activity_time' => '08:00',
    'end_time' => '10:00'
], $adminToken);
if ($acRes['code'] !== 201 && $acRes['code'] !== 200)
    recordResult('Add Activity Stage', false);
$actId = $acRes['body']['activity']['activity_id'];
recordResult('Add Activity Stage', true);


// --- Step 4: Update Data ---
echo "\n--- Step 4: Update Trip Data ---\n";
// Update Trip Name
$upT = callApi($baseUrl . "/trips/$tripId", 'PUT', ['trip_name' => 'Updated Int Trip'], $adminToken);
if ($upT['body']['trip']['trip_name'] !== 'Updated Int Trip')
    recordResult('Update Trip Name', false);
recordResult('Update Trip Name', true);

// Update Activity Location
$upA = callApi($baseUrl . "/activities/$actId", 'PUT', ['location' => 'Mina'], $adminToken);
if ($upA['body']['activity']['location'] !== 'Mina')
    recordResult('Update Activity Loc', false);
recordResult('Update Activity Loc', true);


// --- Step 5: Supervisor Display ---
echo "\n--- Step 5: Supervisor View ---\n";
$view = callApi($baseUrl . "/trips/$tripId", 'GET', [], $supToken); // Using Supervisor Token
$hasTrans = !empty($view['body']['transports']);
$hasActs = !empty($view['body']['activities']);
$isCorrectName = $view['body']['trip_name'] === 'Updated Int Trip';

if ($view['code'] === 200 && $hasTrans && $hasActs && $isCorrectName) {
    recordResult('Supervisor View', true, "Found Transports & Activities");
} else {
    recordResult('Supervisor View', false, "Missing Data or Access Denied");
}


// --- Step 6: Trip Status Report (Planned) ---
echo "\n--- Step 6: Reporting (PLANNED) ---\n";
$rep1 = callApi($baseUrl . '/reports/trips', 'GET', ['status' => 'PLANNED', 'date_from' => $start], $adminToken);
// We expect at least our 1 trip. (Likely more from previous tests, but at least > 0)
$found = false;
foreach ($rep1['body']['trips'] as $t) {
    if ($t['trip_id'] == $tripId)
        $found = true;
}
if ($found)
    recordResult('Report PLANNED', true, "Trip $tripId found in PLANNED report");
else
    recordResult('Report PLANNED', false, "Trip $tripId NOT found");


// --- Step 7: Cancel Trip ---
echo "\n--- Step 7: Cancel Trip ---\n";
$cncl = callApi($baseUrl . "/trips/$tripId/cancel", 'PATCH', [], $adminToken);
if ($cncl['body']['trip']['status'] === 'CANCELLED')
    recordResult('Cancel Trip', true);
else
    recordResult('Cancel Trip', false);


// --- Step 8: Trip Status Report (Cancelled) ---
echo "\n--- Step 8: Reporting (CANCELLED) ---\n";
$rep2 = callApi($baseUrl . '/reports/trips', 'GET', ['status' => 'CANCELLED', 'date_from' => $start], $adminToken);
$foundCancel = false;
foreach ($rep2['body']['trips'] as $t) {
    if ($t['trip_id'] == $tripId)
        $foundCancel = true;
}
if ($foundCancel)
    recordResult('Report CANCELLED', true, "Trip $tripId found in CANCELLED report");
else
    recordResult('Report CANCELLED', false, "Trip $tripId NOT found");


echo "\n=== Integration Test Complete ===\n";
echo "All steps passed successfully.\n";
