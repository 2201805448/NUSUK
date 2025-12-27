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

echo "--- Test Start: Hotel Management via API ---\n";

// 1. Authenticate (Register or Login)
$adminEmail = 'admin_hotel_' . time() . '@example.com';
$password = 'password123';

echo "\n1. Registering new Admin...\n";
$authRes = http_post('/register', [
    'full_name' => 'Admin Hotel API',
    'email' => $adminEmail,
    'password' => $password,
    'phone_number' => '1234567890',
    'role' => 'ADMIN'
]);

if ($authRes['code'] == 201 || $authRes['code'] == 200) {
    echo "Registered successfully.\n";
    $token = $authRes['data']['token'];
} else {
    echo "Registration failed (" . $authRes['code'] . "), trying login...\n";
    // Fallback login (though with random email likely won't work, but creates robustness if I fix email)
    // Actually using unique email every time is safer for test.
    print_r($authRes['data']);
    exit(1);
}

// 2. Create Package
echo "\n2. Creating Package...\n";
$packageRes = http_post('/packages', [
    'package_name' => 'Hotel Test Package',
    'price' => 5000,
    'duration_days' => 10,
    'description' => 'Test',
    'is_active' => true
], $token);

if ($packageRes['code'] != 201) {
    echo "Failed to create package. Code: {$packageRes['code']}\n";
    print_r($packageRes['data']);
    exit(1);
}
$packageId = $packageRes['data']['package']['package_id'];
echo "Package Created: ID $packageId\n";

// 3. Create Trip
echo "\n3. Creating Trip...\n";
$tripRes = http_post('/trips', [
    'package_id' => $packageId,
    'trip_name' => 'Hotel Test Trip',
    'start_date' => date('Y-m-d', strtotime('+10 days')),
    'end_date' => date('Y-m-d', strtotime('+20 days')),
    'status' => 'PLANNED',
    'capacity' => 50
], $token);

if ($tripRes['code'] != 201) {
    echo "Failed to create trip. Code: {$tripRes['code']}\n";
    print_r($tripRes['data']);
    exit(1);
}
$tripId = $tripRes['data']['trip']['trip_id'];
echo "Trip Created: ID $tripId\n";

// 4. Add NEW Hotel to Trip
echo "\n4. Adding NEW Hotel to Trip...\n";
$hotelRes = http_post("/trips/$tripId/hotels", [
    'hotel_name' => 'Hilton Makkah API',
    'city' => 'Makkah',
    'room_type' => 'Standard',
    'capacity' => 200,
    'notes' => 'Near Haram'
], $token);

if ($hotelRes['code'] == 200) {
    echo "Hotel Added Successfully.\n";
    print_r($hotelRes['data']['accommodation']);
} else {
    echo "Failed to add hotel. Code: {$hotelRes['code']}\n";
    print_r($hotelRes['data']);
}

// 5. Verify Hotel is in Trip
echo "\n5. Verifying Hotel in Trip...\n";
$tripShow = http_get("/trips/$tripId", $token);
$hotels = $tripShow['data']['accommodations'];
if (count($hotels) > 0) {
    echo "SUCCESS: Found " . count($hotels) . " hotels.\n";
} else {
    echo "FAILURE: No hotels found in trip.\n";
}

// 6. Create Standalone Hotel
echo "\n6. Creating Standalone Hotel...\n";
$accRes = http_post('/accommodations', [
    'hotel_name' => 'Pullman Zamzam API',
    'city' => 'Madinah',
    'room_type' => 'Suite',
    'capacity' => 150
], $token);
$accId = $accRes['data']['accommodation']['accommodation_id'];
echo "Standalone Hotel Created: ID $accId\n";

// 7. Add Existing Hotel to Trip
echo "\n7. Linking Existing Hotel to Trip...\n";
$linkRes = http_post("/trips/$tripId/hotels", [
    'accommodation_id' => $accId
], $token);
if ($linkRes['code'] == 200) {
    echo "Linked Successfully.\n";
} else {
    echo "Failed to link. Code: {$linkRes['code']}\n";
    print_r($linkRes['data']);
}

// 8. Verify Both Hotels
echo "\n8. Final Verification...\n";
$tripShowFinal = http_get("/trips/$tripId", $token);
$finalHotels = $tripShowFinal['data']['accommodations'];
echo "Total Hotels: " . count($finalHotels) . "\n";
foreach ($finalHotels as $h) {
    echo "- {$h['hotel_name']} (ID: {$h['accommodation_id']})\n";
}

if (count($finalHotels) == 2) {
    echo "\nTEST PASSED!\n";
} else {
    echo "\nTEST FAILED: Expected 2 hotels.\n";
}
