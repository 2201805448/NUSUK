<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Trip;
use App\Models\Pilgrim;
use App\Models\Package;
use App\Models\SupervisorNote;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

// 1. Create Supervisor
$supervisor = User::where('email', 'supervisor_notes@example.com')->first();
if (!$supervisor) {
    $supervisor = User::create([
        'full_name' => 'Supervisor Notes',
        'email' => 'supervisor_notes@example.com',
        'phone_number' => '9998887770',
        'password' => Hash::make('password'),
        'role' => 'SUPERVISOR',
    ]);
}
$token = $supervisor->createToken('test-token')->plainTextToken;

// 2. Create Package
$package = Package::create([
    'package_name' => 'Test Package Notes',
    'price' => 3000,
    'duration_days' => 7,
    'description' => 'Test Description',
    'is_active' => true
]);

// 3. Create Trip
$trip = Trip::create([
    'trip_name' => 'Test Trip Notes',
    'start_date' => '2025-06-01',
    'end_date' => '2025-06-08',
    'price' => 1000,
    'status' => 'PLANNED',
    'package_id' => $package->package_id
]);

// 4. Create Pilgrim
// Checking Pilgrim Model for required fields. Assuming similar to User or specific entries.
// Pilgrim usually linked to a User or standalone. Checking migration 'create_pilgrims_table' would be ideal but I'll guess standard fields or use try-catch to debug.
// Wait, I saw Pilgrim.php earlier in file listing. Let's assume some basics or check database if it fails.
// Based on typical schema, Pilgrim might be just a profile or a user.
// Let's create a User first to be the pilgrim, OR if Pilgrim table is separate.
// "BookingAttendee" was open in user context.
// Let's look at `Pilgrim` model briefly if I can, but I'll try to create one based on common sense if not.
// Actually, `Pilgrim` model was in list. Let's blindly try creating one with minimal fields, or check `create_pilgrims_table` if it fails.
// To be safe, I will use a dummy creation and let the error guide me if I'm wrong.
// But better: I'll peek at the migration first in the next tool call if I wasn't running this script immediately.
// Since I'm writing the script now, I'll add a snippet to inspect Schema columns if I could, but I can't interactively.
// I'll assume it needs a user_id or is a user.
// Let's try to create a User for the pilgrim first.
$pilgrimUser = User::where('email', 'pilgrim_test@example.com')->first();
if (!$pilgrimUser) {
    $pilgrimUser = User::create([
        'full_name' => 'Pilgrim Test',
        'email' => 'pilgrim_test@example.com',
        'phone_number' => '1112223334',
        'password' => Hash::make('password'),
        'role' => 'PILGRIM',
    ]);
}

// Attempt to create Pilgrim record linked to User (if that's how it works) or just use the User ID if Pilgrim IS the User.
// Logic: If Pilgrim extends Model, it has its own table.
// I'll assume 'pilgrims' table has 'user_id' FK given typical design.
try {
    $pilgrim = Pilgrim::create([
        'user_id' => $pilgrimUser->user_id,
        'passport_name' => 'Pilgrim Passport Name',
        'passport_number' => 'A12345678',
        'nationality' => 'Saudi',
        'date_of_birth' => '1990-01-01',
        'gender' => 'MALE'
    ]);
} catch (\Exception $e) {
    echo "Pilgrim creation failed, might need different fields. Error: " . $e->getMessage() . "\n";
    // Fallback or exit
}

// 5. Add Note
$request = Request::create('/api/pilgrims/' . $pilgrim->pilgrim_id . '/notes', 'POST', [
    'trip_id' => $trip->trip_id,
    'note_type' => 'BEHAVIORAL',
    'note_text' => 'Pilgrim is very cooperative.'
]);
$request->headers->set('Authorization', 'Bearer ' . $token);
$request->headers->set('Accept', 'application/json');

$response = $app->handle($request);

echo "Add Note Response Status: " . $response->getStatusCode() . "\n";
echo "Response Body: " . $response->getContent() . "\n";

if ($response->getStatusCode() === 201) {
    echo "SUCCESS: Note added.\n";
} else {
    echo "FAILURE: Note not added.\n";
}

// Clean up
SupervisorNote::where('pilgrim_id', $pilgrim->pilgrim_id)->delete();
$pilgrim->delete();
$pilgrimUser->delete();
$trip->delete();
$package->delete();
// $supervisor->delete();
