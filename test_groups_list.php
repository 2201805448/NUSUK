<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Trip;
use App\Models\GroupTrip;
use App\Models\Package;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

// 1. Setup Users
function createUser($email, $role, $name)
{
    $user = User::where('email', $email)->first();
    if (!$user) {
        $user = User::create([
            'full_name' => $name,
            'email' => $email,
            'phone_number' => '12345678' . rand(10, 99),
            'password' => Hash::make('password'),
            'role' => $role,
        ]);
    }
    return $user;
}

$admin = createUser('admin_gl@example.com', 'ADMIN', 'Admin GL');
$supA = createUser('sup_a@example.com', 'SUPERVISOR', 'Sup A');
$supB = createUser('sup_b@example.com', 'SUPERVISOR', 'Sup B');

$tokenAdmin = $admin->createToken('admin')->plainTextToken;
$tokenSupA = $supA->createToken('supA')->plainTextToken;
$tokenSupB = $supB->createToken('supB')->plainTextToken;

// 2. Cleanup & Setup Data
echo "Setting up data...\n";
try {
    GroupTrip::query()->delete();
    Trip::where('trip_name', 'Test Trip GL')->delete();
    // Package cleanup might fail if trips exist, but we deleted specific trip above.
    Package::where('package_name', 'Pkg GL')->delete();

    $pkg = Package::create([
        'package_name' => 'Pkg GL',
        'price' => 1000,
        'duration_days' => 10,
        'is_active' => true
    ]);

    $trip = Trip::create([
        'trip_name' => 'Test Trip GL',
        'package_id' => $pkg->package_id,
        'start_date' => now(),
        'end_date' => now()->addDays(5),
        'status' => 'PLANNED'
    ]);

    // Group 1 -> Supervisor A
    GroupTrip::create([
        'trip_id' => $trip->trip_id,
        'supervisor_id' => $supA->user_id,
        'group_code' => 'G1-A',
        'group_status' => 'ACTIVE'
    ]);

    // Group 2 -> Supervisor B
    GroupTrip::create([
        'trip_id' => $trip->trip_id,
        'supervisor_id' => $supB->user_id,
        'group_code' => 'G2-B',
        'group_status' => 'ACTIVE'
    ]);
} catch (\Exception $e) {
    echo "Setup Error: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Test Admin View (Should see both)
echo "Admin Viewing Groups...\n";
try {
    $req1 = Request::create('/api/groups', 'GET');
    $req1->headers->set('Authorization', 'Bearer ' . $tokenAdmin);
    $req1->headers->set('Accept', 'application/json');
    $res1 = $app->handle($req1);
    $body1 = $res1->getContent();
    echo "Admin status: " . $res1->getStatusCode() . "\n";
    if ($res1->getStatusCode() !== 200) {
        echo "Admin Error Body: " . substr($body1, 0, 500) . "\n";
    }
    if (strpos($body1, 'G1-A') !== false && strpos($body1, 'G2-B') !== false) {
        echo "SUCCESS: Admin sees both groups.\n";
    } else {
        echo "FAILURE: Admin missing groups.\n";
    }
} catch (\Exception $e) {
    echo "Admin Request Error: " . $e->getMessage() . "\n";
}

use Illuminate\Support\Facades\Auth;

// 4. Test Sup A View (Should see G1 ONLY)
Auth::forgetGuards(); // Clear previous auth state (Admin)
echo "Sup A Viewing Groups...\n";
$req2 = Request::create('/api/groups?debug=1', 'GET');
$req2->headers->set('Authorization', 'Bearer ' . $tokenSupA);
$req2->headers->set('Accept', 'application/json');
$res2 = $app->handle($req2);
$body2 = $res2->getContent();
echo "Sup A status: " . $res2->getStatusCode() . "\n";
if (strpos($body2, 'G1-A') !== false && strpos($body2, 'G2-B') === false) {
    echo "SUCCESS: Sup A sees G1 only.\n";
} else {
    echo "FAILURE: Sup A visibility incorrect.\n";
    $json = json_decode($body2, true);
    if (isset($json['role'])) {
        echo "Detected Role: " . $json['role'] . "\n";
        echo "Detected ID: " . $json['id'] . "\n";
        echo "Filter Val: " . $json['supervisor_id_filter'] . "\n";
        echo "Data Count: " . count($json['data']) . "\n";
    } else {
        echo "Body: " . substr($body2, 0, 500) . "\n";
    }
}

// 5. Cleanup
// Keeping data for manual inspection if needed, or cleared by truncate next run.
