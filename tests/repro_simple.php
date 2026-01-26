<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Pilgrim;

// Create a dummy instance to test serialization
$pilgrim = new Pilgrim();
$pilgrim->pilgrim_id = 999;
$pilgrim->status_type = 'ARRIVAL';

$array = $pilgrim->toArray();

echo "Debug: Testing serialization of dynamic property on Pilgrim model.\n";
if (array_key_exists('status_type', $array)) {
    echo "SUCCESS: status_type IS present in toArray(). Value: " . $array['status_type'] . "\n";
} else {
    echo "FAILURE: status_type IS NOT present in toArray().\n";
    print_r($array);
}

// Now test the mapping logic from the controller
echo "\nDebug: Testing Controller Logic simulation.\n";
// We need an actual DB record for the relationship test, or we mock it.
// Let's rely on the first test. If a simple assignment works, the controller should work IF the loop works.
