<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Trip;
use App\Models\Package;
use App\Models\Evaluation;
use App\Models\Pilgrim;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo "Setting up data for Trip Reviews Test...\n";

// 1. Create User & Token
$user = User::where('email', 'test_user_trip_reviews@example.com')->first();
if (!$user) {
    $user = User::create([
        'full_name' => 'Test User',
        'email' => 'test_user_trip_reviews@example.com',
        'password' => bcrypt('password'),
        'role' => 'USER',
        'phone_number' => '1234567890',
    ]);
}
$token = $user->createToken('test_token')->plainTextToken;

// 2. Create Package
$package = Package::create([
    'package_name' => 'Trip Review Package',
    'price' => 1000,
    'duration_days' => 5,
    'description' => 'Test',
    'is_active' => true,
]);

// 3. Create 2 Trips for this Package
$trip1 = Trip::create([
    'package_id' => $package->package_id,
    'trip_name' => 'Trip A',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+5 days')),
    'status' => 'COMPLETED',
]);

$trip2 = Trip::create([
    'package_id' => $package->package_id,
    'trip_name' => 'Trip B',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+5 days')),
    'status' => 'COMPLETED',
]);

// 4. Create Pilgrim
$pilgrim = Pilgrim::create([
    'user_id' => $user->user_id,
    'passport_name' => 'Test Pilgrim',
    'passport_number' => 'X999999',
    'nationality' => 'Test',
    'passport_img' => 'test_img.jpg',
]);

// 5. Create Reviews
// Review for Trip A (Public)
Evaluation::create([
    'pilgrim_id' => $pilgrim->pilgrim_id,
    'type' => 'TRIP',
    'target_id' => $trip1->trip_id,
    'score' => 5,
    'concern_text' => 'Trip A was amazing! Public.',
    'internal_only' => 0,
]);

// Review for Trip B (Internal)
Evaluation::create([
    'pilgrim_id' => $pilgrim->pilgrim_id,
    'type' => 'TRIP',
    'target_id' => $trip2->trip_id,
    'score' => 2,
    'concern_text' => 'Trip B had issues. Internal.',
    'internal_only' => 1,
]);

// Review for Trip A (Public, Another one)
Evaluation::create([
    'pilgrim_id' => $pilgrim->pilgrim_id,
    'type' => 'TRIP',
    'target_id' => $trip1->trip_id,
    'score' => 4,
    'concern_text' => 'Also loved Trip A.',
    'internal_only' => 0,
]);

echo "Data setup complete.\n";

// 6. Fetch Package Reviews (Trip Reviews aggregated)
echo "Fetching Trip Reviews for Package...\n";
$req = Request::create("/api/packages/{$package->package_id}/reviews", 'GET');
$req->headers->set('Authorization', 'Bearer ' . $token);
$req->headers->set('Accept', 'application/json');
$res = $app->handle($req);

echo "Status: " . $res->getStatusCode() . "\n";
echo "Body: " . $res->getContent() . "\n";

if ($res->getStatusCode() === 200) {
    $data = json_decode($res->getContent(), true);
    $reviews = $data['reviews'];

    // Should see Public review for Trip A, nothing for Trip B (internal)
    $foundPublicA = false;
    $foundInternalB = false;
    $count = 0;

    foreach ($reviews as $r) {
        $count++;
        if (strpos($r['concern_text'], 'Trip A was amazing') !== false) {
            $foundPublicA = true;
        }
        if (strpos($r['concern_text'], 'Trip B had issues') !== false) {
            $foundInternalB = true;
        }
    }

    if ($foundPublicA && !$foundInternalB && $count >= 2) {
        echo "SUCCESS: Correct reviews retrieved.\n";
    } else {
        echo "FAILURE: Incorrect reviews. (Found Public A: $foundPublicA, Found Internal B: $foundInternalB, Count: $count)\n";
    }

} else {
    echo "FAILURE: Request failed.\n";
}

// Cleanup
Evaluation::where('pilgrim_id', $pilgrim->pilgrim_id)->delete();
$trip1->delete();
$trip2->delete();
$package->delete();
$pilgrim->delete();
// User keep or delete? Keep to avoid constraint issues if token used elsewhere. 
