<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "--- PACKAGES ---\n";
    $columns = DB::select('DESCRIBE packages');
    foreach ($columns as $col) {
        echo $col->Field . " | " . " | " . $col->Default . "\n";
    }
    echo "--- USERS ---\n";
    $columns = DB::select('DESCRIBE users');
    foreach ($columns as $col) {
        echo $col->Field . " | " . " | " . $col->Default . "\n";
    }
    echo "--- GROUPS_TRIPS ---\n";
    $columns = DB::select('DESCRIBE groups_trips');
    foreach ($columns as $col) {
        echo $col->Field . " | " . " | " . $col->Default . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
