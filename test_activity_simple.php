<?php
// Force load Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Trip;
use App\Models\Activity;

try {
    echo "--- STARTING TEST ---\n";

    // 1. Create Trip
    $trip = Trip::create([
        'package_id' => 1,
        'trip_name' => 'Auto Test Trip ' . time(),
        'start_date' => '2025-01-01',
        'end_date' => '2025-01-10',
        'status' => 'PLANNED',
        'capacity' => 20
    ]);
    echo "[PASS] Trip Created: ID {$trip->trip_id}\n";

    // 2. Add Activity
    $activity = $trip->activities()->create([
        'activity_type' => 'TEST_VISIT',
        'location' => 'Test Location',
        'activity_date' => '2025-01-02',
        'activity_time' => '10:00',
        'status' => 'SCHEDULED'
    ]);
    echo "[PASS] Activity Added: ID {$activity->activity_id}\n";

    // 3. Verify
    $verify = Activity::find($activity->activity_id);
    if ($verify && $verify->trip_id == $trip->trip_id) {
        echo "[SUCCESS] Verified in DB: {$verify->location}\n";
    } else {
        echo "[FAIL] Could not verify in DB\n";
    }

} catch (\Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
}
echo "--- END TEST ---\n";
