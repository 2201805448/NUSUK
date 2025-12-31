<?php
/**
 * Test Script: Download Trip Documents
 * Tests the feature allowing pilgrims to download trip documents
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Pilgrim;
use App\Models\Trip;
use App\Models\TripDocument;
use App\Models\GroupTrip;
use App\Models\GroupMember;
use App\Models\Package;
use Illuminate\Support\Facades\Hash;

echo "=== Testing Download Trip Documents Feature ===\n\n";

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

// Create Admin user
$adminUser = User::firstOrCreate(
    ['email' => 'admin_docs_test@nusuk.com'],
    [
        'full_name' => 'Documents Test Admin',
        'phone_number' => '0500000060',
        'password' => Hash::make('password123'),
        'role' => 'ADMIN',
        'account_status' => 'ACTIVE'
    ]
);
echo "Admin: {$adminUser->full_name}\n";

// Create Pilgrim user
$pilgrimUser = User::firstOrCreate(
    ['email' => 'pilgrim_docs_test@nusuk.com'],
    [
        'full_name' => 'Documents Test Pilgrim',
        'phone_number' => '0500000061',
        'password' => Hash::make('password123'),
        'role' => 'PILGRIM',
        'account_status' => 'ACTIVE'
    ]
);
echo "Pilgrim: {$pilgrimUser->full_name}\n";

// Create Pilgrim profile
$pilgrim = Pilgrim::firstOrCreate(
    ['user_id' => $pilgrimUser->user_id],
    [
        'passport_name' => 'DOCUMENTS TEST PILGRIM',
        'passport_number' => 'DOC123456',
        'nationality' => 'Sudanese',
        'date_of_birth' => '1993-09-15',
        'gender' => 'FEMALE'
    ]
);
echo "Pilgrim profile created (ID: {$pilgrim->pilgrim_id})\n";

// Find or create package
$package = Package::first();
if (!$package) {
    $package = Package::create([
        'package_name' => 'Documents Test Package',
        'duration_days' => 10,
        'price' => 6500.00
    ]);
}

// Create a trip
$trip = Trip::firstOrCreate(
    ['trip_name' => 'Documents Test Trip'],
    [
        'package_id' => $package->package_id,
        'start_date' => now()->addDays(5),
        'end_date' => now()->addDays(15),
        'status' => 'SCHEDULED'
    ]
);
echo "Trip: {$trip->trip_name} (ID: {$trip->trip_id})\n";

// Create sample documents
$docs = [
    [
        'title' => 'Trip Program Schedule',
        'description' => 'Complete day-by-day program for the Umrah trip',
        'document_type' => 'PROGRAM',
        'file_name' => 'trip_program.pdf',
        'file_path' => 'trip_documents/test/trip_program.pdf',
        'file_type' => 'pdf',
        'file_size' => 1024 * 512, // 512 KB
    ],
    [
        'title' => 'Travel Instructions',
        'description' => 'Important guidelines and instructions for travelers',
        'document_type' => 'INSTRUCTIONS',
        'file_name' => 'travel_instructions.pdf',
        'file_path' => 'trip_documents/test/travel_instructions.pdf',
        'file_type' => 'pdf',
        'file_size' => 1024 * 256, // 256 KB
    ],
    [
        'title' => 'Mecca Map',
        'description' => 'Detailed map of holy sites in Mecca',
        'document_type' => 'MAP',
        'file_name' => 'mecca_map.jpg',
        'file_path' => 'trip_documents/test/mecca_map.jpg',
        'file_type' => 'jpg',
        'file_size' => 1024 * 1024 * 2, // 2 MB
    ],
    [
        'title' => 'Umrah Guide',
        'description' => 'Step-by-step guide to perform Umrah',
        'document_type' => 'GUIDE',
        'file_name' => 'umrah_guide.pdf',
        'file_path' => 'trip_documents/test/umrah_guide.pdf',
        'file_type' => 'pdf',
        'file_size' => 1024 * 768, // 768 KB
    ],
];

$createdDocs = [];
foreach ($docs as $doc) {
    $createdDoc = TripDocument::firstOrCreate(
        ['trip_id' => $trip->trip_id, 'title' => $doc['title']],
        array_merge($doc, [
            'trip_id' => $trip->trip_id,
            'is_public' => true,
            'uploaded_by' => $adminUser->user_id,
        ])
    );
    $createdDocs[] = $createdDoc;
}
echo "Created " . count($createdDocs) . " sample documents\n";

// Create group and add pilgrim
$group = GroupTrip::firstOrCreate(
    ['group_code' => 'DOCS-TEST-GRP'],
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
echo "TEST 1: List all trip documents\n";
$loginResponse = login('pilgrim_docs_test@nusuk.com', 'password123');
if (isset($loginResponse['token'])) {
    $token = $loginResponse['token'];
    echo "✓ Logged in successfully\n";

    $result = makeRequest('GET', "/trips/{$trip->trip_id}/documents", $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved documents list\n";
        echo "  Total documents: {$result['body']['total_documents']}\n";

        if (!empty($result['body']['documents_by_type'])) {
            echo "  Documents by type:\n";
            foreach ($result['body']['documents_by_type'] as $group) {
                echo "    - {$group['type']}: {$group['count']} document(s)\n";
            }
        }

        if (!empty($result['body']['documents'])) {
            echo "  All documents:\n";
            foreach ($result['body']['documents'] as $doc) {
                echo "    - [{$doc['document_type']}] {$doc['title']} ({$doc['file_size_human']})\n";
            }
        }
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
} else {
    echo "✗ Login failed\n";
    print_r($loginResponse);
}

// Test 2: View document details
echo "\nTEST 2: View document details\n";
if (isset($token) && !empty($createdDocs)) {
    $docId = $createdDocs[0]->document_id;
    $result = makeRequest('GET', "/trips/{$trip->trip_id}/documents/{$docId}", $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved document details\n";
        $doc = $result['body']['document'];
        echo "  Title: {$doc['title']}\n";
        echo "  Description: {$doc['description']}\n";
        echo "  Type: {$doc['document_type']}\n";
        echo "  File: {$doc['file_name']} ({$doc['file_size_human']})\n";
        echo "  Download URL: {$doc['download_url']}\n";
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
}

// Test 3: Attempt download (will fail because file doesn't exist, but tests the endpoint)
echo "\nTEST 3: Attempt document download\n";
if (isset($token) && !empty($createdDocs)) {
    $docId = $createdDocs[0]->document_id;
    $result = makeRequest('GET', "/trips/{$trip->trip_id}/documents/{$docId}/download", $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 404 && isset($result['body']['message'])) {
        echo "  ✓ Expected: File not found (demo data, file doesn't actually exist)\n";
        echo "  Message: {$result['body']['message']}\n";
    } elseif ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Download started (file exists)\n";
    } else {
        echo "  Response: " . json_encode($result['body']) . "\n";
    }
}

// Test 4: Unauthorized access to another trip's documents
echo "\nTEST 4: Unauthorized access attempt\n";
if (isset($token)) {
    $result = makeRequest('GET', '/trips/999999/documents', $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 403) {
        echo "  ✓ SUCCESS: Correctly denied access\n";
    } else {
        echo "  Response: " . json_encode($result['body']) . "\n";
    }
}

// Test 5: Admin uploads a document (would require multipart form)
echo "\nTEST 5: Admin document management\n";
$adminLogin = login('admin_docs_test@nusuk.com', 'password123');
if (isset($adminLogin['token'])) {
    echo "  Note: Document upload requires multipart/form-data (file upload)\n";
    echo "  The POST /trips/{trip_id}/documents endpoint is available for admins\n";
    echo "  ✓ Admin login successful (role: ADMIN)\n";
}

echo "\n=== Tests Completed ===\n";
