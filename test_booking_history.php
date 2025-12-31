<?php
/**
 * Test Script: View Booking History
 * Tests the feature allowing pilgrims to view their booking history
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Trip;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

echo "=== Testing View Booking History Feature ===\n\n";

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
    ['email' => 'pilgrim_booking_test@nusuk.com'],
    [
        'full_name' => 'Booking Test Pilgrim',
        'phone_number' => '0500000010',
        'password' => Hash::make('password123'),
        'role' => 'PILGRIM',
        'account_status' => 'ACTIVE'
    ]
);
echo "Pilgrim user: {$pilgrimUser->email} (ID: {$pilgrimUser->user_id})\n";

// Find or create a package
$package = Package::first();
if (!$package) {
    $package = Package::create([
        'package_name' => 'Test Umrah Package',
        'duration_days' => 7,
        'price' => 5000.00,
        'services' => 'Hotel, Transport, Meals'
    ]);
}
echo "Package: {$package->package_name} (ID: {$package->package_id})\n";

// Find or create trips
$futureTrip = Trip::where('end_date', '>=', now())->first();
if (!$futureTrip) {
    $futureTrip = Trip::create([
        'package_id' => $package->package_id,
        'trip_name' => 'Future Umrah Trip',
        'start_date' => now()->addDays(30),
        'end_date' => now()->addDays(37),
        'trip_status' => 'SCHEDULED'
    ]);
}
echo "Future Trip: {$futureTrip->trip_name} (ID: {$futureTrip->trip_id})\n";

$pastTrip = Trip::where('end_date', '<', now())->first();
if (!$pastTrip) {
    $pastTrip = Trip::create([
        'package_id' => $package->package_id,
        'trip_name' => 'Past Umrah Trip',
        'start_date' => now()->subDays(45),
        'end_date' => now()->subDays(38),
        'trip_status' => 'COMPLETED'
    ]);
}
echo "Past Trip: {$pastTrip->trip_name} (ID: {$pastTrip->trip_id})\n";

// Create bookings for the pilgrim
$booking1 = Booking::firstOrCreate(
    ['booking_ref' => 'BK-TEST-CURRENT'],
    [
        'user_id' => $pilgrimUser->user_id,
        'package_id' => $package->package_id,
        'trip_id' => $futureTrip->trip_id,
        'booking_date' => now(),
        'total_price' => 5000.00,
        'pay_method' => 'CREDIT_CARD',
        'status' => 'CONFIRMED',
        'request_notes' => 'Current booking for testing'
    ]
);
echo "Booking 1 (Current): {$booking1->booking_ref} - Status: {$booking1->status}\n";

$booking2 = Booking::firstOrCreate(
    ['booking_ref' => 'BK-TEST-PAST'],
    [
        'user_id' => $pilgrimUser->user_id,
        'package_id' => $package->package_id,
        'trip_id' => $pastTrip->trip_id,
        'booking_date' => now()->subDays(60),
        'total_price' => 4500.00,
        'pay_method' => 'BANK_TRANSFER',
        'status' => 'CONFIRMED',
        'request_notes' => 'Past booking for testing'
    ]
);
echo "Booking 2 (Past): {$booking2->booking_ref} - Status: {$booking2->status}\n";

$booking3 = Booking::firstOrCreate(
    ['booking_ref' => 'BK-TEST-CANCELLED'],
    [
        'user_id' => $pilgrimUser->user_id,
        'package_id' => $package->package_id,
        'trip_id' => $futureTrip->trip_id,
        'booking_date' => now()->subDays(10),
        'total_price' => 5000.00,
        'pay_method' => 'CASH',
        'status' => 'CANCELLED',
        'request_notes' => 'Cancelled booking for testing'
    ]
);
echo "Booking 3 (Cancelled): {$booking3->booking_ref} - Status: {$booking3->status}\n";

echo "\n--- Starting API Tests ---\n\n";

// Login as pilgrim
echo "TEST 1: Pilgrim views booking history\n";
$loginResponse = login('pilgrim_booking_test@nusuk.com', 'password123');
if (isset($loginResponse['token'])) {
    $token = $loginResponse['token'];
    echo "✓ Logged in successfully\n";

    $result = makeRequest('GET', '/bookings', $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved booking history\n";
        echo "  Total bookings: {$result['body']['total_count']}\n";
        echo "  Current bookings: {$result['body']['current_count']}\n";
        echo "  Past bookings: {$result['body']['past_count']}\n";

        if (!empty($result['body']['bookings'])) {
            echo "\n  Bookings list:\n";
            foreach ($result['body']['bookings'] as $b) {
                echo "    - {$b['booking_ref']}: {$b['status']} - " . ($b['trip']['trip_name'] ?? 'N/A') . "\n";
            }
        }
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
} else {
    echo "✗ Login failed\n";
    print_r($loginResponse);
}

// Test 2: Filter by status
echo "\nTEST 2: Filter bookings by status (CONFIRMED)\n";
if (isset($token)) {
    $result = makeRequest('GET', '/bookings?status=CONFIRMED', $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved {$result['body']['total_count']} confirmed booking(s)\n";
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
}

// Test 3: View specific booking details
echo "\nTEST 3: View specific booking details\n";
if (isset($token)) {
    $result = makeRequest('GET', "/bookings/{$booking1->booking_id}", $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 200) {
        echo "  ✓ SUCCESS: Retrieved booking details\n";
        $b = $result['body']['booking'];
        echo "  - Booking Ref: {$b['booking_ref']}\n";
        echo "  - Status: {$b['status']}\n";
        echo "  - Total Price: {$b['total_price']}\n";
        echo "  - Package: " . ($b['package']['package_name'] ?? 'N/A') . "\n";
        echo "  - Trip: " . ($b['trip']['trip_name'] ?? 'N/A') . "\n";
    } else {
        echo "  ✗ FAILED: " . json_encode($result['body']) . "\n";
    }
}

// Test 4: Try to view another user's booking (should fail)
echo "\nTEST 4: Attempt to view non-existent/unauthorized booking\n";
if (isset($token)) {
    $result = makeRequest('GET', '/bookings/999999', $token);
    echo "  Response code: {$result['code']}\n";
    if ($result['code'] === 404) {
        echo "  ✓ SUCCESS: Correctly returned 404 for non-existent booking\n";
    } else {
        echo "  Response: " . json_encode($result['body']) . "\n";
    }
}

echo "\n=== Tests Completed ===\n";
