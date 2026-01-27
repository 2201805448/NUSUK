<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

// 1. Setup Admin & User with Token
$adminEmail = 'admin_fcm_test@example.com';
$admin = User::where('email', $adminEmail)->first();
if (!$admin) {
    $admin = User::create([
        'full_name' => 'Admin FCM',
        'email' => $adminEmail,
        'phone_number' => '9998887770',
        'password' => Hash::make('password'),
        'role' => 'ADMIN',
    ]);
}
$adminToken = $admin->createToken('admin-token')->plainTextToken;

$userEmail = 'user_fcm_test@example.com';
$user = User::where('email', $userEmail)->first();
if (!$user) {
    $user = User::create([
        'full_name' => 'User FCM',
        'email' => $userEmail,
        'phone_number' => '9998887771',
        'password' => Hash::make('password'),
        'role' => 'USER',
        'fcm_token' => 'test_fcm_token_value' // In reality this should be a valid token for FCM to accept it, but we test the code path here
    ]);
} else {
    $user->update(['fcm_token' => 'test_fcm_token_value']);
}

// 2. Test General Notification triggering FCM Code Path
echo "Testing General Notification with FCM Path...\n";

// We can't easily mock the 'firebase.messaging' app alias in this simple script without extensive setup.
// However, the controller catches the exception if FCM fails (which it will with a dummy token).
// So we check if the request succeeds (200 OK) which implies the code path was traversed and the failure caught.

$req = Request::create('/api/notifications/general', 'POST', [
    'title' => 'FCM Test',
    'message' => 'This should try to send to FCM.'
]);
$req->headers->set('Authorization', 'Bearer ' . $adminToken);
$req->headers->set('Accept', 'application/json');

$res = $app->handle($req);

echo "Status Code: " . $res->getStatusCode() . "\n";
echo "Response: " . $res->getContent() . "\n";

if ($res->getStatusCode() === 200) {
    echo "SUCCESS: Notification API handled the request (including FCM attempt).\n";
} else {
    echo "FAILURE: API returned error.\n";
}

// Check Notification DB
$notif = Notification::where('user_id', $user->user_id)->where('title', 'FCM Test')->latest()->first();
if ($notif) {
    echo "SUCCESS: Database notification created.\n";
} else {
    echo "FAILURE: Database notification missing.\n";
}
