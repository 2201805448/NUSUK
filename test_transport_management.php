<?php

$baseUrl = 'http://127.0.0.1:8000/api';
$token = null;

// Helper Functions
function http_post($url, $data, $token = null)
{
    global $baseUrl;
    $ch = curl_init($baseUrl . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    if ($token)
        $headers[] = "Authorization: Bearer $token";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'data' => json_decode($response, true)];
}

function http_get($url, $token = null)
{
    global $baseUrl;
    $ch = curl_init($baseUrl . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = ['Accept: application/json'];
    if ($token)
        $headers[] = "Authorization: Bearer $token";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'data' => json_decode($response, true)];
}

function http_delete($url, $token = null)
{
    global $baseUrl;
    $ch = curl_init($baseUrl . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    $headers = ['Accept: application/json'];
    if ($token)
        $headers[] = "Authorization: Bearer $token";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'data' => json_decode($response, true)];
}

echo "--- Test Start: Transport Management ---\n";

// 1. Authenticate
$adminEmail = 'admin_trans_' . time() . '@example.com';
$authRes = http_post('/register', [
    'full_name' => 'Admin Transport',
    'email' => $adminEmail,
    'password' => 'password123',
    'phone_number' => '1112223333',
    'role' => 'ADMIN'
]);
if ($authRes['code'] == 201) {
    $token = $authRes['data']['token'];
    echo "1. Registered Admin ($adminEmail)\n";
} else {
    echo "FAILED: Register\n";
    print_r($authRes['data']);
    exit(1);
}

// 2. Create Package & Trip
$pkgRes = http_post('/packages', [
    'package_name' => 'Transport Test Pkg',
    'price' => 1000,
    'duration_days' => 5,
    'is_active' => true
], $token);
$packageId = $pkgRes['data']['package']['package_id'];

$tripRes = http_post('/trips', [
    'package_id' => $packageId,
    'trip_name' => 'Transport Trip',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+5 days')),
    'status' => 'PLANNED'
], $token);
$tripId = $tripRes['data']['trip']['trip_id'];
echo "2. Created Trip: ID $tripId\n";

// 3. Add Transport
echo "\n3. Adding Transports...\n";
$trans1 = http_post('/transports', [
    'trip_id' => $tripId,
    'transport_type' => 'Bus',
    'route_from' => 'Jeddah Airport',
    'route_to' => 'Makkah Hotel',
    'departure_time' => date('Y-m-d H:i:s'),
    'notes' => 'VIP Bus'
], $token);

$trans2 = http_post('/transports', [
    'trip_id' => $tripId,
    'transport_type' => 'Train',
    'route_from' => 'Makkah',
    'route_to' => 'Madinah',
    'departure_time' => date('Y-m-d H:i:s', strtotime('+2 days')),
    'notes' => 'Haramain Train'
], $token);

if ($trans1['code'] == 201 && $trans2['code'] == 201) {
    echo "Transports Added Successfully.\n";
    // Check if the key is 'id' or 'transport_id' based on the model return
    // The model has protected $primaryKey = 'transport_id'; 
    // However, Laravel's toArray() usually includes the PK.
    // Let's print to be sure in case of debug, but safe bet is transport_id
    $transId1 = $trans1['data']['transport']['transport_id'];
} else {
    echo "FAILED: Add Transport\n";
    print_r($trans1['data']);
    exit(1);
}

// 3.5 Update Transport
echo "\n3.5. Updating Transport ID $transId1...\n";
$updateRes = http_post("/transports/$transId1?_method=PUT", [
    'notes' => 'Updated Notes VIP',
    'departure_time' => date('Y-m-d H:i:s', strtotime('+5 hours'))
], $token);

if ($updateRes['code'] == 200) {
    echo "Transport Updated Successfully.\n";
} else {
    echo "FAILED: Update Transport\n";
    print_r($updateRes['data']);
    exit(1);
}

// 3.6 View Details (Show)
echo "\n3.6. Viewing Transport Details ID $transId1...\n";
$showRes = http_get("/transports/$transId1", $token);
if ($showRes['code'] == 200 && $showRes['data']['notes'] === 'Updated Notes VIP') {
    echo "View Details Successful. Notes updated correctly.\n";
} else {
    echo "FAILED: View Details\n";
    print_r($showRes['data']);
    exit(1);
}

// 4. List Transports
echo "\n4. Listing Transports for Trip...\n";
$listRes = http_get("/transports?trip_id=$tripId", $token);
$count = count($listRes['data']);
echo "Found $count transports.\n";
if ($count !== 2) {
    echo "FAILED: Expected 2\n";
    exit(1);
}

// 5. Delete Transport
echo "\n5. Deleting first transport...\n";
$delRes = http_delete("/transports/$transId1", $token);
if ($delRes['code'] == 200) {
    echo "Deleted Successfully.\n";
} else {
    echo "FAILED: Delete\n";
    print_r($delRes['data']);
}

// 6. Verify Deletion
$listFinal = http_get("/transports?trip_id=$tripId", $token);
if (count($listFinal['data']) == 1) {
    echo "\nTEST PASSED! 1 transport remaining.\n";
} else {
    echo "\nFAILED: Final count wrong.\n";
}
