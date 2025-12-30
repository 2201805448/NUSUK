<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

// 1. Setup Data
echo "Setting up data...\n";
try {
    TicketLog::query()->delete();
    Ticket::query()->delete();

    // Create User
    $user = User::firstOrCreate(['email' => 'user_support@example.com'], [
        'full_name' => 'User Support',
        'password' => Hash::make('password'),
        'role' => 'PILGRIM',
        'phone_number' => '111000111',
    ]);

} catch (\Exception $e) {
    echo "Setup Error: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Create Ticket
echo "Creating Ticket...\n";
Auth::forgetGuards();
$token = $user->createToken('user')->plainTextToken;

$req1 = Request::create("/api/support/tickets", 'POST', [
    'title' => 'Login Issue',
    'description' => 'I cannot login to the app.',
    'priority' => 'HIGH'
]);
$req1->headers->set('Authorization', 'Bearer ' . $token);
$req1->headers->set('Accept', 'application/json');
$res1 = $app->handle($req1);
echo "Create Status: " . $res1->getStatusCode() . "\n";

if ($res1->getStatusCode() !== 201) {
    echo "Create Failed: " . $res1->getContent() . "\n";
    exit(1);
}

$ticketId = json_decode($res1->getContent())->ticket->ticket_id;
echo "Created Ticket ID: $ticketId\n";

// 3. Get Ticket with Logs
echo "Fetching Ticket Details...\n";
Auth::forgetGuards();
$req2 = Request::create("/api/support/tickets/{$ticketId}", 'GET');
$req2->headers->set('Authorization', 'Bearer ' . $token);
$req2->headers->set('Accept', 'application/json');
$res2 = $app->handle($req2);
echo "Get Status: " . $res2->getStatusCode() . "\n";

$body = $res2->getContent();
if (strpos($body, 'Login Issue') !== false && strpos($body, 'I cannot login') !== false) {
    echo "SUCCESS: Ticket and Logs retrieved correctly.\n";
} else {
    echo "FAILURE: Content missing.\n";
    echo $body . "\n";
}

// 4. Reply to Ticket
echo "Replying to Ticket...\n";
$req3 = Request::create("/api/support/tickets/{$ticketId}/reply", 'POST', [
    'content' => 'Thanks, wait.'
]);
$req3->headers->set('Authorization', 'Bearer ' . $token);
$req3->headers->set('Accept', 'application/json');
$res3 = $app->handle($req3);
echo "Reply Status: " . $res3->getStatusCode() . "\n";

if ($res3->getStatusCode() === 201) {
    echo "SUCCESS: Reply added.\n";
} else {
    echo "FAILURE: Reply failed.\n";
}

// 5. List Tickets
echo "Listing Tickets...\n";
$req4 = Request::create("/api/support/tickets", 'GET');
$req4->headers->set('Authorization', 'Bearer ' . $token);
$req4->headers->set('Accept', 'application/json');
$res4 = $app->handle($req4);
echo "List Status: " . $res4->getStatusCode() . "\n";

$list = json_decode($res4->getContent(), true);
if (count($list) > 0) {
    echo "SUCCESS: Tickets listed (" . count($list) . ")\n";
} else {
    echo "FAILURE: No tickets found.\n";
}

// 6. Admin Transfer Ticket
echo "Admin Transferring Ticket...\n";
Auth::forgetGuards();
$adminToken = \App\Models\User::where('role', 'ADMIN')->first()->createToken('admin')->plainTextToken; // Reuse Admin from before or create new

$req5 = Request::create("/api/support/tickets/{$ticketId}/transfer", 'POST', [
    'department' => 'RELIGIOUS'
]);
$req5->headers->set('Authorization', 'Bearer ' . $adminToken);
$req5->headers->set('Accept', 'application/json');
$res5 = $app->handle($req5);
echo "Transfer Status: " . $res5->getStatusCode() . "\n";

if ($res5->getStatusCode() === 200) {
    echo "SUCCESS: Ticket transferred.\n";
} else {
    echo "FAILURE: Transfer failed.\n";
    echo $res5->getContent() . "\n";
}

// 7. Admin Close Ticket
echo "Admin Closing Ticket...\n";
$req6 = Request::create("/api/support/tickets/{$ticketId}/close", 'POST');
$req6->headers->set('Authorization', 'Bearer ' . $adminToken);
$req6->headers->set('Accept', 'application/json');
$res6 = $app->handle($req6);
echo "Close Status: " . $res6->getStatusCode() . "\n";

if ($res6->getStatusCode() === 200) {
    echo "SUCCESS: Ticket closed.\n";
} else {
    echo "FAILURE: Close failed.\n";
    echo $res6->getContent() . "\n";
}

// 8. Verify Notifications
echo "Verifying Notifications...\n";
$notifCount = \App\Models\Notification::count();
echo "Total Notifications: $notifCount\n";

$latestNotif = \App\Models\Notification::orderBy('created_at', 'desc')->first();
if ($latestNotif) {
    echo "Latest Notification: " . $latestNotif->title . " - " . $latestNotif->message . "\n";
    if (strpos($latestNotif->title, 'Ticket Closed') !== false) {
        echo "SUCCESS: 'Ticket Closed' notification found.\n";
    }
} else {
    echo "FAILURE: No notifications found.\n";
}
