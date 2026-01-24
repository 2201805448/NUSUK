<?php

use App\Models\Pilgrim;
use App\Models\AttendanceTracking;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
try {
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->handle(Illuminate\Http\Request::capture());
} catch (\Exception $e) { /* ignore */
}

echo "Checking Database Consistency...\n";

// 1. Check ID definition in Database
$pilgrimTable = DB::select('SHOW COLUMNS FROM pilgrims WHERE Field = "pilgrim_id"')[0];
$attendanceTable = DB::select('SHOW COLUMNS FROM attendance_tracking WHERE Field = "pilgrim_id"')[0];

echo "Pilgrim PK Type: " . $pilgrimTable->Type . "\n";
echo "Attendance FK Type: " . $attendanceTable->Type . "\n";

// 2. Scan existing data for mismatches
$orphans = DB::table('attendance_tracking')
    ->leftJoin('pilgrims', 'attendance_tracking.pilgrim_id', '=', 'pilgrims.pilgrim_id')
    ->whereNull('pilgrims.pilgrim_id')
    ->count();

echo "Orphaned Attendance Records: $orphans\n";

// 3. Test Relationship Implementation via Model
$pilgrim = Pilgrim::first();
if ($pilgrim) {
    echo "Found Pilgrim ID: " . $pilgrim->pilgrim_id . "\n";

    // Check if attendance exists in DB
    $hasAttendance = DB::table('attendance_tracking')->where('pilgrim_id', $pilgrim->pilgrim_id)->exists();
    echo "Has Attendance in DB? " . ($hasAttendance ? 'YES' : 'NO') . "\n";

    if (!$hasAttendance) {
        echo "Seeding attendance for testing...\n";
        // Need trip_id. check if any trip exists or create one.
        $tripId = DB::table('trips')->value('trip_id');
        if (!$tripId) {
            $tripId = DB::table('trips')->insertGetId([
                'trip_name' => 'Auto Seed Trip',
                'start_date' => now(),
                'end_date' => now()->addDays(5),
                'status' => 'PLANNED'
            ]);
        }

        DB::table('attendance_tracking')->insert([
            'pilgrim_id' => $pilgrim->pilgrim_id,
            'trip_id' => $tripId,
            'status_type' => 'ARRIVAL',
            'timestamp' => now()
        ]);
        echo "Seeded Attendance.\n";
    }

    $pilgrim->refresh(); // Refresh model
    // re-load relation
    $pilgrim->load('latestAttendance');
    $relation = $pilgrim->latestAttendance;

    if ($relation) {
        echo "Relationship Result: FOUND (ID: " . $relation->attendance_id . ")\n";
        print_r($relation->toArray());
    } else {
        echo "Relationship Result: NULL (Reproduced!)\n";

        // Debugging the query
        DB::enableQueryLog();
        $pilgrim = Pilgrim::with('latestAttendance')->find($pilgrim->pilgrim_id);
        print_r(DB::getQueryLog());
    }
} else {
    echo "No pilgrims found in DB to test.\n";
}
