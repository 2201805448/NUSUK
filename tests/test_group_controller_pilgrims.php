<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Pilgrim;
use App\Models\User;
use App\Models\GroupTrip;
use App\Models\GroupMember;
use App\Models\AttendanceTracking;
use App\Models\Trip;
use App\Http\Controllers\Api\GroupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

DB::beginTransaction();

try {
    $unique = time();
    $admin = User::create([
        'full_name' => 'Admin ' . $unique,
        'email' => 'admin_' . $unique . '@example.com',
        'password' => bcrypt('password'),
        'role' => 'ADMIN',
        'phone_number' => '9665' . $unique . '99'
    ]);

    Auth::login($admin);

    $user = User::create([
        'full_name' => 'Group Pilgrim ' . $unique,
        'email' => 'gpilgrim_' . $unique . '@example.com',
        'password' => bcrypt('password'),
        'role' => 'Pilgrim',
        'phone_number' => '9665' . $unique . '00'
    ]);

    $pilgrim = Pilgrim::create([
        'user_id' => $user->user_id,
        'passport_number' => 'PG' . $unique,
        'passport_name' => 'Group Pilgrim Test',
        'gender' => 'MALE',
        'nationality' => 'SA'
    ]);

    // Create Trip and Group
    $trip = Trip::create([
        'trip_name' => 'Test Trip',
        'start_date' => now(),
        'end_date' => now()->addDays(5),
        'status' => 'ONGOING'
    ]);

    $group = GroupTrip::create([
        'trip_id' => $trip->trip_id,
        'group_code' => 'GRP' . $unique,
        'supervisor_id' => $admin->user_id
    ]);

    GroupMember::create([
        'group_id' => $group->group_id,
        'pilgrim_id' => $pilgrim->pilgrim_id,
        'member_status' => 'ACTIVE',
        'join_date' => now()
    ]);

    AttendanceTracking::create([
        'pilgrim_id' => $pilgrim->pilgrim_id,
        'status_type' => 'ARRIVAL',
        'timestamp' => now(),
        'trip_id' => $trip->trip_id,
        'activity_id' => 1
    ]);

    // Instantiate Controller
    $controller = new GroupController();
    $response = $controller->listPilgrims($group->group_id);

    $data = $response->getData(true);

    // listPilgrims returns 'pilgrims' array
    $found = false;
    foreach ($data['pilgrims'] as $p) {
        if ($p['pilgrim_id'] == $pilgrim->pilgrim_id) {
            echo "Found created pilgrim in Group response.\n";
            if (array_key_exists('status_type', $p)) {
                echo "SUCCESS: status_type is present in GroupController response: " . ($p['status_type'] ?? 'NULL') . "\n";
            } else {
                echo "FAILURE: status_type key MISSING in GroupController listPilgrims response.\n";
            }
            $found = true;
            break;
        }
    }

    if (!$found) {
        echo "FAILURE: Pilgrim not found in group list.\n";
    }

} catch (\Exception $e) {
    $err = "EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString();
    echo $err;
    file_put_contents('tests/last_error_group.log', $err);
} finally {
    DB::rollBack();
}
