<?php
/**
 * Test Script: View Accommodation Details
 * Tests the feature allowing pilgrims to view their accommodation details
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

echo "=== Testing View Accommodation Details Feature ===\n\n";

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

// Create Pilgrim user
$pilgrimUser = User::firstOrCreate(
    ['email' => 'pilgrim_accom_test@nusuk.com'],
    [
        'full_name' => 'Accommodation Test Pilgrim',
        'phone_number' => '0500000040',
        'password' => Hash::make('password123'),
        'role' => 'PILGRIM',
        'account_status' => 'ACTIVE'
    ]
);
echo "Pilgrim user: {$pilgrimUser->email} (ID: {$pilgrimUser->user_id})\n";

// Create Pilgrim profile
$pilgrim = Pilgrim::firstOrCreate(
    ['user_id' => $pilgrimUser->user_id],
    [
        'passport_name' => 'ACCOMMODATION TEST PILGRIM',
        'passport_number' => 'ACC123456',
        'nationality' => 'Jordanian',
        'date_of_birth' => '1992-08-10',
        'gender' => 'FEMALE'
    ]
);
echo "Pilgrim profile created (ID: {$pilgrim->pilgrim_id})\n";

// Find or create package
$package = Package::first();
if (!$package) {
    $package = Package::create([
        'package_name' => 'Accommodation Test Package',
        'duration_days' => 10,
        'price' => 7000.00
    ]);
}

// Create a trip
$trip = Trip::firstOrCreate(
    ['trip_name' => 'Accommodation Test Trip'],
    [
        'package_id' => $package->package_id,
        'start_date' => now()->subDays(2),
        'end_date' => now()->addDays(8),
        'status' => 'ACTIVE'
    ]
);
echo "Trip: {$trip->trip_name} (ID: {$trip->trip_id})\n";

// Create accommodations
$hotel1 = Accommodation::firstOrCreate(
    ['hotel_name' => 'Mecca Grand Hotel'],
    [
        'city' => 'Mecca',
        'room_type' => 'Suite',
        'capacity' => 4,
        'notes' => 'Near Masjid al-Haram'
    ]
);
echo "Hotel 1: {$hotel1->hotel_name} (ID: {$hotel1->accommodation_id})\n";

$hotel2 = Accommodation::firstOrCreate(
    ['hotel_name' => 'Madinah Palace Hotel'],
    [
        'city' => 'Madinah',
        'room_type' => 'Double',
        'capacity' => 2,
        'notes' => 'Walking distance to Masjid an-Nabawi'
    ]
);
echo "Hotel 2: {$hotel2->hotel_name} (ID: {$hotel2->accommodation_id})\n";

// Link accommodations to trip
try {
    $trip->accommodations()->syncWithoutDetaching([$hotel1->accommodation_id, $hotel2->accommodation_id]);
    echo "Linked hotels to trip\n";
} catch (\Exception $e) {
    echo "Hotels may already be linked: " . $e->getMessage() . "\n";
}

// Create rooms
$room1 = Room::firstOrCreate(
    ['accommodation_id' => $hotel1->accommodation_id, 'room_number' => '101'],
    [
        'floor' => 1,
        'room_type' => 'Suite',
        'status' => 'OCCUPIED'
    ]
);

$room2 = Room::firstOrCreate(
    ['accommodation_id' => $hotel2->accommodation_id, 'room_number' => '205'],
    [
        'floor' => 2,
        'room_type' => 'Double',
        'status' => 'AVAILABLE'
    ]
);
echo "Created rooms: #{$room1->room_number} and #{$room2->room_number}\n";

// Create room assignments
// Current accommodation (Mecca)
$assignment1 = RoomAssignment::updateOrCreate(
    ['pilgrim_id' => $pilgrim->pilgrim_id, 'accommodation_id' => $hotel1->accommodation_id],
    [
        'room_id' => $room1->id,
        'check_in' => now()->subDays(2),
        'check_out' => now()->addDays(3),
        'status' => 'CONFIRMED'
    ]
);
echo "Assignment 1 (Current - Mecca): Check-in {$assignment1->check_in}, Check-out {$assignment1->check_out}\n";

// Upcoming accommodation (Madinah)
$assignment2 = RoomAssignment::updateOrCreate(
    ['pilgrim_id' => $pilgrim->pilgrim_id, 'accommodation_id' => $hotel2->accommodation_id],
    [
        'room_id' => $room2->id,
        'check_in' => now()->addDays(3),
        'check_out' => now()->addDays(8),
        'status' => 'CONFIRMED'
    ]
);
echo "Assignment 2 (Upcoming - Madinah): Check-in {$assignment2->check_in}, Check-out {$assignment2->check_out}\n";

// Create group and add pilgrim
$group = GroupTrip::firstOrCreate(
    ['group_code' => 'ACCOM-TEST-GROUP'],
    [
        'trip_id' => $trip->trip_id,
        'group_status' => 'ACTIVE'
    ]
);

GroupMember::updateOrCreate(
    ['group_id' => $group->group_id, 'pilgrim_id' => $pilgrim->pilgrim_id],
    ['join_date' => now()->subDays(5), 'member_status' => 'ACTIVE']
);
echo "Added pilgrim to group\n";

echo "\n--- Starting API Tests ---\n\n";

// Login as pilgrim
echo "TEST 1: View all accommodations\n";
$loginResponse = login('pilgrim_accom_test@nusuk.com', 'password123');
if (isset($loginResponse['token'])) {
    $token = $loginResponse['token'];
    echo "✓ Logged in successfully\n";

    $result = makeRequest('GET', '/my-accommodations', $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved accommodations\n";
        echo "  Summary:\n";
        echo "    - Total: {$result['body']['summary']['total_accommodations']}\n";
        echo "    - Current: {$result['body']['summary']['current']}\n";
        echo "    - Upcoming: {$result['body']['summary']['upcoming']}\n";
        echo "    - Past: {$result['body']['summary']['past']}\n";

        if ($result['body']['current_accommodation']) {
            echo "  Current accommodation:\n";
            $curr = $result['body']['current_accommodation'];
            echo "    - Hotel: {$curr['hotel']['hotel_name']} ({$curr['hotel']['city']})\n";
            echo "    - Room: #{$curr['room']['room_number']}, Floor {$curr['room']['floor']}\n";
            echo "    - Stay: {$curr['stay']['duration_nights']} nights\n";
        }
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
} else {
    echo "✗ Login failed\n";
    print_r($loginResponse);
}

// Test 2: View current accommodation only
echo "\nTEST 2: View current accommodation\n";
if (isset($token)) {
    $result = makeRequest('GET', '/my-accommodations/current', $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        if ($result['body']['accommodation']) {
            echo "  ✓ SUCCESS: Retrieved current accommodation\n";
            $acc = $result['body']['accommodation'];
            echo "  - Hotel: {$acc['hotel']['hotel_name']}\n";
            echo "  - City: {$acc['hotel']['city']}\n";
            echo "  - Room: #{$acc['room']['room_number']} ({$acc['room']['room_type']})\n";
            echo "  - Check-out: {$acc['stay']['check_out_day']}\n";
            echo "  - Days remaining: {$acc['stay']['days_remaining']}\n";
        } else {
            echo "  No current accommodation\n";
        }
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
}

// Test 3: View trip-specific accommodations
echo "\nTEST 3: View trip accommodations\n";
if (isset($token)) {
    $result = makeRequest('GET', "/trips/{$trip->trip_id}/my-accommodations", $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved trip accommodations\n";
        echo "  Trip: {$result['body']['trip']['trip_name']}\n";
        echo "  Hotels in trip: {$result['body']['accommodations_count']}\n";
        echo "  Your assignments: {$result['body']['assigned_count']}\n";

        if (!empty($result['body']['accommodations'])) {
            echo "  Hotels:\n";
            foreach ($result['body']['accommodations'] as $h) {
                $assigned = $h['your_assignment'] ? 'Assigned' : 'Not Assigned';
                echo "    - {$h['hotel_name']} ({$h['city']}) - {$assigned}\n";
            }
        }
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
}

// Test 4: Try to access another trip's accommodations
echo "\nTEST 4: Attempt to access unauthorized trip accommodations\n";
if (isset($token)) {
    $result = makeRequest('GET', '/trips/999999/my-accommodations', $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 403) {
        echo "  ✓ SUCCESS: Correctly denied access\n";
    } else {
        echo "  Response: " . json_encode($result['body']) . "\n";
    }
}

echo "\n=== Tests Completed ===\n";
