<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Trip;
use App\Models\Activity;
use Illuminate\Support\Facades\Hash;

// 1. Create Supervisor
$supervisor = User::where('email', 'supervisor_activity@example.com')->first();
if (!$supervisor) {
    $supervisor = User::create([
        'full_name' => 'Supervisor Activity',
        'email' => 'supervisor_activity@example.com',
        'phone_number' => '1234567890',
        'password' => Hash::make('password'),
        'role' => 'SUPERVISOR',
    ]);
}
$token = $supervisor->createToken('test-token')->plainTextToken;

// 1.5 Create Package
$package = \App\Models\Package::create([
    'package_name' => 'Test Package',
    'price' => 5000,
    'duration_days' => 10,
    'description' => 'Test Description',
    'is_active' => true
]);

// 2. Create Trip and Activity
$trip = Trip::create([
    'trip_name' => 'Test Trip Activity Status',
    'start_date' => '2025-05-01',
    'end_date' => '2025-05-10',
    'price' => 1000,
    'status' => 'PLANNED',
    'package_id' => $package->package_id
]);

$activity = Activity::create([
    'trip_id' => $trip->trip_id,
    'activity_type' => 'VISIT',
    'location' => 'Makkah',
    'activity_date' => '2025-05-02',
    'activity_time' => '10:00:00',
    'status' => 'SCHEDULED',
]);

echo "Created Activity ID: {$activity->activity_id} with Status: {$activity->status}\n";

// 3. Update Status to IN_PROGRESS
// $response = HttpClient()->put('/api/activities/' . $activity->activity_id, [
//     'status' => 'IN_PROGRESS'
// ], [
//     'Authorization' => 'Bearer ' . $token,
//     'Accept' => 'application/json'
// ]);

// Helper function to simulate HTTP client if not using real HTTP
// But wait, this is a script, not request simulation helper.
// We'll use Laravel's Request facade or construct a request manually, OR simpler: use cURL or just internal code execution if we don't want to spin up server.
// Given previous patterns, I usually see `test_*.php` using direct Controller calls or artisan test.
// But `test_login.php` seems to likely use internal testing logic?
// Let's stick to using `Illuminate\Support\Facades\Http` or constructing a Request and calling the controller method.
// OR better yet: Use `app()->handle($request)`

$request = Illuminate\Http\Request::create('/api/activities/' . $activity->activity_id, 'PUT', [
    'status' => 'IN_PROGRESS'
]);
$request->headers->set('Authorization', 'Bearer ' . $token);
$request->headers->set('Accept', 'application/json');

$response = $app->handle($request);

echo "Update to IN_PROGRESS Response Status: " . $response->getStatusCode() . "\n";
echo "Response Body: " . $response->getContent() . "\n";

$activity->refresh();
echo "New Status: " . $activity->status . "\n";

if ($activity->status === 'IN_PROGRESS') {
    echo "SUCCESS: Status updated to IN_PROGRESS\n";
} else {
    echo "FAILURE: Status not updated\n";
}

// 4. Update Status to DONE
$request2 = Illuminate\Http\Request::create('/api/activities/' . $activity->activity_id, 'PUT', [
    'status' => 'DONE'
]);
$request2->headers->set('Authorization', 'Bearer ' . $token);
$request2->headers->set('Accept', 'application/json');

$response2 = $app->handle($request2);
$activity->refresh();

if ($activity->status === 'DONE') {
    echo "SUCCESS: Status updated to DONE\n";
} else {
    echo "FAILURE: Status not updated to DONE\n";
}

// Clean up
$activity->delete();
$trip->delete();
$package->delete();
// $supervisor->delete(); // Keep supervisor for future tests or delete if desired
