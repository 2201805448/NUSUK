<?php
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use App\Models\Trip;
use App\Models\Activity;
use App\Models\User;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo "1. Creating Dummy Trip...\n";
// Ensure we have a valid package ID or create one if needed, but assuming 1 exists or using strict mode off.
// Let's just try to create.
try {
    $trip = Trip::create([
        'package_id' => 1,
        'trip_name' => 'Internal Test Trip',
        'start_date' => '2025-06-01',
        'end_date' => '2025-06-10',
        'status' => 'PLANNED',
        'capacity' => 10
    ]);
    echo "Trip ID: " . $trip->trip_id . "\n";

    echo "2. Adding Activity via Model Relationship...\n";
    $activity = $trip->activities()->create([
        'activity_type' => 'RELIGIOUS_VISIT',
        'location' => 'Mount Uhud',
        'activity_date' => '2025-06-02',
        'activity_time' => '08:00',
        'status' => 'SCHEDULED'
    ]);

    if ($activity->exists) {
        echo "Activity Created Successfully: " . $activity->activity_id . "\n";
        echo "Attributes: " . json_encode($activity->toArray()) . "\n";
    } else {
        echo "Failed to create activity.\n";
        exit(1);
    }

    echo "3. Verifying in DB...\n";
    $check = Activity::find($activity->activity_id);
    if ($check) {
        echo "Activity found in DB: " . $check->location . "\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
    exit(1);
}

echo "Test Complete.\n";
