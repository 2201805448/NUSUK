<?php
/**
 * Test Script: Link Accommodation to Groups
 * Tests the feature allowing admin to link accommodations to groups
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Trip;
use App\Models\Accommodation;
use App\Models\GroupTrip;
use App\Models\Package;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "=== Testing Link Accommodation to Groups Feature ===\n\n";

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
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
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
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/login');
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
    if ($response === false) {
        echo "CURL Error: " . curl_error($ch) . "\n";
    }
    curl_close($ch);

    return json_decode($response, true);
}

// Setup test data
echo "--- Setting up test data ---\n";

// Delete existing admin to ensure fresh start
User::where('email', 'admin_grp_acc@nusuk.com')->delete();

// Create Admin user
$adminUser = User::create([
    'email' => 'admin_grp_acc@nusuk.com',
    'full_name' => 'Group Accommodation Admin',
    'phone_number' => '0500000100',
    'password' => 'password123', // Model cast will hash this
    'role' => 'ADMIN',
    'account_status' => 'ACTIVE'
]);
echo "Admin: {$adminUser->full_name}\n";


// Login as admin
$loginResponse = login('admin_grp_acc@nusuk.com', 'password123');
if (!isset($loginResponse['token'])) {
    echo "✗ Login failed\n";
    print_r($loginResponse);
    exit;
}

// Find or create package and trip
$package = Package::first();
$trip = Trip::firstOrCreate(
    ['trip_name' => 'Group Accommodation Test Trip'],
    [
        'package_id' => $package->package_id,
        'start_date' => now()->addDays(10),
        'end_date' => now()->addDays(20),
        'status' => 'PLANNED'
    ]
);
echo "Trip: {$trip->trip_name} (ID: {$trip->trip_id})\n";

// Create accommodation
$hotel = Accommodation::firstOrCreate(
    ['hotel_name' => 'Group Accommodation Test Hotel'],
    [
        'city' => 'Mecca',
        'room_type' => 'Quad',
        'capacity' => 4,
        'notes' => 'Test hotel for group linking'
    ]
);
echo "Hotel: {$hotel->hotel_name} (ID: {$hotel->accommodation_id})\n";

// Link accommodation to trip first
try {
    $trip->accommodations()->syncWithoutDetaching([$hotel->accommodation_id]);
    echo "Linked hotel to trip\n";
} catch (\Exception $e) {
    echo "Hotel may already be linked to trip\n";
}

// Create groups
$groups = [];
for ($i = 1; $i <= 3; $i++) {
    $group = GroupTrip::firstOrCreate(
        ['group_code' => "GRP-ACC-TEST-{$i}"],
        [
            'trip_id' => $trip->trip_id,
            'group_status' => 'ACTIVE'
        ]
    );
    $groups[] = $group;
    echo "Group: {$group->group_code} (ID: {$group->group_id})\n";
}

// Clear existing links for testing
DB::table('group_accommodations')
    ->whereIn('group_id', array_map(fn($g) => $g->group_id, $groups))
    ->delete();

echo "\n--- Starting API Tests ---\n\n";

// Login as admin
$loginResponse = login('admin_grp_acc@nusuk.com', 'password123');
if (!isset($loginResponse['token'])) {
    echo "✗ Login failed\n";
    exit;
}
$token = $loginResponse['token'];
echo "✓ Logged in as Admin\n";

// TEST 1: Link accommodation to a single group
echo "\nTEST 1: Link accommodation to a group\n";
$result = makeRequest('POST', "/groups/{$groups[0]->group_id}/accommodations", $token, [
    'accommodation_id' => $hotel->accommodation_id,
    'check_in_date' => now()->addDays(10)->format('Y-m-d'),
    'check_out_date' => now()->addDays(15)->format('Y-m-d'),
    'notes' => 'Group 1 accommodation assignment'
]);
echo "  Response code: {$result['code']}\n";
if ($result['code'] === 201) {
    echo "  ✓ SUCCESS: Linked accommodation to group\n";
    echo "  Hotel: {$result['body']['accommodation']['hotel_name']}\n";
} else {
    echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
}

// TEST 2: View group accommodations
echo "\nTEST 2: View group accommodations\n";
$result = makeRequest('GET', "/groups/{$groups[0]->group_id}/accommodations", $token);
echo "  Response code: {$result['code']}\n";
if ($result['code'] === 200) {
    echo "  ✓ SUCCESS: Retrieved group accommodations\n";
    echo "  Group: {$result['body']['group']['group_code']}\n";
    echo "  Accommodations count: {$result['body']['accommodations_count']}\n";
    if (!empty($result['body']['accommodations'])) {
        foreach ($result['body']['accommodations'] as $acc) {
            echo "    - {$acc['hotel_name']} ({$acc['city']})\n";
            echo "      Check-in: {$acc['assignment']['check_in_date']}, Check-out: {$acc['assignment']['check_out_date']}\n";
        }
    }
}

// TEST 3: Update accommodation assignment
echo "\nTEST 3: Update accommodation assignment\n";
$result = makeRequest('PUT', "/groups/{$groups[0]->group_id}/accommodations/{$hotel->accommodation_id}", $token, [
    'check_in_date' => now()->addDays(11)->format('Y-m-d'),
    'check_out_date' => now()->addDays(16)->format('Y-m-d'),
    'notes' => 'Updated dates for Group 1'
]);
echo "  Response code: {$result['code']}\n";
if ($result['code'] === 200) {
    echo "  ✓ SUCCESS: Updated accommodation assignment\n";
} else {
    echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
}

// TEST 4: Bulk link to multiple groups
echo "\nTEST 4: Bulk link accommodation to multiple groups\n";
$result = makeRequest('POST', '/group-accommodations/bulk-link', $token, [
    'accommodation_id' => $hotel->accommodation_id,
    'group_ids' => [$groups[1]->group_id, $groups[2]->group_id],
    'check_in_date' => now()->addDays(10)->format('Y-m-d'),
    'check_out_date' => now()->addDays(15)->format('Y-m-d'),
]);
echo "  Response code: {$result['code']}\n";
if ($result['code'] === 200) {
    echo "  ✓ SUCCESS: Bulk link operation completed\n";
    echo "  Linked: {$result['body']['linked_count']} groups\n";
    echo "  Failed: {$result['body']['failed_count']} groups\n";
}

// TEST 5: Prevent duplicate link
echo "\nTEST 5: Prevent duplicate link\n";
$result = makeRequest('POST', "/groups/{$groups[0]->group_id}/accommodations", $token, [
    'accommodation_id' => $hotel->accommodation_id,
]);
echo "  Response code: {$result['code']}\n";
if ($result['code'] === 400) {
    echo "  ✓ SUCCESS: Correctly prevented duplicate link\n";
}

// TEST 6: Unlink accommodation
echo "\nTEST 6: Unlink accommodation from group\n";
$result = makeRequest('DELETE', "/groups/{$groups[0]->group_id}/accommodations/{$hotel->accommodation_id}", $token);
echo "  Response code: {$result['code']}\n";
if ($result['code'] === 200) {
    echo "  ✓ SUCCESS: Unlinked accommodation\n";
}

echo "\n=== Tests Completed ===\n";
