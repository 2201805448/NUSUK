<?php
/**
 * Test Script: View Trip Schedule
 * Tests the feature allowing pilgrims to view the full timeline of their trip
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Pilgrim;
use App\Models\Trip;
use App\Models\Activity;
use App\Models\Transport;
use App\Models\GroupTrip;
use App\Models\GroupMember;
use App\Models\Package;
use App\Models\TripUpdate;
use Illuminate\Support\Facades\Hash;

echo "=== Testing View Trip Schedule Feature ===\n\n";

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
    ['email' => 'pilgrim_schedule_test@nusuk.com'],
    [
        'full_name' => 'Schedule Test Pilgrim',
        'phone_number' => '0500000030',
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
        'passport_name' => 'SCHEDULE TEST PILGRIM',
        'passport_number' => 'SCH123456',
        'nationality' => 'Saudi',
        'date_of_birth' => '1990-06-20',
        'gender' => 'MALE'
    ]
);
echo "Pilgrim profile created (ID: {$pilgrim->pilgrim_id})\n";

// Find or create package
$package = Package::first();
if (!$package) {
    $package = Package::create([
        'package_name' => 'Schedule Test Package',
        'duration_days' => 7,
        'price' => 5500.00,
        'services' => 'Hotel, Transport, Meals, Visits'
    ]);
}

// Create a trip starting today
$trip = Trip::firstOrCreate(
    ['trip_name' => 'Schedule Test Trip'],
    [
        'package_id' => $package->package_id,
        'start_date' => now(),
        'end_date' => now()->addDays(6),
        'status' => 'ACTIVE'
    ]
);
echo "Trip: {$trip->trip_name} (ID: {$trip->trip_id})\n";

// Create activities for multiple days
$activities = [
    ['day' => 0, 'time' => '08:00:00', 'type' => 'BREAKFAST', 'location' => 'Hotel Restaurant'],
    ['day' => 0, 'time' => '10:00:00', 'type' => 'WELCOME_MEETING', 'location' => 'Hotel Conference Room'],
    ['day' => 0, 'time' => '14:00:00', 'type' => 'VISIT_HARAM', 'location' => 'Masjid al-Haram'],
    ['day' => 1, 'time' => '05:00:00', 'type' => 'UMRAH', 'location' => 'Kaaba'],
    ['day' => 1, 'time' => '12:00:00', 'type' => 'LUNCH', 'location' => 'Hotel Restaurant'],
    ['day' => 2, 'time' => '09:00:00', 'type' => 'HISTORICAL_TOUR', 'location' => 'Jabal al-Nour'],
    ['day' => 3, 'time' => '06:00:00', 'type' => 'TRAVEL_TO_MADINAH', 'location' => 'Bus Station'],
    ['day' => 3, 'time' => '14:00:00', 'type' => 'VISIT_MASJID_NABAWI', 'location' => 'Masjid an-Nabawi'],
];

foreach ($activities as $act) {
    Activity::firstOrCreate(
        [
            'trip_id' => $trip->trip_id,
            'activity_type' => $act['type'],
            'activity_date' => now()->addDays($act['day'])->toDateString()
        ],
        [
            'location' => $act['location'],
            'activity_time' => $act['time'],
            'status' => $act['day'] == 0 ? 'SCHEDULED' : 'SCHEDULED'
        ]
    );
}
echo "Created " . count($activities) . " activities\n";

// Create transports
$transports = [
    ['day' => 0, 'time' => '13:30:00', 'type' => 'BUS', 'from' => 'Hotel', 'to' => 'Masjid al-Haram'],
    ['day' => 3, 'time' => '06:00:00', 'type' => 'BUS', 'from' => 'Mecca Hotel', 'to' => 'Madinah Hotel'],
];

foreach ($transports as $t) {
    Transport::firstOrCreate(
        [
            'trip_id' => $trip->trip_id,
            'route_from' => $t['from'],
            'route_to' => $t['to']
        ],
        [
            'transport_type' => $t['type'],
            'departure_time' => now()->addDays($t['day'])->setTimeFromTimeString($t['time']),
            'notes' => 'Test transport'
        ]
    );
}
echo "Created " . count($transports) . " transports\n";

// Create trip updates
TripUpdate::firstOrCreate(
    ['trip_id' => $trip->trip_id, 'title' => 'Welcome Message'],
    [
        'message' => 'Welcome to your Umrah journey! Please join us at the conference room at 10 AM.',
        'created_by' => $pilgrimUser->user_id,
        'created_at' => now()
    ]
);

TripUpdate::firstOrCreate(
    ['trip_id' => $trip->trip_id, 'title' => 'Schedule Update'],
    [
        'message' => 'Tomorrow\'s Umrah will start at 5 AM. Please be ready by 4:30 AM.',
        'created_by' => $pilgrimUser->user_id,
        'created_at' => now()->addHours(6)
    ]
);
echo "Created 2 trip updates\n";

// Create group and add pilgrim
$group = GroupTrip::firstOrCreate(
    ['group_code' => 'SCH-TEST-GROUP'],
    [
        'trip_id' => $trip->trip_id,
        'group_status' => 'ACTIVE'
    ]
);

GroupMember::updateOrCreate(
    ['group_id' => $group->group_id, 'pilgrim_id' => $pilgrim->pilgrim_id],
    ['join_date' => now(), 'member_status' => 'ACTIVE']
);
echo "Added pilgrim to group\n";

echo "\n--- Starting API Tests ---\n\n";

// Login as pilgrim
echo "TEST 1: Pilgrim views full trip schedule\n";
$loginResponse = login('pilgrim_schedule_test@nusuk.com', 'password123');
if (isset($loginResponse['token'])) {
    $token = $loginResponse['token'];
    echo "✓ Logged in successfully\n";

    $result = makeRequest('GET', "/trips/{$trip->trip_id}/schedule", $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved trip schedule\n";
        echo "  Trip: {$result['body']['trip']['trip_name']}\n";
        echo "  Duration: {$result['body']['trip']['duration_days']} days\n";
        echo "  Accommodations: " . count($result['body']['accommodations']) . "\n";
        echo "  Transports: " . count($result['body']['transportation']) . "\n";
        echo "  Activities: {$result['body']['activities_summary']['total']}\n";
        echo "  Timeline events: " . count($result['body']['timeline']) . "\n";
        echo "  Daily schedule days: " . count($result['body']['daily_schedule']) . "\n";
        echo "  Updates: " . count($result['body']['updates']) . "\n";

        if (!empty($result['body']['daily_schedule'])) {
            echo "\n  Daily Schedule Preview:\n";
            foreach (array_slice($result['body']['daily_schedule'], 0, 2) as $day) {
                echo "    Day {$day['day']} ({$day['date']} - {$day['day_name']}):\n";
                foreach (array_slice($day['events']->toArray() ?? $day['events'], 0, 3) as $event) {
                    echo "      - {$event['time']}: {$event['title']} at {$event['location']}\n";
                }
            }
        }
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
} else {
    echo "✗ Login failed\n";
    print_r($loginResponse);
}

// Test 2: View today's schedule
echo "\nTEST 2: View today's schedule\n";
if (isset($token)) {
    $result = makeRequest('GET', "/trips/{$trip->trip_id}/schedule/today", $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved today's schedule\n";
        echo "  Date: {$result['body']['date']} ({$result['body']['day_name']})\n";
        echo "  Activities: {$result['body']['activities_count']}\n";
        echo "  Transports: {$result['body']['transports_count']}\n";

        if (!empty($result['body']['schedule'])) {
            echo "  Today's events:\n";
            foreach ($result['body']['schedule'] as $event) {
                echo "    - {$event['time']}: [{$event['type']}] {$event['title']}\n";
            }
        }
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
}

// Test 3: Try to view a trip schedule the pilgrim isn't part of
echo "\nTEST 3: Attempt to view unauthorized trip schedule\n";
if (isset($token)) {
    $result = makeRequest('GET', '/trips/999999/schedule', $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 403 || $result['code'] === 404) {
        echo "  ✓ SUCCESS: Correctly denied access\n";
    } else {
        echo "  Response: " . json_encode($result['body']) . "\n";
    }
}

echo "\n=== Tests Completed ===\n";
