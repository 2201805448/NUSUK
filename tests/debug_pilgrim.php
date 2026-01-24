<?php
use App\Models\User;
use App\Models\Pilgrim;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "Debug: Pilgrim creation test\n";

try {
    $user = User::create([
        'name' => 'Test Pilgrim',
        'email' => 'testpilgrim_' . time() . '@example.com',
        'password' => bcrypt('password'),
        'role' => 'PILGRIM'
    ]);
    echo "User created: " . $user->user_id . "\n";

    $pilgrim = Pilgrim::create([
        'user_id' => $user->user_id,
        'passport_number' => 'TEST' . time(),
        'passport_name' => 'Test Pilgrim',
        'nationality' => 'Testland',
        'gender' => 'MALE'
    ]);
    echo "Pilgrim created: " . $pilgrim->pilgrim_id . "\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
