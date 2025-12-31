<?php
/**
 * Test Script: View Pilgrims List
 * Tests the feature allowing supervisors to view pilgrims in their groups
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Pilgrim;
use App\Models\Trip;
use App\Models\GroupTrip;
use App\Models\GroupMember;
use App\Models\Package;
use Illuminate\Support\Facades\Hash;

echo "=== Testing View Pilgrims List Feature ===\n\n";

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
    ['email' => 'supervisor_pilgrims_test@nusuk.com'],
    [
        'full_name' => 'Pilgrims List Supervisor',
        'phone_number' => '0500000070',
        'password' => Hash::make('password123'),
        'role' => 'SUPERVISOR',
        'account_status' => 'ACTIVE'
    ]
);
echo "Supervisor: {$supervisorUser->full_name} (ID: {$supervisorUser->user_id})\n";

// Find or create package
$package = Package::first();
if (!$package) {
    $package = Package::create([
        'package_name' => 'Pilgrims List Test Package',
        'duration_days' => 10,
        'price' => 6000.00
    ]);
}

// Create a trip
$trip = Trip::firstOrCreate(
    ['trip_name' => 'Pilgrims List Test Trip'],
    [
        'package_id' => $package->package_id,
        'start_date' => now()->addDays(10),
        'end_date' => now()->addDays(20),
        'status' => 'SCHEDULED'
    ]
);
echo "Trip: {$trip->trip_name} (ID: {$trip->trip_id})\n";

// Create group with supervisor
$group = GroupTrip::firstOrCreate(
    ['group_code' => 'PLG-TEST-GRP'],
    [
        'trip_id' => $trip->trip_id,
        'supervisor_id' => $supervisorUser->user_id,
        'group_status' => 'ACTIVE'
    ]
);
$group->update(['supervisor_id' => $supervisorUser->user_id]);
echo "Group: {$group->group_code} (ID: {$group->group_id})\n";

// Create multiple pilgrims
$pilgrims = [];
for ($i = 1; $i <= 5; $i++) {
    $pilgrimUser = User::firstOrCreate(
        ['email' => "pilgrim_list_test{$i}@nusuk.com"],
        [
            'full_name' => "Test Pilgrim {$i}",
            'phone_number' => "050000008{$i}",
            'password' => Hash::make('password123'),
            'role' => 'PILGRIM',
            'account_status' => 'ACTIVE'
        ]
    );

    $pilgrim = Pilgrim::firstOrCreate(
        ['user_id' => $pilgrimUser->user_id],
        [
            'passport_name' => "TEST PILGRIM {$i}",
            'passport_number' => "PLG{$i}23456",
            'nationality' => ['Egyptian', 'Jordanian', 'Saudi', 'Moroccan', 'Indonesian'][$i - 1],
            'date_of_birth' => "199{$i}-0{$i}-1{$i}",
            'gender' => $i % 2 == 0 ? 'FEMALE' : 'MALE'
        ]
    );

    // Add to group
    GroupMember::updateOrCreate(
        ['group_id' => $group->group_id, 'pilgrim_id' => $pilgrim->pilgrim_id],
        ['join_date' => now()->subDays($i), 'member_status' => 'ACTIVE']
    );

    $pilgrims[] = [
        'user' => $pilgrimUser,
        'pilgrim' => $pilgrim
    ];
}
echo "Created " . count($pilgrims) . " pilgrims and added to group\n";

echo "\n--- Starting API Tests ---\n\n";

// Login as supervisor
echo "TEST 1: Supervisor views all pilgrims in their groups\n";
$loginResponse = login('supervisor_pilgrims_test@nusuk.com', 'password123');
if (isset($loginResponse['token'])) {
    $token = $loginResponse['token'];
    echo "✓ Logged in as Supervisor\n";

    $result = makeRequest('GET', '/my-pilgrims', $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved pilgrims list\n";
        echo "  Summary:\n";
        echo "    - Total groups: {$result['body']['summary']['total_groups']}\n";
        echo "    - Total pilgrims: {$result['body']['summary']['total_pilgrims']}\n";
        echo "    - Active pilgrims: {$result['body']['summary']['active_pilgrims']}\n";

        if (!empty($result['body']['pilgrims'])) {
            echo "  Pilgrims:\n";
            foreach (array_slice($result['body']['pilgrims'], 0, 5) as $p) {
                echo "    - {$p['full_name']} ({$p['nationality']}) - Group: {$p['group_code']}\n";
            }
        }
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
} else {
    echo "✗ Login failed\n";
    print_r($loginResponse);
}

// Test 2: View pilgrims in specific group
echo "\nTEST 2: View pilgrims in specific group\n";
if (isset($token)) {
    $result = makeRequest('GET', "/groups/{$group->group_id}/pilgrims", $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved group pilgrims\n";
        echo "  Group: {$result['body']['group']['group_code']}\n";
        echo "  Trip: {$result['body']['group']['trip']['trip_name']}\n";
        echo "  Summary:\n";
        echo "    - Total: {$result['body']['summary']['total_pilgrims']}\n";
        echo "    - Active: {$result['body']['summary']['active_pilgrims']}\n";
        echo "    - Removed: {$result['body']['summary']['removed_pilgrims']}\n";

        if (!empty($result['body']['pilgrims'])) {
            echo "  Pilgrims details:\n";
            foreach ($result['body']['pilgrims'] as $p) {
                echo "    - {$p['full_name']}\n";
                echo "      Email: {$p['email']}, Phone: {$p['phone_number']}\n";
                echo "      Passport: {$p['passport_number']}, Nationality: {$p['nationality']}\n";
            }
        }
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
}

// Test 3: Filter by trip_id
echo "\nTEST 3: Filter pilgrims by trip\n";
if (isset($token)) {
    $result = makeRequest('GET', "/my-pilgrims?trip_id={$trip->trip_id}", $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved pilgrims filtered by trip\n";
        echo "  Filter: trip_id = {$result['body']['filter']['trip_id']}\n";
        echo "  Active pilgrims: {$result['body']['summary']['active_pilgrims']}\n";
    }
}

// Test 4: Unauthorized access to another group
echo "\nTEST 4: Unauthorized access to another group\n";
$otherGroup = GroupTrip::where('supervisor_id', '!=', $supervisorUser->user_id)
    ->whereNotNull('supervisor_id')
    ->first();
if (isset($token) && $otherGroup) {
    $result = makeRequest('GET', "/groups/{$otherGroup->group_id}/pilgrims", $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 403) {
        echo "  ✓ SUCCESS: Correctly denied access to unauthorized group\n";
    } else {
        echo "  Response: " . json_encode($result['body']) . "\n";
    }
} else {
    echo "  Skipped: No other group found for testing\n";
}

echo "\n=== Tests Completed ===\n";
