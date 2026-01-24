<?php
use App\Models\User;
use App\Models\Pilgrim;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "Debug: DB Insert test\n";

DB::beginTransaction();
try {
    $tripId = DB::table('trips')->insertGetId([
        'trip_name' => 'Test Trip ' . time(),
        'start_date' => '2025-01-01',
        'end_date' => '2025-01-05',
        'status' => 'PLANNED'
    ]);
    echo "Trip ID via DB: $tripId\n";

    $userId = DB::table('users')->insertGetId([
        'name' => 'Test Pilgrim',
        'email' => 'testpilgrim_db_' . time() . '@example.com',
        'password' => bcrypt('password'),
        'role' => 'PILGRIM'
    ]);

    $pilgrimId = DB::table('pilgrims')->insertGetId([
        'user_id' => $userId,
        //'passport_number' => 'TEST' . time(), // if nullable
        'passport_number' => 'T' . time(), // ensure length safe
        'passport_name' => 'Test',
        'nationality' => 'T',
        'gender' => 'MALE',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "Pilgrim ID via DB: $pilgrimId\n";

    $attendanceId = DB::table('attendance_tracking')->insertGetId([
        'pilgrim_id' => $pilgrimId,
        'trip_id' => $tripId,
        'activity_id' => null,
        'status_type' => 'ARRIVAL',
        'timestamp' => now()
    ]);
    echo "Attendance ID via DB: $attendanceId\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    DB::rollBack();
    echo "Rolled back.\n";
}
