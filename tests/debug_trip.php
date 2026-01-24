<?php
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "Debug: Trip creation test\n";

try {
    $trip = Trip::create([
        'trip_name' => 'Simple Trip',
        'start_date' => '2025-01-01',
        'end_date' => '2025-01-05',
        // 'status' => 'PLANNED' // Relying on default
    ]);
    echo "Trip created: " . $trip->trip_id . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
