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
use App\Models\GroupTrip;
use App\Models\GroupMember;
use App\Models\Pilgrim;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

// 1. Setup Admin & Supervisor
$admin = User::where('email', 'admin_notif@example.com')->first();
if (!$admin) {
    $admin = User::create([
        'full_name' => 'Admin Notif',
        'email' => 'admin_notif@example.com',
        'phone_number' => '1112223330',
        'password' => Hash::make('password'),
        'role' => 'ADMIN',
    ]);
}
$adminToken = $admin->createToken('admin-token')->plainTextToken;

$supervisor = User::where('email', 'sup_notif@example.com')->first();
if (!$supervisor) {
    $supervisor = User::create([
        'full_name' => 'Supervisor Notif',
        'email' => 'sup_notif@example.com',
        'phone_number' => '1112223331',
        'password' => Hash::make('password'),
        'role' => 'SUPERVISOR',
    ]);
}
$supToken = $supervisor->createToken('sup-token')->plainTextToken;

// 2. Setup User/Trip/Booking
$user = User::where('email', 'user_notif@example.com')->first();
if (!$user) {
    $user = User::create([
        'full_name' => 'User Notif',
        'email' => 'user_notif@example.com',
        'phone_number' => '1112223332',
        'password' => Hash::make('password'),
        'role' => 'USER', // or PILGRIM
    ]);
}

$package = Package::create([
    'package_name' => 'Pkg Notif',
    'price' => 1000,
    'duration_days' => 5,
    'description' => 'Test',
    'is_active' => true
]);

$trip = Trip::create([
    'trip_name' => 'Trip Notif',
    'start_date' => '2025-09-01',
    'end_date' => '2025-09-05',
    'price' => 1000,
    'status' => 'PLANNED',
    'package_id' => $package->package_id
]);

$booking = Booking::create([
    'user_id' => $user->user_id,
    'package_id' => $package->package_id,
    'trip_id' => $trip->trip_id,
    'booking_ref' => 'REF123',
    'booking_date' => now(),
    'total_price' => 1000,
    'pay_method' => 'CASH',
    'status' => 'CONFIRMED'
]);

// 3. Test General Notification (Admin)
echo "Testing General Notification (Admin)...\n";
$req1 = Request::create('/api/notifications/general', 'POST', [
    'title' => 'General Alert',
    'message' => 'This is a broadcast.'
]);
$req1->headers->set('Authorization', 'Bearer ' . $adminToken);
$req1->headers->set('Accept', 'application/json');
$res1 = $app->handle($req1);
echo "General Status: " . $res1->getStatusCode() . "\n";

// Verify user got it
$notif = Notification::where('user_id', $user->user_id)->where('title', 'General Alert')->first();
if ($notif)
    echo "SUCCESS: General Notification received.\n";
else
    echo "FAILURE: General Notification not received.\n";


// 4. Test Trip Notification (Supervisor)
echo "Testing Trip Notification (Supervisor)...\n";
$req2 = Request::create('/api/trips/' . $trip->trip_id . '/notifications', 'POST', [
    'title' => 'Trip Alert',
    'message' => 'Meeting at lobby.'
]);
$req2->headers->set('Authorization', 'Bearer ' . $supToken);
$req2->headers->set('Accept', 'application/json');
$res2 = $app->handle($req2);
echo "Trip Status: " . $res2->getStatusCode() . "\n";
echo "Response: " . $res2->getContent() . "\n";

// Verify user got it
$notif2 = Notification::where('user_id', $user->user_id)->where('title', 'Trip Alert')->first();
if ($notif2)
    echo "SUCCESS: Trip Notification received.\n";
else
    echo "FAILURE: Trip Notification not received.\n";

// Cleanup
Notification::where('user_id', $user->user_id)->delete();
$booking->delete();
$trip->delete();
$package->delete();
// Users kept or deleted
