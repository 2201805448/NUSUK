<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Trip;
use App\Models\GroupTrip;
use App\Models\Package;
use App\Models\TripMessage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

// 1. Setup Data
echo "Setting up data...\n";
try {
    TripMessage::query()->delete();
    GroupTrip::query()->delete();
    Trip::where('trip_name', 'Chat Trip')->delete();
    Package::where('package_name', 'Pkg Chat')->delete();

    // Create Users
    $admin = User::firstOrCreate(['email' => 'admin_chat@example.com'], [
        'full_name' => 'Admin Chat',
        'password' => Hash::make('password'),
        'role' => 'ADMIN',
        'phone_number' => '111222333',
    ]);

    $supA = User::firstOrCreate(['email' => 'sup_a_chat@example.com'], [
        'full_name' => 'Sup A Chat',
        'password' => Hash::make('password'),
        'role' => 'SUPERVISOR',
        'phone_number' => '444555666',
    ]);

    $supB = User::firstOrCreate(['email' => 'sup_b_chat@example.com'], [
        'full_name' => 'Sup B Chat',
        'password' => Hash::make('password'),
        'role' => 'SUPERVISOR',
        'phone_number' => '777888999',
    ]);

    $pkg = Package::create([
        'package_name' => 'Pkg Chat',
        'price' => 500,
        'duration_days' => 5,
        'is_active' => true,
    ]);

    $trip = Trip::create([
        'trip_name' => 'Chat Trip',
        'package_id' => $pkg->package_id,
        'start_date' => now(),
        'end_date' => now()->addDays(5),
        'status' => 'ONGOING'
    ]);

    // Assign Sup A to Trip (via Group)
    GroupTrip::create([
        'trip_id' => $trip->trip_id,
        'supervisor_id' => $supA->user_id,
        'group_code' => 'G-CHAT-A',
        'group_status' => 'ACTIVE'
    ]);

    // Sup B is NOT assigned

} catch (\Exception $e) {
    echo "Setup Error: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Admin Sends Message
echo "Admin sending message...\n";
Auth::forgetGuards();
$tokenAdmin = $admin->createToken('admin')->plainTextToken;
$req1 = Request::create("/api/trips/{$trip->trip_id}/chat", 'POST', ['content' => 'Hello from Admin']);
$req1->headers->set('Authorization', 'Bearer ' . $tokenAdmin);
$req1->headers->set('Accept', 'application/json');
$res1 = $app->handle($req1);
echo "Admin Send Status: " . $res1->getStatusCode() . "\n";

if ($res1->getStatusCode() !== 201) {
    echo "Admin Send Failed: " . $res1->getContent() . "\n";
    exit(1);
}

// 3. Supervisor A Reads Chat
echo "Sup A reading chat...\n";
Auth::forgetGuards();
$tokenSupA = $supA->createToken('supA')->plainTextToken;
$req2 = Request::create("/api/trips/{$trip->trip_id}/chat", 'GET');
$req2->headers->set('Authorization', 'Bearer ' . $tokenSupA);
$req2->headers->set('Accept', 'application/json');
$res2 = $app->handle($req2);
echo "Sup A Read Status: " . $res2->getStatusCode() . "\n";

if (strpos($res2->getContent(), 'Hello from Admin') !== false) {
    echo "SUCCESS: Sup A sees Admin's message.\n";
} else {
    echo "FAILURE: Sup A cannot see message.\n";
}

// 4. Supervisor B access (Should fail)
echo "Sup B accessing chat (Unauthorized)...\n";
Auth::forgetGuards();
$tokenSupB = $supB->createToken('supB')->plainTextToken;
$req3 = Request::create("/api/trips/{$trip->trip_id}/chat", 'GET');
$req3->headers->set('Authorization', 'Bearer ' . $tokenSupB);
$req3->headers->set('Accept', 'application/json');
$res3 = $app->handle($req3);
echo "Sup B Status: " . $res3->getStatusCode() . "\n";

if ($res3->getStatusCode() == 403) {
    echo "SUCCESS: Sup B is correctly blocked.\n";
} else {
    echo "FAILURE: Sup B was not blocked (Status: " . $res3->getStatusCode() . ")\n";
}
