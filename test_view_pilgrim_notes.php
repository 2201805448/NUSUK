<?php
/**
 * Test Script: View Pilgrim Notes
 * Tests the feature allowing supervisors to view notes submitted by pilgrims
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Pilgrim;
use App\Models\Trip;
use App\Models\PilgrimNote;
use App\Models\GroupTrip;
use App\Models\GroupMember;
use App\Models\Package;
use Illuminate\Support\Facades\Hash;

echo "=== Testing View Pilgrim Notes Feature ===\n\n";

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
    ['email' => 'supervisor_notes_view@nusuk.com'],
    [
        'full_name' => 'Notes View Supervisor',
        'phone_number' => '0500000090',
        'password' => Hash::make('password123'),
        'role' => 'SUPERVISOR',
        'account_status' => 'ACTIVE'
    ]
);
echo "Supervisor: {$supervisorUser->full_name}\n";

// Create Pilgrim user
$pilgrimUser = User::firstOrCreate(
    ['email' => 'pilgrim_notes_submit@nusuk.com'],
    [
        'full_name' => 'Notes Submit Pilgrim',
        'phone_number' => '0500000091',
        'password' => Hash::make('password123'),
        'role' => 'PILGRIM',
        'account_status' => 'ACTIVE'
    ]
);

$pilgrim = Pilgrim::firstOrCreate(
    ['user_id' => $pilgrimUser->user_id],
    [
        'passport_name' => 'NOTES SUBMIT PILGRIM',
        'passport_number' => 'NTS123456',
        'nationality' => 'Pakistani',
        'date_of_birth' => '1988-07-22',
        'gender' => 'MALE'
    ]
);
echo "Pilgrim: {$pilgrimUser->full_name} (ID: {$pilgrim->pilgrim_id})\n";

// Find or create package and trip
$package = Package::first();
$trip = Trip::firstOrCreate(
    ['trip_name' => 'Pilgrim Notes Test Trip'],
    [
        'package_id' => $package->package_id,
        'start_date' => now()->addDays(5),
        'end_date' => now()->addDays(15),
        'status' => 'SCHEDULED'
    ]
);
echo "Trip: {$trip->trip_name} (ID: {$trip->trip_id})\n";

// Create group with supervisor
$group = GroupTrip::firstOrCreate(
    ['group_code' => 'NOTES-TEST-GRP'],
    [
        'trip_id' => $trip->trip_id,
        'supervisor_id' => $supervisorUser->user_id,
        'group_status' => 'ACTIVE'
    ]
);
$group->update(['supervisor_id' => $supervisorUser->user_id]);

// Add pilgrim to group
GroupMember::updateOrCreate(
    ['group_id' => $group->group_id, 'pilgrim_id' => $pilgrim->pilgrim_id],
    ['join_date' => now(), 'member_status' => 'ACTIVE']
);
echo "Added pilgrim to group: {$group->group_code}\n";

echo "\n--- Starting API Tests ---\n\n";

// TEST 1: Pilgrim submits notes
echo "TEST 1: Pilgrim submits feedback notes\n";
$pilgrimLogin = login('pilgrim_notes_submit@nusuk.com', 'password123');
if (isset($pilgrimLogin['token'])) {
    $pilgrimToken = $pilgrimLogin['token'];
    echo "✓ Logged in as Pilgrim\n";

    // Submit multiple notes
    $notes = [
        ['note_type' => 'FEEDBACK', 'category' => 'ACCOMMODATION', 'priority' => 'MEDIUM', 'note_text' => 'The hotel room was very clean and comfortable. Great service!'],
        ['note_type' => 'SUGGESTION', 'category' => 'TRANSPORT', 'priority' => 'LOW', 'note_text' => 'It would be helpful to have more information about bus schedules.'],
        ['note_type' => 'COMPLAINT', 'category' => 'FOOD', 'priority' => 'HIGH', 'note_text' => 'The breakfast options are limited. Please add more variety.'],
    ];

    $createdNotes = [];
    foreach ($notes as $noteData) {
        $noteData['trip_id'] = $trip->trip_id;
        $result = makeRequest('POST', '/my-notes', $pilgrimToken, $noteData);
        if ($result['code'] === 201) {
            $createdNotes[] = $result['body']['note'];
            echo "  ✓ Submitted {$noteData['note_type']} note about {$noteData['category']}\n";
        } else {
            echo "  ✗ Failed to submit note: " . json_encode($result['body']) . "\n";
        }
    }

    // View own notes
    echo "\n  Viewing own notes:\n";
    $result = makeRequest('GET', '/my-notes', $pilgrimToken);
    if ($result['code'] === 200) {
        echo "  ✓ Total notes: {$result['body']['total']}\n";
    }
}

// TEST 2: Supervisor views pilgrim notes
echo "\nTEST 2: Supervisor views all pilgrim notes\n";
$supervisorLogin = login('supervisor_notes_view@nusuk.com', 'password123');
if (isset($supervisorLogin['token'])) {
    $supervisorToken = $supervisorLogin['token'];
    echo "✓ Logged in as Supervisor\n";

    $result = makeRequest('GET', '/pilgrim-notes', $supervisorToken);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved pilgrim notes\n";
        echo "  Summary:\n";
        echo "    - Total notes: {$result['body']['summary']['total_notes']}\n";
        echo "    - Pending: {$result['body']['summary']['pending']}\n";
        echo "    - Reviewed: {$result['body']['summary']['reviewed']}\n";

        if (!empty($result['body']['analysis']['by_category'])) {
            echo "  By category:\n";
            foreach ($result['body']['analysis']['by_category'] as $cat => $count) {
                echo "    - {$cat}: {$count}\n";
            }
        }

        if (!empty($result['body']['notes'])) {
            echo "  Notes preview:\n";
            foreach (array_slice($result['body']['notes'], 0, 3) as $note) {
                echo "    - [{$note['note_type']}] {$note['category']}: " . substr($note['note_text'], 0, 50) . "...\n";
            }
        }
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
}

// TEST 3: Supervisor responds to a note
echo "\nTEST 3: Supervisor responds to a pilgrim note\n";
if (isset($supervisorToken) && !empty($createdNotes)) {
    $noteId = $createdNotes[0]['note_id'];
    $result = makeRequest('POST', "/pilgrim-notes/{$noteId}/respond", $supervisorToken, [
        'status' => 'REVIEWED',
        'response' => 'Thank you for your feedback! We are glad you enjoyed your stay.'
    ]);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Response added\n";
        echo "  New status: {$result['body']['note']['status']}\n";
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
}

// TEST 4: Filter notes
echo "\nTEST 4: Filter notes by category\n";
if (isset($supervisorToken)) {
    $result = makeRequest('GET', '/pilgrim-notes?category=FOOD', $supervisorToken);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Filtered notes by FOOD category\n";
        echo "  Results: {$result['body']['summary']['total_notes']} notes\n";
    }
}

echo "\n=== Tests Completed ===\n";
