<?php

use App\Models\User;
use App\Models\GroupTrip;
use App\Models\Trip;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Starting Group Delete Verification...\n";

try {
    // 1. Setup Admin
    $admin = User::where('role', 'ADMIN')->first();
    if (!$admin) {
        die("Error: No Admin found.\n");
    }
    Auth::login($admin);

    // 2. Setup Trip if needed
    $trip = Trip::first();
    if (!$trip) {
        $trip = Trip::create([
            'trip_name' => 'Delete Test Trip',
            'start_date' => now(),
            'end_date' => now()->addDays(5),
            'status' => 'UPCOMING',
            'price' => 1000,
            'capacity' => 100
        ]);
    }

    // 3. Create Group to Delete
    $groupCode = 'DEL-TEST-' . uniqid();
    $group = GroupTrip::create([
        'group_code' => $groupCode,
        'trip_id' => $trip->trip_id,
        'supervisor_id' => $admin->user_id,
        'group_status' => 'ACTIVE'
    ]);

    $groupId = $group->group_id;
    echo "Created Group ID: $groupId\n";

    // 4. Call Destroy Method (Simulated)
    $controller = new \App\Http\Controllers\Api\GroupController();
    $response = $controller->destroy($groupId);

    // Check Response
    if ($response->getStatusCode() === 200) {
        echo "Response 200 OK: " . json_encode($response->getData()) . "\n";
    } else {
        echo "Response Error: " . $response->getStatusCode() . "\n";
        exit(1);
    }

    // 5. Verify Database
    $check = GroupTrip::find($groupId);
    if (!$check) {
        echo "VERIFIED: Group deleted from database.\n";
    } else {
        echo "FAILED: Group still exists in database.\n";
        exit(1);
    }

} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    exit(1);
}
