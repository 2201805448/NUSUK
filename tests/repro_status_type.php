<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Pilgrim;
use App\Models\User;
use App\Models\AttendanceTracking;
use Illuminate\Support\Facades\DB;

// Mock data or find existing
$pilgrim = Pilgrim::first();

if (!$pilgrim) {
    // Create dummy if none exists (just for structure check, don't save to avoid clutter if possible, or use transaction)
    echo "No pilgrim found, creating dummy in transaction...\n";
    DB::beginTransaction();

    $user = User::create([
        'name' => 'Repro User',
        'email' => 'repro_' . time() . '@example.com',
        'password' => bcrypt('password'),
        'role' => 'PILGRIM'
    ]);

    $pilgrim = Pilgrim::create([
        'user_id' => $user->user_id,
        'passport_number' => 'P' . time(),
        'first_name' => 'Test',
        'last_name' => 'Pilgrim',
        'gender' => 'MALE',
        'nationality' => 'SA'
    ]);

    AttendanceTracking::create([
        'pilgrim_id' => $pilgrim->pilgrim_id,
        'status_type' => 'ARRIVAL',
        'timestamp' => now(),
        'trip_id' => 1, // assumption
        'activity_id' => 1 // assumption
    ]);

    // logic from controller
    $pilgrim = Pilgrim::with('latestAttendance')->find($pilgrim->pilgrim_id);
    $pilgrim->status_type = $pilgrim->latestAttendance ? $pilgrim->latestAttendance->status_type : 'NONE';

    echo "JSON Output:\n";
    echo json_encode($pilgrim) . "\n";

    DB::rollBack();
} else {
    // Use existing
    $pilgrim = Pilgrim::with('latestAttendance')->find($pilgrim->pilgrim_id);
    $pilgrim->status_type = $pilgrim->latestAttendance ? $pilgrim->latestAttendance->status_type : 'NONE_EXISTING';

    echo "JSON Output (Existing Pilgrim):\n";
    echo json_encode($pilgrim) . "\n";
}
