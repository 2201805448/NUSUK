<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Trip;
use App\Models\Package;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\TripUpdate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

// 1. Setup Data
$supervisor = User::where('email', 'sup_updates@example.com')->first();
if (!$supervisor) {
    $supervisor = User::create([
        'full_name' => 'Supervisor Updates',
        'email' => 'sup_updates@example.com',
        'phone_number' => '1112224444',
        'password' => Hash::make('password'),
        'role' => 'SUPERVISOR',
    ]);
}
$token = $supervisor->createToken('test-token')->plainTextToken;

$package = Package::create([
    'package_name' => 'Pkg Updates',
    'price' => 1000,
    'duration_days' => 5,
    'description' => 'Test',
    'is_active' => true
]);

$trip = Trip::create([
    'trip_name' => 'Trip Updates Test',
    'start_date' => '2025-10-01',
    'end_date' => '2025-10-05',
    'price' => 1000,
    'status' => 'PLANNED',
    'package_id' => $package->package_id
]);

$user = User::where('email', 'user_updates@example.com')->first();
if (!$user) {
    $user = User::create([
        'full_name' => 'User Updates',
        'email' => 'user_updates@example.com',
        'phone_number' => '1112224445',
        'password' => Hash::make('password'),
        'role' => 'USER',
    ]);
}

$booking = Booking::create([
    'user_id' => $user->user_id,
    'package_id' => $package->package_id,
    'trip_id' => $trip->trip_id,
    'booking_ref' => 'REFUPD',
    'booking_date' => now(),
    'total_price' => 1000,
    'pay_method' => 'CASH',
    'status' => 'CONFIRMED'
]);

// 2. Post Trip Update
echo "Posting Trip Update...\n";
$req = Request::create('/api/trips/' . $trip->trip_id . '/updates', 'POST', [
    'title' => 'Schedule Change',
    'message' => 'Departure delayed by 1 hour.'
]);
$req->headers->set('Authorization', 'Bearer ' . $token);
$req->headers->set('Accept', 'application/json');

$res = $app->handle($req);

echo "Post Response: " . $res->getStatusCode() . "\n";
echo "Body: " . $res->getContent() . "\n";

// 3. Verify Notification
$notif = Notification::where('user_id', $user->user_id)->where('title', 'Trip Update: Schedule Change')->first();
if ($notif)
    echo "SUCCESS: Notification received.\n";
else
    echo "FAILURE: Notification not received.\n";

// 4. Verify Update Feed/History
echo "Fetching Update Feed...\n";
$req2 = Request::create('/api/trips/' . $trip->trip_id . '/updates', 'GET');
$req2->headers->set('Authorization', 'Bearer ' . $token); // Or User token
$req2->headers->set('Accept', 'application/json');

$res2 = $app->handle($req2);

echo "Feed Response: " . $res2->getStatusCode() . "\n";
$feed = $res2->getContent();
echo "Feed Body: " . substr($feed, 0, 100) . "...\n";

if (strpos($feed, 'Schedule Change') !== false) {
    echo "SUCCESS: Update found in feed.\n";
} else {
    echo "FAILURE: Update not found in feed.\n";
}

// Cleanup
Notification::where('user_id', $user->user_id)->delete();
TripUpdate::where('trip_id', $trip->trip_id)->delete();
$booking->delete();
$trip->delete();
$package->delete();
$user->delete(); // Optional
