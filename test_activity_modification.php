<?php

$baseUrl = 'http://127.0.0.1:8000/api';
$token = null;

// Helper Functions
function http_post($url, $data, $token = null, $method = 'POST')
{
    global $baseUrl;
    $ch = curl_init($baseUrl . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    } else {
        curl_setopt($ch, CURLOPT_POST, true);
    }
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

echo "--- Test Start: Activity Modification ---\n";

// 1. Authenticate
$adminEmail = 'admin_actmod_' . time() . '@example.com';
$authRes = http_post('/register', [
    'full_name' => 'Admin Modifier',
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
    exit(1);
}

// 2. Setup (Package & Trip & Activity)
$pkgRes = http_post('/packages', ['package_name' => 'Pkg Mod', 'price' => 100, 'duration_days' => 5], $token);
$tripRes = http_post('/trips', ['package_id' => $pkgRes['data']['package']['package_id'], 'trip_name' => 'Trip Mod', 'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d', strtotime('+5 days'))], $token);
$tripId = $tripRes['data']['trip']['trip_id'];

$actRes = http_post("/trips/$tripId/activities", [
    'activity_type' => 'VISIT',
    'location' => 'Old Location',
    'activity_date' => date('Y-m-d'),
    'activity_time' => '10:00',
    'status' => 'SCHEDULED'
], $token);
$activityId = $actRes['data']['activity']['activity_id'];
echo "2. Created Activity ID $activityId at 'Old Location'\n";

// 3. Update Activity
echo "\n3. Updating Activity...\n";
$updateRes = http_post("/activities/$activityId", [
    'location' => 'New Modified Location',
    'activity_time' => '14:00'
], $token, 'PUT');

if ($updateRes['code'] == 200) {
    echo "Activity Updated Successfully.\n";
    if ($updateRes['data']['activity']['location'] === 'New Modified Location' && $updateRes['data']['activity']['activity_time'] === '14:00:00') {
        echo "Verification Passed: Location and Time updated.\n";
    } else {
        echo "Verification Failed: Attributes mismatch.\n";
        print_r($updateRes['data']);
    }
} else {
    echo "FAILED: Update Activity\n";
    print_r($updateRes['data']);
    exit(1);
}

// 4. Delete Activity
echo "\n4. Deleting Activity...\n";
$delRes = http_post("/activities/$activityId", [], $token, 'DELETE');

if ($delRes['code'] == 200) {
    echo "Activity Deleted Successfully.\n";

    // Verify it's gone
    $checkRes = http_get("/activities/$activityId", $token);
    if ($checkRes['code'] == 404) {
        echo "Verification Passed: Activity is gone (404).\n";
    } else {
        echo "Verification Failed: Activity still exists or incorrect error code.\n";
        print_r($checkRes['data']); // Might return 404 HTML if using standard Laravel handler, or JSON error.
    }
} else {
    echo "FAILED: Delete Activity\n";
    print_r($delRes['data']);
    exit(1);
}

// Helper to handle DELETE method simulation in http_post, need to ensure cURL handles it.
// The existing http_post function handles 'PUT', let's fix it to handling 'DELETE' too.
/**
 * Update http_post function to handle DELETE
 */
