<?php
/**
 * Test script for Pilgrim Accommodation endpoints
 * Tests all 4 endpoints: index, current, forTrip, housing
 */

$baseUrl = 'http://127.0.0.1:8000/api';

// Get token - first login as a pilgrim user
echo "=== Testing Pilgrim Accommodation Endpoints ===\n\n";

// Step 1: Login as a user to get token
echo "1. Logging in as user...\n";

$loginData = [
    'email' => 'pilgrim@test.com',  // Change to a valid pilgrim user email
    'password' => 'password'         // Change to valid password
];

$ch = curl_init("$baseUrl/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
$loginResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login Response (HTTP $httpCode): $loginResponse\n\n";

$loginResult = json_decode($loginResponse, true);
$token = $loginResult['token'] ?? null;

if (!$token) {
    echo "ERROR: Could not get authentication token. Check credentials.\n";
    echo "Trying with admin user to find a pilgrim...\n\n";

    // Try with admin user
    $loginData = [
        'email' => 'admin@nusuk.com',
        'password' => 'password'
    ];

    $ch = curl_init("$baseUrl/login");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
    $loginResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Admin Login Response (HTTP $httpCode): $loginResponse\n\n";

    $loginResult = json_decode($loginResponse, true);
    $token = $loginResult['token'] ?? null;

    if (!$token) {
        echo "ERROR: Could not login as admin either. Make sure the server is running.\n";
        exit(1);
    }
}

echo "Token obtained successfully!\n\n";

// Step 2: Test /my-accommodations endpoint
echo "2. Testing GET /my-accommodations...\n";

$ch = curl_init("$baseUrl/my-accommodations");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    "Authorization: Bearer $token"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response (HTTP $httpCode):\n";
$data = json_decode($response, true);
echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

if ($httpCode === 403) {
    echo "!! 403 Forbidden - User is not a pilgrim or lacks pilgrim profile\n";
}

// Step 3: Test /my-accommodations/current endpoint
echo "3. Testing GET /my-accommodations/current...\n";

$ch = curl_init("$baseUrl/my-accommodations/current");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    "Authorization: Bearer $token"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response (HTTP $httpCode):\n";
$data = json_decode($response, true);
echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

if ($httpCode === 403) {
    echo "!! 403 Forbidden - User is not a pilgrim or lacks pilgrim profile\n";
}

// Step 4: Test /trips/{trip_id}/my-accommodations endpoint
echo "4. Testing GET /trips/1/my-accommodations...\n";

$ch = curl_init("$baseUrl/trips/1/my-accommodations");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    "Authorization: Bearer $token"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response (HTTP $httpCode):\n";
$data = json_decode($response, true);
echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

if ($httpCode === 403) {
    echo "!! 403 Forbidden - User is not registered for this trip\n";
}

// Step 5: Test /trips/{trip_id}/my-housing endpoint
echo "5. Testing GET /trips/1/my-housing...\n";

$ch = curl_init("$baseUrl/trips/1/my-housing");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    "Authorization: Bearer $token"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response (HTTP $httpCode):\n";
$data = json_decode($response, true);
echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

if ($httpCode === 403) {
    echo "!! 403 Forbidden - User is not registered for this trip\n";
}

echo "=== Test Complete ===\n";
echo "\nNote: 403 errors occur when:\n";
echo "- User does not have a pilgrim profile (404 with 'Pilgrim profile not found')\n";
echo "- User is not registered for the specified trip (403 with 'You are not registered for this trip')\n";
