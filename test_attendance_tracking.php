<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Trip;
use App\Models\Pilgrim;
use App\Models\Package;
use App\Models\Activity;
use App\Models\AttendanceTracking;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

Log::info("=== STARTING ATTENDANCE PERMISSION TEST ===");
echo "=== Testing Attendance Tracking Permissions ===\n\n";

// --- Helpers ---
function createTestUser($role, $email)
{
    $user = User::where('email', $email)->first();
    if (!$user) {
        $user = User::create([
            'full_name' => "Test $role",
            'email' => $email,
            'phone_number' => rand(1000000000, 9999999999),
            'password' => Hash::make('password'),
            'role' => $role,
        ]);
    }
    return $user;
}

function makeRequest($app, $method, $uri, $token, $data = [])
{
    // Clear Auth state to prevent stickiness
    Auth::forgetGuards();
    // Also might need to clear the resolved request in the container if it's cached
    $app->forgetInstance('request');

    $request = Request::create($uri, $method, $data);
    $request->headers->set('Authorization', 'Bearer ' . $token);
    $request->headers->set('Accept', 'application/json');

    // Rebind request
    $app->instance('request', $request);

    return $app->handle($request);
}

// 1. Setup Data
echo "1. Setting up test data...\n";

// Users
$supervisor = createTestUser('SUPERVISOR', 'supervisor_test_attend@example.com');
$admin = createTestUser('ADMIN', 'admin_test_attend@example.com');
$pilgrimUser = createTestUser('PILGRIM', 'pilgrim_test_attend@example.com');
$otherUser = createTestUser('PILGRIM', 'other_test_attend@example.com');

$supervisorToken = $supervisor->createToken('test-sup')->plainTextToken;
$adminToken = $admin->createToken('test-admin')->plainTextToken;
$pilgrimToken = $pilgrimUser->createToken('test-pilgrim')->plainTextToken;

// Metadata
$package = Package::create([
    'package_name' => 'Test Pkg',
    'price' => 100,
    'duration_days' => 1,
    'description' => 'Test',
    'is_active' => true
]);

$trip = Trip::create([
    'trip_name' => 'Test Trip',
    'start_date' => now(),
    'end_date' => now()->addDays(5),
    'price' => 100,
    'status' => 'PLANNED',
    'package_id' => $package->package_id
]);

try {
    $pilgrim = Pilgrim::create([
        'user_id' => $pilgrimUser->user_id,
        'passport_name' => 'Pilgrim One',
        'passport_number' => 'P123456',
        'nationality' => 'Test',
        'date_of_birth' => '1990-01-01',
        'gender' => 'MALE'
    ]);
} catch (\Exception $e) {
    $pilgrim = Pilgrim::where('user_id', $pilgrimUser->user_id)->first();
}

$payload = [
    'trip_id' => $trip->trip_id,
    'status_type' => 'ARRIVAL',
    'supervisor_note' => 'Test Note'
];

$url = "/api/pilgrims/{$pilgrim->pilgrim_id}/attendance";

// 2. Test Admin Access (Should Fail - 403)
echo "\n2. Testing ADMIN access (Expect 403)...\n";
Log::info("Testing ADMIN access...");
$response = makeRequest($app, 'POST', $url, $adminToken, $payload);
echo "Status: " . $response->getStatusCode() . "\n";
if ($response->getStatusCode() === 403) {
    echo "PASS: Admin denied.\n";
} else {
    echo "FAIL: Admin got " . $response->getStatusCode() . "\n";
}

// 3. Test Pilgrim Access (Should Fail - 403)
echo "\n3. Testing PILGRIM access (Expect 403)...\n";
Log::info("Testing PILGRIM access...");
$response = makeRequest($app, 'POST', $url, $pilgrimToken, $payload);
echo "Status: " . $response->getStatusCode() . "\n";
if ($response->getStatusCode() === 403) {
    echo "PASS: Pilgrim denied.\n";
} else {
    echo "FAIL: Pilgrim got " . $response->getStatusCode() . "\n";
}

// 4. Test Supervisor Access (Should Succeed - 201)
echo "\n4. Testing SUPERVISOR access (Expect 201)...\n";
Log::info("Testing SUPERVISOR access...");
$response = makeRequest($app, 'POST', $url, $supervisorToken, $payload);
echo "Status: " . $response->getStatusCode() . "\n";
if ($response->getStatusCode() === 201) {
    echo "PASS: Supervisor allowed.\n";
    echo "Response: " . substr($response->getContent(), 0, 100) . "...\n";
} else {
    echo "FAIL: Supervisor got " . $response->getStatusCode() . "\n";
    echo "Body: " . $response->getContent() . "\n";
}


// Cleanup
echo "\nCleaning up...\n";
AttendanceTracking::where('pilgrim_id', $pilgrim->pilgrim_id)->delete();
if ($pilgrim)
    $pilgrim->delete();
if ($package)
    $package->delete(); // cascading logic might delete trips
if ($trip)
    $trip->delete();
// Users kept for next runs or manual cleanup if needed
echo "=== Test Complete ===\n";
