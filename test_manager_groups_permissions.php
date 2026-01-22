<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Accommodation;
use Illuminate\Support\Facades\Hash;

$baseUrl = 'http://127.0.0.1:8000/api';
$runId = time();
$adminEmail = 'verify_admin_' . $runId . '@example.com';
$supEmail = 'verify_sup_' . $runId . '@example.com';
$password = 'password123';

echo "=== Verifying Manager-Only Permissions for Groups ===\n\n";

// 1. Create Users (Direct DB)
echo "1. Creating Users & Accommodation (DB)...\n";

$admin = User::create([
    'full_name' => 'VerifyAdmin',
    'email' => $adminEmail,
    'password' => Hash::make($password),
    'phone_number' => '999' . $runId,
    'role' => 'ADMIN',
    'account_status' => 'ACTIVE'
]);
echo "ADMIN Created (DB ID: {$admin->user_id}).\n";

$sup = User::create([
    'full_name' => 'VerifySup',
    'email' => $supEmail,
    'password' => Hash::make($password),
    'phone_number' => '888' . $runId,
    'role' => 'SUPERVISOR',
    'account_status' => 'ACTIVE'
]);
echo "SUPERVISOR Created (DB ID: {$sup->user_id}).\n";

// Create Accommodation
$acc = Accommodation::create([
    'hotel_name' => 'Test Hotel ' . $runId,
    'city' => 'Mecca',
    'room_type' => 'QUAD',
    'capacity' => 4,
    'start' => 4,
    'phone' => '1234567890',
    'email' => 'hotel' . $runId . '@example.com'
]);
echo "Accommodation Created (DB ID: {$acc->accommodation_id}).\n\n";


function callApi($url, $method = 'POST', $data = [], $token = null)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    curl_setopt($ch, CURLOPT_URL, $url);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => json_decode($response, true), 'raw_body' => $response];
}

// 2. Login to get Tokens
echo "2. Logging in to get tokens...\n";

// Login Admin
$loginAdm = callApi($baseUrl . '/login', 'POST', ['email' => $adminEmail, 'password' => $password]);
$adminToken = $loginAdm['body']['token'] ?? null;
if (!$adminToken) {
    die("Failed to login Admin. Response: " . $loginAdm['raw_body'] . "\n");
}
echo "ADMIN Logged In.\n";

// Login Supervisor
$loginSup = callApi($baseUrl . '/login', 'POST', ['email' => $supEmail, 'password' => $password]);
$supToken = $loginSup['body']['token'] ?? null;
if (!$supToken) {
    die("Failed to login Supervisor. Response: " . $loginSup['raw_body'] . "\n");
}
echo "SUPERVISOR Logged In.\n\n";


// 3. Setup Trip (Admin)
echo "3. Creating Trip (Admin)...\n";

$pkgData = [
    'package_name' => 'VerifySearchPkg',
    'price' => 500,
    'duration_days' => 5,
    'is_active' => true,
    'accommodation_id' => $acc->accommodation_id,
    'room_type' => 'QUAD'
];

$pkg = callApi($baseUrl . '/packages', 'POST', $pkgData, $adminToken);
$pkgBody = $pkg['body'];
$pkgId = $pkgBody['package']['package_id'] ?? null;

if (!$pkgId) {
    echo "Package Creation Failed: " . $pkg['raw_body'] . "\n";
    die("Cannot proceed without Package.\n");
}

$trip = callApi($baseUrl . '/trips', 'POST', ['package_id' => $pkgId, 'trip_name' => 'VerifyTrip', 'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d', strtotime('+5 days'))], $adminToken);
$tripId = $trip['body']['trip']['trip_id'] ?? null;
if (!$tripId) {
    echo "Trip Creation Failed: " . $trip['raw_body'] . "\n";
    die("Cannot proceed without Trip.\n");
}
echo "Trip Created ID: $tripId\n\n";

