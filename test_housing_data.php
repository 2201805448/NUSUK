<?php
/**
 * Test Script: View Housing/Accommodation Data
 * Tests the feature allowing pilgrims to view housing data with group association
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Pilgrim;
use App\Models\Trip;
use App\Models\Accommodation;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\GroupTrip;
use App\Models\GroupMember;
use App\Models\Package;
use Illuminate\Support\Facades\Hash;

echo "=== Testing View Housing/Accommodation Data Feature ===\n\n";

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
    curl_close($ch);

    return ['code' => $httpCode, 'body' => json_decode($response, true)];
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
    curl_close($ch);

    return json_decode($response, true);
}

// Setup test data
echo "--- Setting up test data ---\n";

// Create Supervisor user
$supervisorUser = User::firstOrCreate(
    ['email' => 'supervisor_housing_test@nusuk.com'],
    [
        'full_name' => 'Housing Test Supervisor',
        'phone_number' => '0500000050',
        'password' => Hash::make('password123'),
        'role' => 'SUPERVISOR',
        'account_status' => 'ACTIVE'
    ]
);
echo "Supervisor: {$supervisorUser->full_name} (ID: {$supervisorUser->user_id})\n";

// Create Pilgrim user
$pilgrimUser = User::firstOrCreate(
    ['email' => 'pilgrim_housing_test@nusuk.com'],
    [
        'full_name' => 'Housing Test Pilgrim',
        'phone_number' => '0500000051',
        'password' => Hash::make('password123'),
        'role' => 'PILGRIM',
        'account_status' => 'ACTIVE'
    ]
);
echo "Pilgrim: {$pilgrimUser->full_name} (ID: {$pilgrimUser->user_id})\n";

// Create Pilgrim profile
$pilgrim = Pilgrim::firstOrCreate(
    ['user_id' => $pilgrimUser->user_id],
    [
        'passport_name' => 'HOUSING TEST PILGRIM',
        'passport_number' => 'HSG123456',
        'nationality' => 'Moroccan',
        'date_of_birth' => '1995-04-20',
        'gender' => 'MALE'
    ]
);
echo "Pilgrim profile created (ID: {$pilgrim->pilgrim_id})\n";

// Find or create package
$package = Package::first();
if (!$package) {
    $package = Package::create([
        'package_name' => 'Housing Test Package',
        'duration_days' => 14,
        'price' => 8000.00
    ]);
}

// Create a trip
$trip = Trip::firstOrCreate(
    ['trip_name' => 'Housing Test Trip'],
    [
        'package_id' => $package->package_id,
        'start_date' => now()->subDays(3),
        'end_date' => now()->addDays(11),
        'status' => 'ACTIVE'
    ]
);
echo "Trip: {$trip->trip_name} (ID: {$trip->trip_id})\n";

// Create accommodation
$hotel = Accommodation::firstOrCreate(
    ['hotel_name' => 'Housing Test Hotel'],
    [
        'city' => 'Mecca',
        'room_type' => 'Triple',
        'capacity' => 3,
        'notes' => '5 minutes walk to Masjid al-Haram'
    ]
);
echo "Hotel: {$hotel->hotel_name} (ID: {$hotel->accommodation_id})\n";

// Link accommodation to trip
try {
    $trip->accommodations()->syncWithoutDetaching([$hotel->accommodation_id]);
    echo "Linked hotel to trip\n";
} catch (\Exception $e) {
    echo "Hotel may already be linked\n";
}

// Create room
$room = Room::firstOrCreate(
    ['accommodation_id' => $hotel->accommodation_id, 'room_number' => '303'],
    [
        'floor' => 3,
        'room_type' => 'Triple',
        'status' => 'OCCUPIED'
    ]
);
echo "Room: #{$room->room_number}, Floor {$room->floor}\n";

// Create group with supervisor
$group = GroupTrip::firstOrCreate(
    ['group_code' => 'HOUSING-TEST-GRP'],
    [
        'trip_id' => $trip->trip_id,
        'supervisor_id' => $supervisorUser->user_id,
        'group_status' => 'ACTIVE'
    ]
);
// Ensure supervisor is set
$group->update(['supervisor_id' => $supervisorUser->user_id]);
echo "Group: {$group->group_code} with supervisor {$supervisorUser->full_name}\n";

// Add pilgrim to group
GroupMember::updateOrCreate(
    ['group_id' => $group->group_id, 'pilgrim_id' => $pilgrim->pilgrim_id],
    ['join_date' => now()->subDays(5), 'member_status' => 'ACTIVE']
);
echo "Added pilgrim to group\n";

// Create room assignment (current)
$assignment = RoomAssignment::updateOrCreate(
    ['pilgrim_id' => $pilgrim->pilgrim_id, 'accommodation_id' => $hotel->accommodation_id],
    [
        'room_id' => $room->id,
        'check_in' => now()->subDays(3),
        'check_out' => now()->addDays(4),
        'status' => 'CONFIRMED'
    ]
);
echo "Room assignment: Check-in {$assignment->check_in}, Check-out {$assignment->check_out}\n";

echo "\n--- Starting API Tests ---\n\n";

// Login as pilgrim
echo "TEST 1: View housing data with group information\n";
$loginResponse = login('pilgrim_housing_test@nusuk.com', 'password123');
if (isset($loginResponse['token'])) {
    $token = $loginResponse['token'];
    echo "✓ Logged in successfully\n";

    $result = makeRequest('GET', "/trips/{$trip->trip_id}/my-housing", $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved housing data\n";

        // Trip info
        echo "\n  Trip Information:\n";
        echo "    - Name: {$result['body']['trip']['trip_name']}\n";
        echo "    - Status: {$result['body']['trip']['trip_status']}\n";

        // Group info
        echo "\n  Group Information:\n";
        echo "    - Code: {$result['body']['group']['group_code']}\n";
        echo "    - Status: {$result['body']['group']['group_status']}\n";
        if ($result['body']['group']['supervisor']) {
            echo "    - Supervisor: {$result['body']['group']['supervisor']['name']}\n";
            echo "    - Supervisor Phone: {$result['body']['group']['supervisor']['phone']}\n";
        }
        echo "    - Member Status: {$result['body']['group']['member_status']}\n";

        // Housing summary
        echo "\n  Housing Summary:\n";
        echo "    - Total Assignments: {$result['body']['housing_summary']['total_assignments']}\n";
        echo "    - Current: {$result['body']['housing_summary']['current']}\n";
        echo "    - Upcoming: {$result['body']['housing_summary']['upcoming']}\n";
        echo "    - Past: {$result['body']['housing_summary']['past']}\n";

        // Current housing
        if ($result['body']['current_housing']) {
            $curr = $result['body']['current_housing'];
            echo "\n  Current Housing:\n";
            echo "    - Place: {$curr['place_of_residence']['hotel_name']} ({$curr['place_of_residence']['city']})\n";
            echo "    - Room: #{$curr['room']['room_number']}, Floor {$curr['room']['floor']}\n";
            echo "    - Check-in: {$curr['dates']['check_in_formatted']}\n";
            echo "    - Check-out: {$curr['dates']['check_out_formatted']}\n";
            echo "    - Duration: {$curr['dates']['duration_nights']} nights\n";
        }
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
} else {
    echo "✗ Login failed\n";
    print_r($loginResponse);
}

// Test 2: Compare with my-accommodations endpoint
echo "\nTEST 2: Compare with my-accommodations for trip\n";
if (isset($token)) {
    $result = makeRequest('GET', "/trips/{$trip->trip_id}/my-accommodations", $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved accommodations\n";
        echo "  Note: my-housing includes group info, my-accommodations focuses on room details\n";
    }
}

// Test 3: Unauthorized access
echo "\nTEST 3: Attempt unauthorized access to another trip\n";
if (isset($token)) {
    $result = makeRequest('GET', '/trips/999999/my-housing', $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 403) {
        echo "  ✓ SUCCESS: Correctly denied access\n";
    } else {
        echo "  Response: " . json_encode($result['body']) . "\n";
    }
}

echo "\n=== Tests Completed ===\n";
