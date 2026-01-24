<?php

use App\Models\User;
use App\Models\Pilgrim;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "Starting verification...\n";

DB::beginTransaction();

try {
    // 1. Create Trip
    $trip = Trip::create([
        'trip_name' => 'Test Trip ' . time(),
        'start_date' => '2025-01-01',
        'end_date' => '2025-01-05',
    ]);
    echo "Created Trip ID: " . $trip->trip_id . "\n";

    // 2. Create User & Pilgrim
    $user = User::create([
        'name' => 'Test Pilgrim',
        'email' => 'testpilgrim_' . time() . '@example.com',
        'password' => bcrypt('password'),
        'role' => 'PILGRIM'
    ]);

    $pilgrim = Pilgrim::create([
        'user_id' => $user->user_id,
        'passport_number' => 'TEST' . time(),
        'passport_name' => 'Test Pilgrim',
        'nationality' => 'Testland',
        'gender' => 'MALE'
    ]);

    echo "Created Pilgrim ID: " . $pilgrim->pilgrim_id . "\n";

    // 3. Add Attendance Record via DB
    $attendanceId = DB::table('attendance_tracking')->insertGetId([
        'pilgrim_id' => $pilgrim->pilgrim_id,
        'trip_id' => $trip->trip_id,
        'activity_id' => null,
        'status_type' => 'ARRIVAL',
        'timestamp' => now(),
    ]);

    echo "Created Attendance ID (DB): " . $attendanceId . "\n";

    // 4. Call Controller Method
    $response = (new \App\Http\Controllers\Api\PilgrimController)->index();

    // 5. Verify Response
    $content = $response->getContent();
    $data = json_decode($content, true);

    if (!isset($data['pilgrims'])) {
        throw new Exception("Response structure incorrect. Key 'pilgrims' missing.");
    }

    $found = false;
    foreach ($data['pilgrims'] as $p) {
        if ($p['pilgrim_id'] == $pilgrim->pilgrim_id) {
            $found = true;
            if (isset($p['latest_attendance'])) {
                if ($p['latest_attendance']['attendance_id'] == $attendanceId) {
                    echo "SUCCESS: Pilgrim found with correct latest_attendance.\n";
                } else {
                    echo "FAILURE: Pilgrim found, latest_attendance present but ID mismatch. Wanted $attendanceId, got " . $p['latest_attendance']['attendance_id'] . "\n";
                }
            } else {
                echo "FAILURE: Pilgrim found but latest_attendance is missing.\n";
            }
            break;
        }
    }

    if (!$found) {
        throw new Exception("FAILURE: Created pilgrim not found in list.");
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
} finally {
    DB::rollBack();
    echo "Transaction rolled back.\n";
}
