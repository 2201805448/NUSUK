<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Pilgrim;
use App\Models\User;
use App\Models\AttendanceTracking;
use App\Http\Controllers\Api\PilgrimController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Ensure we have a pilgrim with attendance
DB::beginTransaction();

try {
    $unique = time();
    // Corrected User fields based on User.php
    $user = User::create([
        'full_name' => 'Controller Test ' . $unique,
        'email' => 'controller_' . $unique . '@example.com',
        'phone_number' => '9665' . $unique,
        'password' => bcrypt('password'),
        'role' => 'Pilgrim' // title case as per mutator logic or just strings
    ]);

    // Corrected Pilgrim fields based on Pilgrim.php
    $pilgrim = Pilgrim::create([
        'user_id' => $user->user_id,
        'passport_number' => 'PCONT' . $unique,
        'passport_name' => 'Controller Test',
        'gender' => 'MALE',
        'nationality' => 'SA'
    ]);

    AttendanceTracking::create([
        'pilgrim_id' => $pilgrim->pilgrim_id,
        'status_type' => 'DEPARTURE',
        'timestamp' => now(),
        'trip_id' => 1,
        'activity_id' => 1
    ]);

    // Instantiate Controller
    $controller = new PilgrimController();
    $response = $controller->index();

    // Response is a JsonResponse
    $data = $response->getData(true); // get as array

    $found = false;
    foreach ($data['pilgrims'] as $p) {
        if ($p['pilgrim_id'] == $pilgrim->pilgrim_id) {
            echo "Found created pilgrim in response.\n";
            // Check for status_type presence
            if (array_key_exists('status_type', $p)) {
                echo "SUCCESS: status_type is present: " . ($p['status_type'] ?? 'NULL') . "\n";
                if ($p['status_type'] === 'DEPARTURE') {
                    echo "SUCCESS: status_type matches DEPARTURE\n";
                } else {
                    echo "FAILURE: status_type value mismatch. Got: " . ($p['status_type'] ?? 'NULL') . "\n";
                }
            } else {
                echo "FAILURE: status_type key MISSING in pilgrim object.\n";
                // Dump keys to see what IS there
                print_r(array_keys($p));
            }
            $found = true;
            break;
        }
    }

    if (!$found) {
        echo "FAILURE: Created pilgrim not found in list.\n";
    }

} catch (\Exception $e) {
    $err = "EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString();
    echo $err;
    file_put_contents('tests/last_error.log', $err);
} finally {
    DB::rollBack();
}
