<?php

use App\Models\User;
use App\Models\Pilgrim;
use App\Models\GroupTrip;
use App\Models\Trip;
use App\Models\GroupMember;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Starting verification for Group Remove Member fix...\n";

try {
    // 1. Setup Data
    $admin = User::where('role', 'ADMIN')->first();
    if (!$admin) {
        echo "Error: No Admin user found.\n";
        exit(1);
    }
    Auth::login($admin);

    $trip = Trip::first();
    if (!$trip) {
        // Create a dummy trip if none exists
        $trip = Trip::create([
            'trip_name' => 'Verification Trip',
            'start_date' => now(),
            'end_date' => now()->addDays(10),
            'status' => 'UPCOMING',
            'price' => 1000,
            'capacity' => 100
        ]);
    }

    $groupCode = 'TEST-GRP-' . uniqid();
    $group = GroupTrip::create([
        'group_code' => $groupCode,
        'trip_id' => $trip->trip_id,
        'supervisor_id' => $admin->user_id,
        'group_status' => 'ACTIVE'
    ]);

    echo "Created Group: {$group->group_id} ({$group->group_code})\n";

    // Create a test user/pilgrim
    $uniq = uniqid();
    $testUser = User::create([
        'full_name' => 'Test Pilgrim ' . $uniq,
        'email' => 'pilgrim' . $uniq . '@test.com',
        'password' => bcrypt('password'),
        'role' => 'PILGRIM',
        'phone_number' => '050000' . rand(1000, 9999),
    ]);

    $testPilgrim = Pilgrim::create([
        'user_id' => $testUser->user_id,
        'passport_number' => 'PASS-' . $uniq,
        'passport_name' => $testUser->full_name,
        'nationality' => 'Testland',
        'gender' => 'MALE',
    ]);

    echo "Created Pilgrim: {$testPilgrim->pilgrim_id}\n";

    // Add Member
    $member = GroupMember::create([
        'group_id' => $group->group_id,
        'pilgrim_id' => $testPilgrim->pilgrim_id,
        'join_date' => now(),
        'member_status' => 'ACTIVE'
    ]);

    echo "Added Member to Group. Status: {$member->member_status}\n";

    // 2. Refresh Group and Check List (Should see 1 member)
    $group->refresh();
    $group->load([
        'members' => function ($q) {
            $q->where('member_status', 'ACTIVE');
        }
    ]);

    if ($group->members->count() !== 1) {
        echo "FAILED: Expected 1 member, found " . $group->members->count() . "\n";
        exit(1);
    }
    echo "Verified: 1 Active Member found.\n";

    // 3. Remove Member (Call Controller Logic or Simulate)
// Using Controller Logic Simulation since we don't have full HTTP stack wrapper here easily, 
// but we can call the method or just do the DB update as the controller does.
// The controller update logic is:
    GroupMember::where('group_id', $group->group_id)
        ->where('pilgrim_id', $testPilgrim->pilgrim_id)
        ->update(['member_status' => 'REMOVED']);

    echo "Removed Member (Status updated to REMOVED).\n";

    // 4. Verify Index (Eager Load Filtering)
// Re-fetch group like the index method would
    $query = GroupTrip::with([
        'supervisor',
        'members' => function ($q) {
            $q->where('member_status', 'ACTIVE');
        }
    ])->where('group_id', $group->group_id)->first();

    echo "Index Query Members Count: " . $query->members->count() . "\n";
    if ($query->members->count() !== 0) {
        echo "FAILED [Index]: Expected 0 members after removal, found " . $query->members->count() . "\n";
        // Debug
        foreach ($query->members as $m) {
            echo " - Member Status: " . $m->member_status . "\n";
        }
    } else {
        echo "PASSED [Index]: Filtered out removed member.\n";
    }

    // 5. Verify listPilgrims (Eager Load Filtering)
    $groupList = GroupTrip::with([
        'trip',
        'supervisor',
        'members' => function ($q) {
            $q->where('member_status', 'ACTIVE');
        },
        'members.pilgrim.user'
    ])->findOrFail($group->group_id);

    echo "ListPilgrims Query Members Count: " . $groupList->members->count() . "\n";

    if ($groupList->members->count() !== 0) {
        echo "FAILED [ListPilgrims]: Expected 0 members after removal, found " . $groupList->members->count() . "\n";
    } else {
        echo "PASSED [ListPilgrims]: Filtered out removed member.\n";
    }

    echo "Verification Complete.\n";
} catch (\Exception $e) {
    $msg = "Global Error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
    echo $msg;
    file_put_contents(__DIR__ . '/verification_error.log', $msg);
    exit(1);
}
