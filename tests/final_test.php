<?php
// ... imports ...
use App\Models\User;
use App\Models\Pilgrim;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "Starting Final Verification...\n";

DB::beginTransaction();

try {
    // 1. Setup Data
    echo "Creating Trip...\n";
    $trip = Trip::create([
        'trip_name' => 'Demo Trip',
        'start_date' => '2025-05-01',
        'end_date' => '2025-05-10',
        'status' => 'PLANNED'
    ]);
    echo "Trip OK.\n";

    echo "Creating User...\n";
    $user = User::create([
        'full_name' => 'Demo Pilgrim',
        'email' => 'demo_' . time() . '@test.com',
        'password' => bcrypt('123456'),
        'role' => 'PILGRIM',
        // Add likely required fields just in case
        'phone_number' => '1234567890',
    ]);
    echo "User OK.\n";

    echo "Creating Pilgrim...\n";
    $pilgrim = Pilgrim::create([
        'user_id' => $user->user_id,
        'passport_number' => 'D' . time(),
        'passport_name' => 'Demo Pilgrim',
        'nationality' => 'Test',
        'gender' => 'MALE'
    ]);
    echo "Pilgrim OK.\n";

    // Insert attendance manually
    $attId = DB::table('attendance_tracking')->insertGetId([
        'pilgrim_id' => $pilgrim->pilgrim_id,
        'trip_id' => $trip->trip_id,
        'status_type' => 'ARRIVAL',
        'timestamp' => now(),
        'activity_id' => null
    ]);

    echo "Created Pilgrim ID: {$pilgrim->pilgrim_id}, Attendance ID: {$attId}\n";

    // 2. Execute Controller
    $controller = new \App\Http\Controllers\Api\PilgrimController();
    $response = $controller->index();
    $data = json_decode($response->getContent(), true);

    // 3. Inspect Result
    $found = false;
    foreach ($data['pilgrims'] as $p) {
        if ($p['pilgrim_id'] == $pilgrim->pilgrim_id) {
            $found = true;
            echo "\nFound Pilgrim in Response:\n";
            echo "ID: " . $p['pilgrim_id'] . "\n";

            if (isset($p['latest_attendance'])) {
                echo "Latest Attendance: FOUND\n";
                // Only print essential info to avoid large output truncation
                echo "Att ID: " . $p['latest_attendance']['attendance_id'] . "\n";
                echo "Status: " . $p['latest_attendance']['status_type'] . "\n";

                if ($p['latest_attendance']['attendance_id'] == $attId) {
                    echo "\nTIMESTAMPDATA MATCHED! Test PASSED.\n";
                } else {
                    echo "\nID Mismatch! Test FAILED.\n";
                }
            } else {
                echo "Latest Attendance: MISSING! Test FAILED.\n";
            }
            break;
        }
    }

    if (!$found) {
        echo "Pilgrim not found in response.\n";
    }

} catch (\Throwable $e) {
    // Write error to file to avoid truncation
    file_put_contents('test_error.log', $e->getMessage() . "\n" . $e->getTraceAsString());
    echo "Error occurred! See test_error.log\n";
    echo substr($e->getMessage(), 0, 100) . "\n";
} finally {
    DB::rollBack();
    echo "\nCleaned up (Rollback).\n";
}
