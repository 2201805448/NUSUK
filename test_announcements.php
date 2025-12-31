<?php
/**
 * Test Script: Create Announcement Feature
 * Tests creating announcements with different types, priorities, and validations.
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Announcement;
use App\Models\Trip;
use App\Models\Package;
use Illuminate\Support\Facades\Hash;

echo "=== Testing Create Announcement Feature ===\n\n";

// Helper function to make authenticated request
function makeRequest($method, $url, $token, $data = [])
{
    $baseUrl = 'http://localhost:8000/api';
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $baseUrl . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
        'Content-Type: application/json'
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($response === false) {
        echo "CURL Error: " . curl_error($ch) . "\n";
    }
    curl_close($ch);

    $body = json_decode($response, true);
    if ($httpCode >= 500) {
        echo "SERVER ERROR ($httpCode):\n";
        echo $response . "\n";
    }

    return ['code' => $httpCode, 'body' => $body];
}

// Helper function to login
function login($email, $password)
{
    $baseUrl = 'http://localhost:8000/api';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/login');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'email' => $email,
        'password' => $password
    ]));

    $response = curl_exec($ch);
    curl_close($ch);

    $decoded = json_decode($response, true);
    if ($decoded === null) {
        echo "Response raw: " . $response . "\n";
    }

    return $decoded;
}

// Setup test data
echo "--- Setting up test data ---\n";

// Delete existing admin to ensure fresh start
User::where('email', 'admin_anc@nusuk.com')->delete();

// Create Admin user
$adminUser = User::create([
    'email' => 'admin_anc@nusuk.com',
    'full_name' => 'Announcement Admin',
    'phone_number' => '0500000101',
    'password' => 'password123',
    'role' => 'ADMIN',
    'account_status' => 'ACTIVE'
]);
echo "Admin: {$adminUser->full_name}\n";

// Create Package and Trip for testing relations
$package = Package::firstOrCreate(
    ['package_name' => 'Announcement Test Package'],
    ['duration_days' => 5, 'price' => 500]
);
echo "Package Package ID: {$package->package_id}\n";

$trip = Trip::firstOrCreate(
    ['trip_name' => 'Announcement Test Trip'],
    [
        'package_id' => $package->package_id,
        'start_date' => now()->addDays(20),
        'end_date' => now()->addDays(25),
        'status' => 'PLANNED'
    ]
);
echo "Trip ID: {$trip->trip_id}\n";

echo "\n--- Starting API Tests ---\n\n";

// Login as admin
$loginResponse = login('admin_anc@nusuk.com', 'password123');
if (!isset($loginResponse['token'])) {
    echo "✗ Login failed\n";
    echo "Response: " . json_encode($loginResponse) . "\n";
    exit;
}
$token = $loginResponse['token'];
echo "✓ Logged in as Admin\n";

// TEST 1: Create GENERAL Announcement
echo "\nTEST 1: Create GENERAL Announcement (Normal Priority)\n";
$result = makeRequest('POST', '/announcements', $token, [
    'title' => 'General News',
    'content' => 'Welcome to our service.',
    'type' => 'GENERAL',
    'priority' => 'NORMAL'
]);
echo "  Response code: {$result['code']}\n";
if ($result['code'] === 201) {
    echo "  ✓ SUCCESS: Created GENERAL announcement\n";
    echo "    Type: {$result['body']['data']['type']}, Priority: {$result['body']['data']['priority']}\n";
} else {
    echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
}

// TEST 2: Create TRIP Announcement (Urgent)
echo "\nTEST 2: Create TRIP Announcement (Urgent)\n";
$result = makeRequest('POST', '/announcements', $token, [
    'title' => 'Trip Update',
    'content' => 'Urgent changes to the schedule.',
    'type' => 'TRIP',
    'related_id' => $trip->trip_id,
    'priority' => 'URGENT'
]);
echo "  Response code: {$result['code']}\n";
if ($result['code'] === 201) {
    echo "  ✓ SUCCESS: Created TRIP announcement\n";
    echo "    Related ID: {$result['body']['data']['related_id']}\n";
} else {
    echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
}

// TEST 3: Create PACKAGE Announcement (High)
echo "\nTEST 3: Create PACKAGE Announcement (High)\n";
$result = makeRequest('POST', '/announcements', $token, [
    'title' => 'New Package Offer',
    'content' => 'Check out this amazing package.',
    'type' => 'PACKAGE',
    'related_id' => $package->package_id,
    'priority' => 'HIGH'
]);
echo "  Response code: {$result['code']}\n";
if ($result['code'] === 201) {
    echo "  ✓ SUCCESS: Created PACKAGE announcement\n";
} else {
    echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
}

// TEST 4: Validation Fail - Missing related_id for TRIP
echo "\nTEST 4: Validation Fail - Missing related_id\n";
$result = makeRequest('POST', '/announcements', $token, [
    'title' => 'Invalid Trip Announce',
    'content' => 'Should fail.',
    'type' => 'TRIP',
    // related_id missing
]);
echo "  Response code: {$result['code']}\n";
if ($result['code'] === 422) {
    echo "  ✓ SUCCESS: Correctly rejected missing related_id\n";
} else {
    echo "  ✗ FAILED: Got code {$result['code']}, expected 422\n";
}

// TEST 5: Validation Fail - Invalid related_id
echo "\nTEST 5: Validation Fail - Invalid related_id\n";
$result = makeRequest('POST', '/announcements', $token, [
    'title' => 'Invalid ID Announce',
    'content' => 'Should fail.',
    'type' => 'TRIP',
    'related_id' => 999999
]);
echo "  Response code: {$result['code']}\n";
if ($result['code'] === 422) {
    echo "  ✓ SUCCESS: Correctly rejected invalid Trip ID\n";
} else {
    echo "  ✗ FAILED: Got code {$result['code']}, expected 422\n";
}

// TEST 6: List Announcements (Verify Sorting)
echo "\nTEST 6: List Announcements (Sort check)\n";
$result = makeRequest('GET', '/announcements', $token);
echo "  Response code: {$result['code']}\n";
if ($result['code'] === 200) {
    echo "  ✓ SUCCESS: Retrieved announcements\n";
    $list = $result['body'];
    if (count($list) > 0) {
        $first = $list[0];
        echo "  First item priority: {$first['priority']} (Expected URGENT)\n";
        echo "  First item title: {$first['title']}\n";
    }
}

// TEST 7: Edit Announcement (Update content and priority)
echo "\nTEST 7: Edit Announcement\n";
// Create a new announcement to edit
$createRes = makeRequest('POST', '/announcements', $token, [
    'title' => 'To Be Edited',
    'content' => 'Original Content',
    'type' => 'GENERAL',
    'priority' => 'NORMAL'
]);
$anncId = $createRes['body']['data']['announcement_id'];

// Edit it
$result = makeRequest('PUT', "/announcements/{$anncId}", $token, [
    'title' => 'Edited Title',
    'priority' => 'HIGH'
]);
echo "  Response code: {$result['code']}\n";
if ($result['code'] === 200) {
    echo "  ✓ SUCCESS: Edited announcement\n";
    echo "    New Title: {$result['body']['data']['title']}\n";
    echo "    New Priority: {$result['body']['data']['priority']}\n";
} else {
    echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
}

// TEST 8: Edit Announcement (Change Type invalid)
echo "\nTEST 8: Edit Announcement (Change Type invalid)\n";
$result = makeRequest('PUT', "/announcements/{$anncId}", $token, [
    'type' => 'TRIP',
    // Missing related_id
]);
echo "  Response code: {$result['code']}\n";
if ($result['code'] === 422) {
    echo "  ✓ SUCCESS: Correctly rejected type change without related_id\n";
} else {
    echo "  ✗ FAILED: Got code {$result['code']}, expected 422\n";
}

// TEST 9: View Announcement Details
echo "\nTEST 9: View Announcement Details\n";
// Create a TRIP announcement to verify related data fetching
$createTripAnnc = makeRequest('POST', '/announcements', $token, [
    'title' => 'Trip Details Announce',
    'content' => 'Details inside',
    'type' => 'TRIP',
    'related_id' => $trip->trip_id,
    'priority' => 'HIGH'
]);
$tripAnncId = $createTripAnnc['body']['data']['announcement_id'];

$result = makeRequest('GET', "/announcements/{$tripAnncId}", $token);
echo "  Response code: {$result['code']}\n";
if ($result['code'] === 200) {
    echo "  ✓ SUCCESS: Retrieved details\n";
    $data = $result['body']['data'];
    $related = $result['body']['related_data'];

    echo "    Title: {$data['title']}\n";
    if ($related) {
        echo "    Related Trip: " . ($related['trip_name'] ?? 'Unknown') . "\n";
    } else {
        echo "    ✗ FAILED: Related data missing\n";
    }
} else {
    echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
}

// TEST 10: Delete Announcement
echo "\nTEST 10: Delete Announcement\n";
// Use the trip announcement created in Test 9
$result = makeRequest('DELETE', "/announcements/{$tripAnncId}", $token);
echo "  Response code: {$result['code']}\n";
if ($result['code'] === 200) {
    echo "  ✓ SUCCESS: Deleted announcement\n";
    // Verify it's gone
    $check = makeRequest('GET', "/announcements/{$tripAnncId}", $token);
    if ($check['code'] === 404) {
        echo "  ✓ VERIFIED: Announcement 404 after delete\n";
    } else {
        echo "  ✗ FAILED: Still accessible after delete\n";
    }
} else {
    echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
}

echo "\n=== Tests Completed ===\n";
