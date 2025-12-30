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

// 1. Setup Data
$supervisor = User::where('email', 'supervisor_reports@example.com')->first();
if (!$supervisor) {
    $supervisor = User::create([
        'full_name' => 'Supervisor Reports',
        'email' => 'supervisor_reports@example.com',
        'phone_number' => '1112229999',
        'password' => Hash::make('password'),
        'role' => 'SUPERVISOR',
    ]);
}
$token = $supervisor->createToken('test-token')->plainTextToken;

$package = Package::create([
    'package_name' => 'Test Package Reports',
    'price' => 2000,
    'duration_days' => 5,
    'description' => 'Test',
    'is_active' => true
]);

$trip = Trip::create([
    'trip_name' => 'Test Trip Reports',
    'start_date' => '2025-08-01',
    'end_date' => '2025-08-06',
    'price' => 1000,
    'status' => 'PLANNED',
    'package_id' => $package->package_id
]);

$activity = Activity::create([
    'trip_id' => $trip->trip_id,
    'activity_type' => 'VISIT',
    'location' => 'Mina',
    'activity_date' => '2025-08-02',
    'activity_time' => '10:00:00',
    'status' => 'SCHEDULED',
]);

$pilgrimUser = User::where('email', 'pilgrim_report@example.com')->first();
if (!$pilgrimUser) {
    $pilgrimUser = User::create([
        'full_name' => 'Pilgrim Report',
        'email' => 'pilgrim_report@example.com',
        'phone_number' => '5556667777',
        'password' => Hash::make('password'),
        'role' => 'PILGRIM',
    ]);
}

try {
    $pilgrim = Pilgrim::create([
        'user_id' => $pilgrimUser->user_id,
        'passport_name' => 'Pilgrim Report Name',
        'passport_number' => 'C12345678',
        'nationality' => 'Oman',
        'date_of_birth' => '1992-02-02',
        'gender' => 'MALE'
    ]);
} catch (\Exception $e) {
    $pilgrim = Pilgrim::where('user_id', $pilgrimUser->user_id)->first();
}

// 2. Record Attendance
AttendanceTracking::create([
    'pilgrim_id' => $pilgrim->pilgrim_id,
    'trip_id' => $trip->trip_id,
    'activity_id' => $activity->activity_id,
    'status_type' => 'ARRIVAL',
    'timestamp' => now(),
    'supervisor_id' => $supervisor->user_id,
    'supervisor_note' => 'Arrived for report'
]);

// 3. Fetch Reports
echo "Fetching Reports for Trip ID: " . $trip->trip_id . "\n";
$request = Request::create('/api/trips/' . $trip->trip_id . '/attendance-reports', 'GET');
$request->headers->set('Authorization', 'Bearer ' . $token);
$request->headers->set('Accept', 'application/json');

$response = $app->handle($request);

echo "Report Response Status: " . $response->getStatusCode() . "\n";
$content = $response->getContent();
echo "Body Preview: " . substr($content, 0, 200) . "...\n";

// Verify content
if (strpos($content, 'ARRIVAL') !== false && strpos($content, 'Pilgrim Report Name') === false) {
    // Wait, Pilgrim model has passport_name but user model has name. 
    // And I eager loaded 'pilgrim'. 'passport_name' should be in 'pilgrim' object.
}

if ($response->getStatusCode() === 200) {
    echo "SUCCESS: Reports retrieved.\n";
} else {
    echo "FAILURE: Reports retrieval failed.\n";
}

// Cleanup
AttendanceTracking::where('trip_id', $trip->trip_id)->delete();
$pilgrim->delete();
$pilgrimUser->delete();
$activity->delete();
$trip->delete();
$package->delete();
