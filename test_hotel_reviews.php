<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Trip;
use App\Models\Package;
use App\Models\Evaluation;
use App\Models\Accommodation;
use App\Models\Pilgrim;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo "Setting up data for Hotel Reviews Test...\n";

// 1. Create User & Token
$user = User::where('email', 'test_user@example.com')->first();
if (!$user) {
    $user = User::create([
        'full_name' => 'Test User',
        'email' => 'test_user@example.com',
        'password' => bcrypt('password'),
        'role' => 'USER',
        'phone_number' => '1234567890',
    ]);
}
$token = $user->createToken('test_token')->plainTextToken;

// 2. Create Package & Trip
$package = Package::create([
    'package_name' => 'Review Test Package',
    'price' => 1000,
    'duration_days' => 5,
    'description' => 'Test',
    'is_active' => true,
]);

$trip = Trip::create([
    'package_id' => $package->package_id,
    'trip_name' => 'Review Test Trip',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+5 days')),
    'status' => 'PLANNED',
]);

// 3. Create Hotel & Link to Trip
$hotel = Accommodation::create([
    'hotel_name' => 'Review Test Hotel',
    'city' => 'Makkah',
    'room_type' => 'Double',
    'capacity' => 100,
]);
$trip->accommodations()->attach($hotel->accommodation_id);

// 4. Create Pilgrim (needed for Evaluation)
$pilgrim = Pilgrim::create([
    'user_id' => $user->user_id,
    'passport_name' => 'Test Pilgrim',
    'passport_number' => 'X123456',
    'nationality' => 'Test',
    //'contact_number' => '123123', // Not in table based on migration view
]);

// 5. Create Reviews (One Public, One Internal)
Evaluation::create([
    'pilgrim_id' => $pilgrim->pilgrim_id,
    'type' => 'HOTEL',
    'target_id' => $hotel->accommodation_id,
    'score' => 5,
    'concern_text' => 'Great hotel! Public review.',
    'internal_only' => 0, // Public
]);

Evaluation::create([
    'pilgrim_id' => $pilgrim->pilgrim_id,
    'type' => 'HOTEL',
    'target_id' => $hotel->accommodation_id,
    'score' => 1,
    'concern_text' => 'Bad food. Internal complaint.',
    'internal_only' => 1, // Private
]);

echo "Data setup complete.\n";

// 6. Fetch Hotel Reviews
echo "Fetching Hotel Reviews...\n";
$req = Request::create("/api/trips/{$trip->trip_id}/hotel-reviews", 'GET');
$req->headers->set('Authorization', 'Bearer ' . $token);
$req->headers->set('Accept', 'application/json');
$res = $app->handle($req);

echo "Status: " . $res->getStatusCode() . "\n";
echo "Body: " . $res->getContent() . "\n";

if ($res->getStatusCode() === 200) {
    $reviews = json_decode($res->getContent(), true);
    // Should see Public review, should NOT see Internal review
    $foundPublic = false;
    $foundInternal = false;

    foreach ($reviews as $hotelData) {
        foreach ($hotelData['reviews'] as $r) {
            if (strpos($r['concern_text'], 'Public review') !== false) {
                $foundPublic = true;
            }
            if (strpos($r['concern_text'], 'Internal complaint') !== false) {
                $foundInternal = true;
            }
        }
    }

    if ($foundPublic && !$foundInternal) {
        echo "SUCCESS: Only public reviews retrieved.\n";
    } else {
        echo "FAILURE: Incorrect reviews retrieved (Public: " . ($foundPublic ? 'Yes' : 'No') . ", Internal: " . ($foundInternal ? 'Yes' : 'No') . ")\n";
    }
} else {
    echo "FAILURE: Request failed.\n";
}

// Cleanup
$trip->delete();
$package->delete();
$hotel->delete(); // Cascade handles link? Check migration.
// If not cascade on hotel delete, manually detach. But trip link has on delete cascade. Link table yes.
// Evaluation has cascade on pilgrim delete.
$pilgrim->delete();
