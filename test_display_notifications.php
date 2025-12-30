<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

// 1. Setup User
$user = User::where('email', 'user_display_notif@example.com')->first();
if (!$user) {
    $user = User::create([
        'full_name' => 'User Display Notif',
        'email' => 'user_display_notif@example.com',
        'phone_number' => '1112224499',
        'password' => Hash::make('password'),
        'role' => 'USER',
    ]);
}
$token = $user->createToken('test-token')->plainTextToken;

// 2. Create Dummy Notifications
$notif1 = Notification::create([
    'user_id' => $user->user_id,
    'title' => 'Test Notification 1',
    'message' => 'Message 1',
    'is_read' => false,
    'created_at' => now(),
]);
$notif2 = Notification::create([
    'user_id' => $user->user_id,
    'title' => 'Test Notification 2',
    'message' => 'Message 2',
    'is_read' => false,
    'created_at' => now(),
]);

// 3. List Notifications
echo "Listing Notifications...\n";
$req1 = Request::create('/api/notifications', 'GET');
$req1->headers->set('Authorization', 'Bearer ' . $token);
$req1->headers->set('Accept', 'application/json');
$res1 = $app->handle($req1);
echo "List Status: " . $res1->getStatusCode() . "\n";
echo "List Body: " . substr($res1->getContent(), 0, 200) . "...\n";

// 4. Mark As Read
echo "Marking Notification 1 as Read...\n";
$req2 = Request::create('/api/notifications/' . $notif1->notification_id . '/read', 'PUT');
$req2->headers->set('Authorization', 'Bearer ' . $token);
$req2->headers->set('Accept', 'application/json');
$res2 = $app->handle($req2);
echo "Mark Read Status: " . $res2->getStatusCode() . "\n";

// Verify
$notif1->refresh();
if ($notif1->is_read)
    echo "SUCCESS: Notification 1 is read.\n";
else
    echo "FAILURE: Notification 1 is NOT read.\n";

// 5. Mark All As Read
echo "Marking All as Read...\n";
$req3 = Request::create('/api/notifications/read-all', 'PUT');
$req3->headers->set('Authorization', 'Bearer ' . $token);
$req3->headers->set('Accept', 'application/json');
$res3 = $app->handle($req3);
echo "Mark All Status: " . $res3->getStatusCode() . "\n";

// Verify
$notif2->refresh();
if ($notif2->is_read)
    echo "SUCCESS: Notification 2 is read.\n";
else
    echo "FAILURE: Notification 2 is NOT read.\n";

// Cleanup
Notification::where('user_id', $user->user_id)->delete();
$user->delete();
