<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

// 1. Setup Users
$pilgrim = User::where('email', 'pilgrim_chat@example.com')->first();
if (!$pilgrim) {
    $pilgrim = User::create([
        'full_name' => 'Pilgrim Chat',
        'email' => 'pilgrim_chat@example.com',
        'phone_number' => '9998887770',
        'password' => Hash::make('password'),
        'role' => 'PILGRIM',
    ]);
}
$pilgrimToken = $pilgrim->createToken('pilgrim-token')->plainTextToken;

$supervisor = User::where('email', 'sup_chat@example.com')->first();
if (!$supervisor) {
    $supervisor = User::create([
        'full_name' => 'Supervisor Chat',
        'email' => 'sup_chat@example.com',
        'phone_number' => '9998887771',
        'password' => Hash::make('password'),
        'role' => 'SUPERVISOR',
    ]);
}
$supToken = $supervisor->createToken('sup-token')->plainTextToken;

// 2. Pilgrim sends message to Supervisor
echo "Pilgrim sending message to Supervisor...\n";
$req1 = Request::create('/api/messages', 'POST', [
    'receiver_id' => $supervisor->user_id,
    'content' => 'Hello Supervisor, I have a question.'
]);
$req1->headers->set('Authorization', 'Bearer ' . $pilgrimToken);
$req1->headers->set('Accept', 'application/json');
$res1 = $app->handle($req1);
echo "Send Status: " . $res1->getStatusCode() . "\n";

// 3. Supervisor views conversation
echo "Supervisor viewing conversation...\n";
$req2 = Request::create('/api/messages/' . $pilgrim->user_id, 'GET');
$req2->headers->set('Authorization', 'Bearer ' . $supToken);
$req2->headers->set('Accept', 'application/json');
$res2 = $app->handle($req2);
echo "View Status: " . $res2->getStatusCode() . "\n";
echo "Messages: " . $res2->getContent() . "\n";

// 4. Supervisor replies
echo "Supervisor replying...\n";
$req3 = Request::create('/api/messages', 'POST', [
    'receiver_id' => $pilgrim->user_id,
    'content' => 'Hi Pilgrim, how can I help?'
]);
$req3->headers->set('Authorization', 'Bearer ' . $supToken);
$req3->headers->set('Accept', 'application/json');
$res3 = $app->handle($req3);
echo "Reply Status: " . $res3->getStatusCode() . "\n";

// 5. Check Inbox (Index)
echo "Checking Pilgrim Inbox...\n";
$req4 = Request::create('/api/messages', 'GET');
$req4->headers->set('Authorization', 'Bearer ' . $pilgrimToken);
$req4->headers->set('Accept', 'application/json');
$res4 = $app->handle($req4);
echo "Inbox Status: " . $res4->getStatusCode() . "\n";
$inbox = $res4->getContent();
echo "Inbox Preview: " . substr($inbox, 0, 150) . "...\n";

// Verify
if (strpos($inbox, 'Supervisor Chat') !== false) {
    echo "SUCCESS: Conversation found in inbox.\n";
} else {
    echo "FAILURE: Conversation not found.\n";
}


// Cleanup
Message::where('sender_id', $pilgrim->user_id)->orWhere('receiver_id', $pilgrim->user_id)->delete();
$pilgrim->delete();
$supervisor->delete();
