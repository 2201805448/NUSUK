<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Pilgrim;

echo "=== Checking Users ===\n";
$users = User::take(10)->get(['user_id', 'email', 'role', 'full_name']);
foreach ($users as $u) {
    echo "{$u->user_id}: {$u->email} - {$u->role} - {$u->full_name}\n";
}

echo "\n=== Checking Pilgrims ===\n";
$pilgrims = Pilgrim::with('user')->take(5)->get();
foreach ($pilgrims as $p) {
    echo "Pilgrim ID: {$p->pilgrim_id}, User: " . ($p->user ? $p->user->email : 'N/A') . "\n";
}
