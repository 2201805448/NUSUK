<?php
/**
 * Test Script: Pilgrim Documents Review
 * Tests the feature allowing supervisors and management to view pilgrim documents
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Pilgrim;
use App\Models\GroupTrip;
use App\Models\GroupMember;
use App\Models\Trip;
use Illuminate\Support\Facades\Hash;

echo "=== Testing Pilgrim Documents Review Feature ===\n\n";

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

// Create or find Admin user
$adminUser = User::firstOrCreate(
    ['email' => 'admin_doc_test@nusuk.com'],
    [
        'full_name' => 'Doc Test Admin',
        'phone_number' => '0500000001',
        'password' => Hash::make('password123'),
        'role' => 'ADMIN',
        'account_status' => 'ACTIVE'
    ]
);
echo "Admin user: {$adminUser->email} (ID: {$adminUser->user_id})\n";

// Create or find Supervisor user
$supervisorUser = User::firstOrCreate(
    ['email' => 'supervisor_doc_test@nusuk.com'],
    [
        'full_name' => 'Doc Test Supervisor',
        'phone_number' => '0500000002',
        'password' => Hash::make('password123'),
        'role' => 'SUPERVISOR',
        'account_status' => 'ACTIVE'
    ]
);
echo "Supervisor user: {$supervisorUser->email} (ID: {$supervisorUser->user_id})\n";

// Create Pilgrim users
$pilgrimUser1 = User::firstOrCreate(
    ['email' => 'pilgrim_doc_test1@nusuk.com'],
    [
        'full_name' => 'Test Pilgrim One',
        'phone_number' => '0500000003',
        'password' => Hash::make('password123'),
        'role' => 'PILGRIM',
        'account_status' => 'ACTIVE'
    ]
);

$pilgrimUser2 = User::firstOrCreate(
    ['email' => 'pilgrim_doc_test2@nusuk.com'],
    [
        'full_name' => 'Test Pilgrim Two',
        'phone_number' => '0500000004',
        'password' => Hash::make('password123'),
        'role' => 'PILGRIM',
        'account_status' => 'ACTIVE'
    ]
);

// Create Pilgrim profiles
$pilgrim1 = Pilgrim::firstOrCreate(
    ['user_id' => $pilgrimUser1->user_id],
    [
        'passport_name' => 'TEST PILGRIM ONE',
        'passport_number' => 'A12345678',
        'passport_img' => '/storage/passports/test1.jpg',
        'visa_img' => '/storage/visas/test1.jpg',
        'nationality' => 'Egyptian',
        'date_of_birth' => '1985-05-15',
        'gender' => 'MALE',
        'emergency_call' => '+201234567890'
    ]
);
echo "Pilgrim 1: {$pilgrim1->passport_name} (ID: {$pilgrim1->pilgrim_id})\n";

$pilgrim2 = Pilgrim::firstOrCreate(
    ['user_id' => $pilgrimUser2->user_id],
    [
        'passport_name' => 'TEST PILGRIM TWO',
        'passport_number' => 'B98765432',
        'passport_img' => '/storage/passports/test2.jpg',
        'visa_img' => '/storage/visas/test2.jpg',
        'nationality' => 'Saudi',
        'date_of_birth' => '1990-10-20',
        'gender' => 'FEMALE',
        'emergency_call' => '+966501234567'
    ]
);
echo "Pilgrim 2: {$pilgrim2->passport_name} (ID: {$pilgrim2->pilgrim_id})\n";

// Find or create a trip
$trip = Trip::first();
if (!$trip) {
    echo "Warning: No trips found. Creating a test trip...\n";
    $trip = Trip::create([
        'trip_name' => 'Document Test Trip',
        'start_date' => now()->addDays(30),
        'end_date' => now()->addDays(45),
        'trip_status' => 'SCHEDULED'
    ]);
}
echo "Using Trip: {$trip->trip_name} (ID: {$trip->trip_id})\n";

// Create a group and assign supervisor
$group = GroupTrip::firstOrCreate(
    ['group_code' => 'DOC-TEST-GROUP'],
    [
        'trip_id' => $trip->trip_id,
        'supervisor_id' => $supervisorUser->user_id,
        'group_status' => 'ACTIVE'
    ]
);
// Update supervisor if needed
$group->update(['supervisor_id' => $supervisorUser->user_id]);
echo "Group: {$group->group_code} (ID: {$group->group_id})\n";

// Add pilgrim1 to the group (supervised by our supervisor)
GroupMember::updateOrCreate(
    ['group_id' => $group->group_id, 'pilgrim_id' => $pilgrim1->pilgrim_id],
    ['join_date' => now(), 'member_status' => 'ACTIVE']
);
echo "Added Pilgrim 1 to supervised group\n";

// Create another group without our supervisor (for testing access control)
$otherGroup = GroupTrip::firstOrCreate(
    ['group_code' => 'DOC-TEST-OTHER'],
    [
        'trip_id' => $trip->trip_id,
        'supervisor_id' => null,
        'group_status' => 'ACTIVE'
    ]
);

// Add pilgrim2 to the other group (NOT supervised by our test supervisor)
GroupMember::updateOrCreate(
    ['group_id' => $otherGroup->group_id, 'pilgrim_id' => $pilgrim2->pilgrim_id],
    ['join_date' => now(), 'member_status' => 'ACTIVE']
);
echo "Added Pilgrim 2 to other group (not supervised by test supervisor)\n";

echo "\n--- Starting API Tests ---\n\n";

// Test 1: Admin login and list all documents
echo "TEST 1: Admin lists all pilgrim documents\n";
$adminLogin = login('admin_doc_test@nusuk.com', 'password123');
if (isset($adminLogin['token'])) {
    $adminToken = $adminLogin['token'];
    echo "✓ Admin logged in successfully\n";

    $result = makeRequest('GET', '/pilgrims/documents', $adminToken);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved {$result['body']['count']} pilgrim(s)\n";
        if (isset($result['body']['data'][0])) {
            $sample = $result['body']['data'][0];
            echo "  Sample data: {$sample['passport_name']} - {$sample['passport_number']}\n";
        }
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
} else {
    echo "✗ Admin login failed\n";
}

// Test 2: Admin view specific pilgrim documents
echo "\nTEST 2: Admin views specific pilgrim documents\n";
if (isset($adminToken)) {
    $result = makeRequest('GET', "/pilgrims/{$pilgrim1->pilgrim_id}/documents", $adminToken);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved pilgrim documents\n";
        $data = $result['body']['data'];
        echo "  - Passport Name: {$data['documents']['passport_name']}\n";
        echo "  - Passport Number: {$data['documents']['passport_number']}\n";
        echo "  - Nationality: {$data['personal_data']['nationality']}\n";
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
}

// Test 3: Supervisor login and list their group's documents
echo "\nTEST 3: Supervisor lists their group's pilgrim documents\n";
$supervisorLogin = login('supervisor_doc_test@nusuk.com', 'password123');
if (isset($supervisorLogin['token'])) {
    $supervisorToken = $supervisorLogin['token'];
    echo "✓ Supervisor logged in successfully\n";

    $result = makeRequest('GET', '/pilgrims/documents', $supervisorToken);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved {$result['body']['count']} pilgrim(s)\n";
        // Should only see pilgrim1, not pilgrim2
        $found1 = false;
        $found2 = false;
        foreach ($result['body']['data'] as $p) {
            if ($p['pilgrim_id'] == $pilgrim1->pilgrim_id)
                $found1 = true;
            if ($p['pilgrim_id'] == $pilgrim2->pilgrim_id)
                $found2 = true;
        }
        if ($found1 && !$found2) {
            echo "  ✓ PERMISSION CHECK PASSED: Supervisor only sees pilgrims in their groups\n";
        } elseif ($found1 && $found2) {
            echo "  ⚠ WARNING: Supervisor can see pilgrims outside their groups\n";
        }
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
} else {
    echo "✗ Supervisor login failed\n";
}

// Test 4: Supervisor tries to view pilgrim NOT in their group
echo "\nTEST 4: Supervisor attempts to view pilgrim NOT in their group\n";
if (isset($supervisorToken)) {
    $result = makeRequest('GET', "/pilgrims/{$pilgrim2->pilgrim_id}/documents", $supervisorToken);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 403) {
        echo "  ✓ SUCCESS: Access correctly denied (403 Forbidden)\n";
    } else {
        echo "  ✗ UNEXPECTED: Expected 403, got {$result['code']}\n";
        echo "  Response: " . json_encode($result['body']) . "\n";
    }
}

// Test 5: Supervisor views pilgrim IN their group
echo "\nTEST 5: Supervisor views pilgrim IN their group\n";
if (isset($supervisorToken)) {
    $result = makeRequest('GET', "/pilgrims/{$pilgrim1->pilgrim_id}/documents", $supervisorToken);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved pilgrim documents\n";
        $data = $result['body']['data'];
        echo "  - Passport Name: {$data['documents']['passport_name']}\n";
        echo "  - Group: " . ($data['group_info']['group_code'] ?? 'N/A') . "\n";
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
}

// Test 6: Admin filters by trip_id
echo "\nTEST 6: Admin filters documents by trip_id\n";
if (isset($adminToken)) {
    $result = makeRequest('GET', "/pilgrims/documents?trip_id={$trip->trip_id}", $adminToken);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved {$result['body']['count']} pilgrim(s) for trip {$trip->trip_id}\n";
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
}

// Test 7: Admin filters by group_id
echo "\nTEST 7: Admin filters documents by group_id\n";
if (isset($adminToken)) {
    $result = makeRequest('GET', "/pilgrims/documents?group_id={$group->group_id}", $adminToken);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved {$result['body']['count']} pilgrim(s) for group {$group->group_id}\n";
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
}

echo "\n=== Tests Completed ===\n";
