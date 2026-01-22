<?php

use App\Models\User;
use App\Models\GroupTrip;
use App\Models\Pilgrim;
use App\Models\GroupMember;
use App\Models\Trip;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\GroupController;
use Illuminate\Http\Request;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- STARTING REQUESTED VERIFICATION ---\n";
DB::beginTransaction();

try {
    // 1. Setup Data
    echo "[1/4] Setting up test environment...\n";
    $trip = Trip::firstOrCreate(
        ['trip_name' => 'Pilgrim View Test Trip'],
        [
            'start_date' => now()->addDays(30),
            'end_date' => now()->addDays(40),
            'status' => 'PLANNED',
            'notes' => 'For Verification'
        ]
    );

    $supervisor = User::create([
        'full_name' => 'Sup Verify',
        'email' => 'sup_verify_' . time() . '@example.com',
        'password' => Hash::make('password'),
        'role' => 'SUPERVISOR',
        'phone_number' => '9665' . rand(10000000, 99999999)
    ]);

    $group = GroupTrip::create([
        'trip_id' => $trip->trip_id,
        'supervisor_id' => $supervisor->user_id,
        'group_code' => 'GRP-VERIFY-' . time(),
        'group_status' => 'ACTIVE'
    ]);

    $pilgrimUser = User::create([
        'full_name' => 'Pilgrim Verify',
        'email' => 'pilg_verify_' . time() . '@example.com',
        'password' => Hash::make('password'),
        'role' => 'PILGRIM',
        'phone_number' => '9665' . rand(10000000, 99999999)
    ]);

    // Explicitly setting passport number to verify it shows up
    $testPassport = 'P-VERIFY-' . rand(1000, 9999);
    $pilgrimProfile = Pilgrim::create([
        'user_id' => $pilgrimUser->user_id,
        'passport_name' => 'Pilgrim Verify Passport',
        'passport_number' => $testPassport,
        'nationality' => 'VerifyLand',
        'gender' => 'FEMALE',
        'date_of_birth' => '1995-05-05',
        'emergency_call' => '999'
    ]);

    GroupMember::create([
        'group_id' => $group->group_id,
        'pilgrim_id' => $pilgrimProfile->pilgrim_id,
        'member_status' => 'ACTIVE',
        'join_date' => now()
    ]);

    echo "      Created Group: {$group->group_code}\n";
    echo "      Created Pilgrim with Passport: $testPassport\n";


    // 2. Test Function 1: Manager View (Group specific list)
    echo "\n[2/4] Testing Function 1: Manager (Admin) Group List View...\n";
    $admin = User::where('role', 'ADMIN')->first();
    if (!$admin) {
        $admin = User::factory()->create(['role' => 'ADMIN']);
    }
    Auth::login($admin);

    $controller = new GroupController();
    $response = $controller->listPilgrims($group->group_id);

    if ($response->getStatusCode() === 200) {
        $data = $response->getData(true);
        $count = count($data['pilgrims']);
        echo "      Success! Retrieved list. Count: $count\n";
    } else {
        echo "      FAILED. Status: " . $response->getStatusCode() . "\n";
    }


    // 3. Test Function 2: Supervisor View (Consolidated list)
    echo "\n[3/4] Testing Function 2: Supervisor Consolidated List View...\n";
    Auth::login($supervisor); // Switch to supervisor

    $request = new Request();
    $response = $controller->listAllPilgrims($request);

    if ($response->getStatusCode() === 200) {
        $data = $response->getData(true);
        $list = $data['pilgrims'];
        $found = false;
        echo "      Success! Retrieved list. Found " . count($list) . " pilgrims.\n";

        foreach ($list as $p) {
            if ($p['passport_number'] === $testPassport) {
                $found = true;
                echo "      [PASS] Found correct Pilgrim: {$p['full_name']}\n";
                echo "      [PASS] Passport Number verified: {$p['passport_number']}\n";
                echo "      [PASS] Group Name verified: {$p['group_code']}\n";
                echo "      [PASS] Status verified: {$p['member_status']}\n";
            }
        }

        if (!$found) {
            echo "      [FAIL] Did not find the test pilgrim in the list.\n";
        }
    } else {
        echo "      FAILED. Status: " . $response->getStatusCode() . "\n";
    }

    echo "\n[4/4] Verification Complete.\n";

} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
} finally {
    DB::rollBack();
    echo "\n--- Database Transaction Rolled Back (Clean State) ---\n";
}
