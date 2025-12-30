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

// 1. Create Supervisor
$supervisor = User::where('email', 'supervisor_attendance@example.com')->first();
if (!$supervisor) {
    $supervisor = User::create([
        'full_name' => 'Supervisor Attendance',
        'email' => 'supervisor_attendance@example.com',
        'phone_number' => '5554443332',
        'password' => Hash::make('password'),
        'role' => 'SUPERVISOR',
    ]);
}
$token = $supervisor->createToken('test-token')->plainTextToken;

// 2. Create Package
$package = Package::create([
    'package_name' => 'Test Package Attendance',
    'price' => 2000,
    'duration_days' => 5,
    'description' => 'Test',
    'is_active' => true
]);

// 3. Create Trip
$trip = Trip::create([
    'trip_name' => 'Test Trip Attendance',
    'start_date' => '2025-07-01',
    'end_date' => '2025-07-06',
    'price' => 1000,
    'status' => 'PLANNED',
    'package_id' => $package->package_id
]);

// 4. Create Activity
$activity = Activity::create([
    'trip_id' => $trip->trip_id,
    'activity_type' => 'VISIT',
    'location' => 'Madinah',
    'activity_date' => '2025-07-02',
    'activity_time' => '08:00:00',
    'status' => 'SCHEDULED',
]);

// 5. Create Pilgrim User & Pilgrim
$pilgrimUser = User::where('email', 'pilgrim_attend@example.com')->first();
if (!$pilgrimUser) {
    $pilgrimUser = User::create([
        'full_name' => 'Pilgrim MakeAttendance',
        'email' => 'pilgrim_attend@example.com',
        'phone_number' => '1110001110',
        'password' => Hash::make('password'),
        'role' => 'PILGRIM',
    ]);
}

try {
    $pilgrim = Pilgrim::create([
        'user_id' => $pilgrimUser->user_id,
        'passport_name' => 'Pilgrim Attendance Name',
        'passport_number' => 'B98765432',
        'nationality' => 'Egypt',
        'date_of_birth' => '1985-05-05',
        'gender' => 'FEMALE'
    ]);
} catch (\Exception $e) {
    // If pilgrim exists (due to user_id constraint), fetch it
    $pilgrim = Pilgrim::where('user_id', $pilgrimUser->user_id)->first();
}


// 6. Record Arrival
echo "Recording ARRIVAL...\n";
$request = Request::create('/api/pilgrims/' . $pilgrim->pilgrim_id . '/attendance', 'POST', [
    'trip_id' => $trip->trip_id,
    'activity_id' => $activity->activity_id,
    'status_type' => 'ARRIVAL',
    'supervisor_note' => 'Arrived on time.'
]);
$request->headers->set('Authorization', 'Bearer ' . $token);
$request->headers->set('Accept', 'application/json');

$response = $app->handle($request);

echo "Arrival Response: " . $response->getStatusCode() . "\n";
echo "Body: " . $response->getContent() . "\n";

// 7. Record Departure
echo "Recording DEPARTURE...\n";
$request2 = Request::create('/api/pilgrims/' . $pilgrim->pilgrim_id . '/attendance', 'POST', [
    'trip_id' => $trip->trip_id,
    'activity_id' => $activity->activity_id,
    'status_type' => 'DEPARTURE',
    'supervisor_note' => 'Left for hotel.'
]);
$request2->headers->set('Authorization', 'Bearer ' . $token);
$request2->headers->set('Accept', 'application/json');

$response2 = $app->handle($request2);

echo "Departure Response: " . $response2->getStatusCode() . "\n";

// Cleanup
AttendanceTracking::where('pilgrim_id', $pilgrim->pilgrim_id)->delete();
$pilgrim->delete();
$pilgrimUser->delete();
$activity->delete();
$trip->delete();
$package->delete();
