<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

$email = 'test_fcm_login_' . time() . '@example.com';
$password = 'password123';
$fcmToken = 'dumm_fcm_token_' . time();

echo "1. Registering new user ($email)...\n";

$reqReg = Request::create('/api/register', 'POST', [
    'full_name' => 'FCM Tester',
    'email' => $email,
    'phone_number' => '1234567899',
    'password' => $password,
    'role' => 'USER'
]);
$reqReg->headers->set('Accept', 'application/json');

$resReg = $app->handle($reqReg);

if ($resReg->getStatusCode() !== 201) {
    echo "Registration failed. Status: " . $resReg->getStatusCode() . "\n";
    echo "Response: " . $resReg->getContent() . "\n";
    exit(1);
}

echo "2. Testing Login with FCM Token...\n";

$reqLogin = Request::create('/api/login', 'POST', [
    'email' => $email,
    'password' => $password,
    'fcm_token' => $fcmToken
]);
$reqLogin->headers->set('Accept', 'application/json');

$resLogin = $app->handle($reqLogin);

if ($resLogin->getStatusCode() !== 200) {
    echo "Login failed. Status: " . $resLogin->getStatusCode() . "\n";
    echo "Response: " . $resLogin->getContent() . "\n";
    exit(1);
}

$data = json_decode($resLogin->getContent(), true);
if (!isset($data['token'])) {
    echo "Login successful but no token?\n";
    print_r($data);
    exit(1);
}

echo "Login Successful. Token received.\n";

// Verify Database
$user = User::where('email', $email)->first();
if ($user && $user->fcm_token === $fcmToken) {
    echo "SUCCESS: FCM Token saved correctly in database.\n";
} else {
    echo "FAILURE: FCM Token NOT saved.\n";
    echo "Expected: $fcmToken\n";
    echo "Actual: " . ($user ? $user->fcm_token : 'User not found') . "\n";
}
