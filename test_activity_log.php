<?php
/**
 * Test Script: View Previous Activity Log
 * Tests the feature allowing pilgrims to view their trip and activity history
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Pilgrim;
use App\Models\Trip;
use App\Models\Activity;
use App\Models\GroupTrip;
use App\Models\GroupMember;
use App\Models\Package;
use Illuminate\Support\Facades\Hash;

echo "=== Testing View Previous Activity Log Feature ===\n\n";

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
    ['email' => 'pilgrim_activity_test@nusuk.com'],
    [
        'full_name' => 'Activity Test Pilgrim',
        'phone_number' => '0500000020',
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
        'passport_name' => 'ACTIVITY TEST PILGRIM',
        'passport_number' => 'ACT123456',
        'nationality' => 'Egyptian',
        'date_of_birth' => '1988-03-15',
        'gender' => 'MALE'
    ]
);
echo "Pilgrim profile created (ID: {$pilgrim->pilgrim_id})\n";

// Find or create package
$package = Package::first();
if (!$package) {
    $package = Package::create([
        'package_name' => 'Activity Test Package',
        'duration_days' => 10,
        'price' => 6000.00
    ]);
}

// Create a past trip with activities
$pastTrip = Trip::firstOrCreate(
    ['trip_name' => 'Past Activity Test Trip'],
    [
        'package_id' => $package->package_id,
        'start_date' => now()->subDays(45),
        'end_date' => now()->subDays(35),
        'status' => 'COMPLETED'
    ]
);
echo "Past Trip: {$pastTrip->trip_name} (ID: {$pastTrip->trip_id})\n";

// Create activities for past trip
$activity1 = Activity::firstOrCreate(
    ['trip_id' => $pastTrip->trip_id, 'activity_type' => 'VISIT_HARAM'],
    [
        'location' => 'Masjid al-Haram, Mecca',
        'activity_date' => $pastTrip->start_date,
        'activity_time' => '08:00:00',
        'status' => 'DONE'
    ]
);

$activity2 = Activity::firstOrCreate(
    ['trip_id' => $pastTrip->trip_id, 'activity_type' => 'UMRAH'],
    [
        'location' => 'Kaaba, Mecca',
        'activity_date' => $pastTrip->start_date->addDay(),
        'activity_time' => '05:00:00',
        'status' => 'DONE'
    ]
);

$activity3 = Activity::firstOrCreate(
    ['trip_id' => $pastTrip->trip_id, 'activity_type' => 'VISIT_MADINAH'],
    [
        'location' => 'Masjid an-Nabawi, Madinah',
        'activity_date' => $pastTrip->start_date->addDays(3),
        'activity_time' => '10:00:00',
        'status' => 'DONE'
    ]
);
echo "Created 3 activities for past trip\n";

// Create a current/future trip
$futureTrip = Trip::firstOrCreate(
    ['trip_name' => 'Current Activity Test Trip'],
    [
        'package_id' => $package->package_id,
        'start_date' => now()->addDays(5),
        'end_date' => now()->addDays(15),
        'status' => 'SCHEDULED'
    ]
);
echo "Future Trip: {$futureTrip->trip_name} (ID: {$futureTrip->trip_id})\n";

// Create activities for future trip
Activity::firstOrCreate(
    ['trip_id' => $futureTrip->trip_id, 'activity_type' => 'HOTEL_CHECK_IN'],
    [
        'location' => 'Mecca Grand Hotel',
        'activity_date' => $futureTrip->start_date,
        'activity_time' => '14:00:00',
        'status' => 'SCHEDULED'
    ]
);

Activity::firstOrCreate(
    ['trip_id' => $futureTrip->trip_id, 'activity_type' => 'WELCOME_MEETING'],
    [
        'location' => 'Hotel Conference Room',
        'activity_date' => $futureTrip->start_date,
        'activity_time' => '19:00:00',
        'status' => 'SCHEDULED'
    ]
);
echo "Created 2 activities for future trip\n";

// Create groups and add pilgrim
$group1 = GroupTrip::firstOrCreate(
    ['group_code' => 'ACT-TEST-PAST'],
    [
        'trip_id' => $pastTrip->trip_id,
        'group_status' => 'FINISHED'
    ]
);

$group2 = GroupTrip::firstOrCreate(
    ['group_code' => 'ACT-TEST-FUTURE'],
    [
        'trip_id' => $futureTrip->trip_id,
        'group_status' => 'ACTIVE'
    ]
);

// Add pilgrim to groups
GroupMember::updateOrCreate(
    ['group_id' => $group1->group_id, 'pilgrim_id' => $pilgrim->pilgrim_id],
    ['join_date' => $pastTrip->start_date, 'member_status' => 'ACTIVE']
);

GroupMember::updateOrCreate(
    ['group_id' => $group2->group_id, 'pilgrim_id' => $pilgrim->pilgrim_id],
    ['join_date' => now(), 'member_status' => 'ACTIVE']
);
echo "Added pilgrim to both groups\n";

echo "\n--- Starting API Tests ---\n\n";

// Login as pilgrim
echo "TEST 1: Pilgrim views activity log\n";
$loginResponse = login('pilgrim_activity_test@nusuk.com', 'password123');
if (isset($loginResponse['token'])) {
    $token = $loginResponse['token'];
    echo "✓ Logged in successfully\n";

    $result = makeRequest('GET', '/activity-log', $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved activity log\n";
        echo "  Summary:\n";
        echo "    - Total trips: {$result['body']['summary']['total_trips']}\n";
        echo "    - Past trips: {$result['body']['summary']['past_trips']}\n";
        echo "    - Current trips: {$result['body']['summary']['current_trips']}\n";
        echo "    - Upcoming trips: {$result['body']['summary']['upcoming_trips']}\n";
        echo "    - Total activities: {$result['body']['summary']['total_activities']}\n";
        echo "    - Completed activities: {$result['body']['summary']['completed_activities']}\n";

        if (!empty($result['body']['activity_log'])) {
            echo "\n  Trips:\n";
            foreach ($result['body']['activity_log'] as $trip) {
                echo "    - {$trip['trip_name']} ({$trip['status_category']}): {$trip['activities_count']} activities\n";
            }
        }
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
} else {
    echo "✗ Login failed\n";
    print_r($loginResponse);
}

// Test 2: View specific trip activity log
echo "\nTEST 2: View detailed activity log for past trip\n";
if (isset($token)) {
    $result = makeRequest('GET', "/activity-log/trips/{$pastTrip->trip_id}", $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved trip activity log\n";
        echo "  Trip: {$result['body']['trip']['trip_name']}\n";
        echo "  Activities Summary:\n";
        echo "    - Total: {$result['body']['activities_summary']['total']}\n";
        echo "    - Completed: {$result['body']['activities_summary']['completed']}\n";
        echo "    - Scheduled: {$result['body']['activities_summary']['scheduled']}\n";

        if (!empty($result['body']['program_by_date'])) {
            echo "  Program by date:\n";
            foreach ($result['body']['program_by_date'] as $day) {
                echo "    {$day['date']}:\n";
                foreach ($day['activities'] as $act) {
                    echo "      - {$act['activity_type']} at {$act['location']} ({$act['status']})\n";
                }
            }
        }
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
}

// Test 3: Try to view a trip the pilgrim didn't participate in
echo "\nTEST 3: Attempt to view unauthorized trip\n";
if (isset($token)) {
    $result = makeRequest('GET', '/activity-log/trips/999999', $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 403 || $result['code'] === 404) {
        echo "  ✓ SUCCESS: Correctly denied access to unauthorized trip\n";
    } else {
        echo "  Response: " . json_encode($result['body']) . "\n";
    }
}

// Test 4: User without pilgrim profile
echo "\nTEST 4: User without pilgrim profile\n";
$nonPilgrimUser = User::firstOrCreate(
    ['email' => 'non_pilgrim_test@nusuk.com'],
    [
        'full_name' => 'Non Pilgrim User',
        'phone_number' => '0500000021',
        'password' => Hash::make('password123'),
        'role' => 'PILGRIM',
        'account_status' => 'ACTIVE'
    ]
);
$loginResponse2 = login('non_pilgrim_test@nusuk.com', 'password123');
if (isset($loginResponse2['token'])) {
    $token2 = $loginResponse2['token'];
    $result = makeRequest('GET', '/activity-log', $token2);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 404) {
        echo "  ✓ SUCCESS: Correctly returned 404 for user without pilgrim profile\n";
    } else {
        echo "  Response: " . json_encode($result['body']) . "\n";
    }
}

echo "\n=== Tests Completed ===\n";