// 3.5 Create a Group (As Admin) to test against
echo "3.5 Creating Test Group (Admin)...\n";
$res = callApi($baseUrl . '/groups', 'POST', ['name' => 'TestGroup-' . $runId, 'trip_id' => $tripId, 'pilgrim_ids' => [$admin->user_id]], $adminToken);
$groupId = $res['body']['group']['group_id'] ?? null;
if (!$groupId) {
    die("Failed to create test group.\n");
}
echo "Test Group Created ID: $groupId\n\n";

// 4. Test SUPERVISOR Permissions (Should Fail)
echo "4. Testing SUPERVISOR Permissions (Expect 403 Forbidden)...\n";

echo "   a) Try Create Group (Supervisor)... ";
$res = callApi($baseUrl . '/groups', 'POST', ['name' => 'SupGroup', 'trip_id' => $tripId, 'pilgrim_ids' => [$sup->user_id]], $supToken);
if ($res['code'] === 403) {
    echo "PASSED (403 Forbidden)\n";
} else {
    echo "FAILED (Got " . $res['code'] . ")\n";
}

echo "   b) Try List Groups (Supervisor)... ";
$res = callApi($baseUrl . '/groups', 'GET', [], $supToken);
if ($res['code'] === 403) {
    echo "PASSED (403 Forbidden)\n";
} else {
    echo "FAILED (Got " . $res['code'] . ")\n";
}

echo "   c) Try List My Pilgrims (Supervisor)... ";
$res = callApi($baseUrl . '/my-pilgrims', 'GET', [], $supToken);
if ($res['code'] === 403) {
    echo "PASSED (403 Forbidden)\n";
} else {
    echo "FAILED (Got " . $res['code'] . ")\n";
}

echo "   d) Try Display Mutamir List per Group (Supervisor)... ";
if ($groupId) {
    $res = callApi($baseUrl . "/groups/$groupId/pilgrims", 'GET', [], $supToken);
    if ($res['code'] === 403) {
        echo "PASSED (403 Forbidden)\n";
    } else {
        echo "FAILED (Got " . $res['code'] . ")\n";
    }
} else {
    echo "SKIPPED (No Group ID to test)\n";
}

echo "\n";

// 5. Test ADMIN Permissions (Should Succeed)
echo "5. Testing ADMIN Permissions (Expect 200/201)...\n";

echo "   a) Try Create Group (Admin)... ";
$groupName = "AdmGroup-" . $runId;
// Use user_id of Admin as pilgrim just to satisfy validation
$res = callApi($baseUrl . '/groups', 'POST', ['name' => $groupName, 'trip_id' => $tripId, 'pilgrim_ids' => [$admin->user_id]], $adminToken);
if ($res['code'] === 201) {
    echo "PASSED (201 Created)\n";
    $groupId = $res['body']['group']['group_id'];
} else {
    echo "FAILED (Got " . $res['code'] . ")\n";
    print_r($res['body']);
    $groupId = null;
}

echo "   b) Try List Groups (Admin)... ";
$res = callApi($baseUrl . '/groups', 'GET', [], $adminToken);
if ($res['code'] === 200) {
    // Check if created group is in list
    $found = false;
    foreach ($res['body'] as $g) {
        if (isset($g['group_id']) && $g['group_id'] == $groupId) {
            $found = true;
            break;
        }
    }
    if ($found) {
        echo "PASSED (Group found in list)\n";
    } else {
        echo "WARNING: 200 OK but group not found in list\n";
    }
} else {
    echo "FAILED (Got " . $res['code'] . ")\n";
    print_r($res['body']);
}

echo "   c) Try View Group Details (Admin)... ";
if ($groupId) {
    $res = callApi($baseUrl . "/groups/$groupId", 'GET', [], $adminToken);
    if ($res['code'] === 200) {
        echo "PASSED (200 OK)\n";
    } else {
        echo "FAILED (Got " . $res['code'] . ")\n";
    }

    echo "   d) Try Display Mutamir List per Group (Admin)... ";
    $res = callApi($baseUrl . "/groups/$groupId/pilgrims", 'GET', [], $adminToken);
    if ($res['code'] === 200) {
        echo "PASSED (200 OK)\n";
    } else {
        echo "FAILED (Got " . $res['code'] . ")\n";
    }

} else {
    echo "SKIPPED (No Group ID)\n";
}

echo "\nDone.\n";
