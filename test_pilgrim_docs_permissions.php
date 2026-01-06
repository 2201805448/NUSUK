<?php

use App\Models\User;
use App\Models\Pilgrim;
use App\Models\GroupTrip;
use App\Models\GroupMember;
use App\Models\Trip;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

require __DIR__ . '/bootstrap/app.php';

// Ensure a Trip exists
$trip = Trip::firstOrCreate(
    ['trip_id' => 1],
    [
        'package_id' => 1,
        'trip_name' => 'Test Trip',
        'start_date' => '2025-01-01',
        'end_date' => '2025-01-10',
        'status' => 'UPCOMING'
    ]
);

// Create Admin User
$admin = User::firstOrCreate(
    ['email' => 'admin_test_docs@example.com'],
    [
        'full_name' => 'Admin Test Docs',
        'password' => Hash::make('password'),
        'role' => 'ADMIN',
        'phone_number' => '9999999999'
    ]
);

// Create Supervisor User
$supervisor = User::firstOrCreate(
    ['email' => 'supervisor_test_docs@example.com'],
    [
        'full_name' => 'Supervisor Test Docs',
        'password' => Hash::make('password'),
        'role' => 'SUPERVISOR',
        'phone_number' => '8888888888'
    ]
);

// Create Another Supervisor
$otherSupervisor = User::firstOrCreate(
    ['email' => 'other_sup_test_docs@example.com'],
    [
        'full_name' => 'Other Supervisor Docs',
        'password' => Hash::make('password'),
        'role' => 'SUPERVISOR',
        'phone_number' => '7777777777'
    ]
);

// Create Pilgrim User & Pilgrim Record
$pilgrimUser = User::firstOrCreate(
    ['email' => 'pilgrim_test_docs@example.com'],
    [
        'full_name' => 'Pilgrim Test Docs',
        'password' => Hash::make('password'),
        'role' => 'PILGRIM',
        'phone_number' => '6666666666'
    ]
);

$pilgrim = Pilgrim::firstOrCreate(
    ['user_id' => $pilgrimUser->user_id],
    [
        'passport_number' => 'P12345678',
        'nationality' => 'Test Country'
    ]
);

// Create Group managed by Supervisor
$group = GroupTrip::firstOrCreate(
    ['group_code' => 'GRP-DOCS-001'],
    [
        'trip_id' => $trip->trip_id,
        'supervisor_id' => $supervisor->user_id,
        'group_status' => 'ACTIVE'
    ]
);

// Add Pilgrim to Group
GroupMember::firstOrCreate(
    ['group_id' => $group->group_id, 'pilgrim_id' => $pilgrim->pilgrim_id],
    [
        'member_status' => 'ACTIVE',
        'join_date' => now()
    ]
);

function testRequest($method, $uri, $user, $data = [])
{
    // Manually setting the user for the request context is tricky in a raw script invoking the kernel handle.
    // However, Auth::login($user) should work if the middleware uses Session or if we mock it.
    // API usually uses Sanctum/Token. For this test, strictly speaking, we are invoking controller methods via route dispatch.
    // The previous script tried to use Kernel handle. 

    // Simpler approach: Resolve the controller and call the method directly acting as the user?
    // But that bypasses middleware.
    // We want to test the controller logic mainly. Middleware roles are handled in routes.
    // Access control logic IN Controller is what we are testing (Gate logic effectively).
    // The Controller uses Auth::user().

    Auth::login($user);
    $request = Request::create($uri, $method, $data);
    $request->setUserResolver(function () use ($user) {
        return $user; });

    // Dispatch via Kernel to hit the router and controller
    $app = app();
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

    // We need to capture output
    ob_start();
    $response = $kernel->handle($request);
    ob_end_clean();

    return $response;
}

echo "Testing Admin Access...\n";
$response = testRequest('GET', '/api/pilgrims/' . $pilgrim->pilgrim_id . '/documents', $admin);
echo "Admin View Status: " . $response->getStatusCode() . "\n";

$response = testRequest('PUT', '/api/pilgrims/' . $pilgrim->pilgrim_id . '/documents', $admin, ['passport_number' => 'UPDATED123']);
echo "Admin Update Status: " . $response->getStatusCode() . "\n";

echo "\nTesting Supervisor Access (Own Group)...\n";
$response = testRequest('GET', '/api/pilgrims/' . $pilgrim->pilgrim_id . '/documents', $supervisor);
echo "Supervisor View Status (Own): " . $response->getStatusCode() . "\n";

$response = testRequest('PUT', '/api/pilgrims/' . $pilgrim->pilgrim_id . '/documents', $supervisor, ['passport_number' => 'FAIL123']);
echo "Supervisor Update Status: " . $response->getStatusCode() . "\n";

echo "\nTesting Supervisor Access (Other Group)...\n";
// Create another group for other supervisor? OR just check current pilgrim who is NOT in other supervisor group.
$response = testRequest('GET', '/api/pilgrims/' . $pilgrim->pilgrim_id . '/documents', $otherSupervisor);
echo "Supervisor View Status (Other): " . $response->getStatusCode() . "\n";
