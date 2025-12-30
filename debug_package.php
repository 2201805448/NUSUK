<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Package;

try {
    echo "Creating Package...\n";
    $pkg = Package::create([
        'package_name' => 'Debug Pkg',
        'price' => 100,
        'duration_days' => 5,
        'start_date' => now(), // Ignored likely
        'end_date' => now(),   // Ignored likely
        'is_active' => true
    ]);
    echo "Package created: " . $pkg->package_id . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
