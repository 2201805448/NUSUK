<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
try {
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->handle(Illuminate\Http\Request::capture());
} catch (\Exception $e) {
}

echo "Testing Controller Output...\n";

$controller = new \App\Http\Controllers\Api\PilgrimController();
$response = $controller->index();
$content = $response->getContent();

// Decode ID 1 specifically because we know it has attendance now
$data = json_decode($content, true);

if (isset($data['pilgrims'])) {
    foreach ($data['pilgrims'] as $p) {
        if ($p['pilgrim_id'] == 1) {
            echo "Pilgrim 1 Found.\n";
            if (array_key_exists('latest_attendance', $p)) {
                echo "Key 'latest_attendance' EXISTS.\n";
                if ($p['latest_attendance']) {
                    echo "Value is NOT NULL. ID: " . $p['latest_attendance']['attendance_id'] . "\n";
                } else {
                    echo "Value is NULL.\n";
                }
            } else {
                echo "Key 'latest_attendance' MISSING from JSON.\n";
            }
        }
    }
} else {
    echo "Key 'pilgrims' missing from response.\n";
}
