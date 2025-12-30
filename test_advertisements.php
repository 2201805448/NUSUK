<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Announcement;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

// 1. Setup Admin & User
$admin = User::where('email', 'admin_ads@example.com')->first();
if (!$admin) {
    $admin = User::create([
        'full_name' => 'Admin Ads',
        'email' => 'admin_ads@example.com',
        'phone_number' => '1112228811',
        'password' => Hash::make('password'),
        'role' => 'ADMIN',
    ]);
}
$adminToken = $admin->createToken('admin-token')->plainTextToken;

$user = User::where('email', 'user_ads@example.com')->first();
if (!$user) {
    $user = User::create([
        'full_name' => 'User Ads',
        'email' => 'user_ads@example.com',
        'phone_number' => '1112228822',
        'password' => Hash::make('password'),
        'role' => 'USER',
    ]);
}
$userToken = $user->createToken('user-token')->plainTextToken;

// 2. Create Advertisement (Admin)
echo "Creating Advertisement...\n";
$req1 = Request::create('/api/announcements', 'POST', [
    'title' => 'Special Umrah Offer',
    'content' => '50% off for families.',
    'expiry_date' => date('Y-m-d', strtotime('+1 week')),
]);
$req1->headers->set('Authorization', 'Bearer ' . $adminToken);
$req1->headers->set('Accept', 'application/json');
$res1 = $app->handle($req1);
echo "Create Status: " . $res1->getStatusCode() . "\n";
echo "Create Body: " . $res1->getContent() . "\n";

// 3. View Advertisements (User)
echo "Viewing Advertisements...\n";
$req2 = Request::create('/api/announcements', 'GET');
$req2->headers->set('Authorization', 'Bearer ' . $userToken);
$req2->headers->set('Accept', 'application/json');
$res2 = $app->handle($req2);
echo "View Status: " . $res2->getStatusCode() . "\n";
$content = $res2->getContent();
echo "View Body: " . substr($content, 0, 200) . "...\n";

// Verify
if (strpos($content, 'Special Umrah Offer') !== false) {
    echo "SUCCESS: Advertisement found.\n";
} else {
    echo "FAILURE: Advertisement not found.\n";
}

// Cleanup
Announcement::truncate(); // Simple cleanup for this table
$admin->delete();
$user->delete();
