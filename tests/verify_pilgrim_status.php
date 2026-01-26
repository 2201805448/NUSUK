<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    // Clean up
    Schema::disableForeignKeyConstraints();
    DB::table('attendance_tracking')->truncate();
    DB::table('activities')->truncate();
    DB::table('trips')->truncate();
    DB::table('pilgrims')->truncate();
    DB::table('users')->truncate();
    Schema::enableForeignKeyConstraints();

    echo "Database truncated.\n";

    // 1. Create User
    $userId = DB::table('users')->insertGetId([
        'full_name' => 'Test User',
        'email' => 'test@example.com',
        'phone_number' => '1234567890',
        'password' => bcrypt('password'),
        'role' => 'PILGRIM',
        'account_status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "User created: {$userId}\n";

    // 2. Create Pilgrim
    $pilgrimId = DB::table('pilgrims')->insertGetId([
        'user_id' => $userId,
        'passport_number' => 'A1234567',
        'passport_name' => 'Test Pilgrim',
        'nationality' => 'Saudi', // Required field
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "Pilgrim created: {$pilgrimId}\n";

    // 3. Create Trip
    $tripId = DB::table('trips')->insertGetId([
        'trip_name' => 'Test Trip',
        'status' => 'PLANNED',
        'start_date' => now(),
        'end_date' => now()->addDays(5),
    ]);
    echo "Trip created: {$tripId}\n";

    // 4. Create Activity
    $activityId = DB::table('activities')->insertGetId([
        'trip_id' => $tripId,
        'activity_type' => 'VISIT',
        'location' => 'Mecca',
        'activity_date' => now(),
        'activity_time' => '12:00:00',
        'status' => 'SCHEDULED',
    ]);
    echo "Activity created: {$activityId}\n";

    // 5. Create Attendance
    DB::table('attendance_tracking')->insert([
        'pilgrim_id' => $pilgrimId,
        'trip_id' => $tripId,
        'activity_id' => $activityId,
        'status_type' => 'ARRIVAL', // Valid enum value
        'timestamp' => now(),
    ]);
    echo "Attendance created.\n";

    // 6. Test Controller Logic
    $controller = new \App\Http\Controllers\Api\PilgrimController();
    $response = $controller->index();
    $data = $response->getData(true);

    if (empty($data['pilgrims'])) {
        echo "FAILURE: No pilgrims returned.\n";
        exit(1);
    }

    $p = $data['pilgrims'][0];

    // Verify status_type
    if (isset($p['status_type']) && $p['status_type'] === 'ARRIVAL') {
        echo "SUCCESS: status_type present and correct.\n";
        exit(0);
    } else {
        echo "FAILURE: status_type missing or incorrect.\n";
        print_r($p);
        exit(1);
    }

} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
} catch (\Throwable $e) {
    echo "THROWABLE: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}
