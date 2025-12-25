<?php

$baseUrl = 'http://127.0.0.1:8000/api';
$email = 'reset_test_' . time() . '@example.com';
$oldPassword = 'password123';
$newPassword = 'newpassword456';

// Helper function for cURL
function callApi($url, $method = 'POST', $data = [])
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Content-Type: application/json']);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => $response];
}

echo "1. Registering User ($email)...\n";
$reg = callApi($baseUrl . '/register', 'POST', [
    'full_name' => 'Reset Tester',
    'email' => $email,
    'phone_number' => '1112223333',
    'password' => $oldPassword,
    'role' => 'USER'
]);

if ($reg['code'] !== 201) {
    echo "Registration failed: " . $reg['body'] . "\n";
    exit(1);
}
echo "User registered.\n\n";

echo "2. Sending Reset Code...\n";
$send = callApi($baseUrl . '/password/send-code', 'POST', ['email' => $email]);
echo "Response Code: " . $send['code'] . "\n";
echo "Response: " . $send['body'] . "\n";

$otp = json_decode($send['body'], true)['debug_otp'] ?? null;
if (!$otp) {
    echo "FAILURE: Could not retrieve debug OTP.\n";
    exit(1);
}
echo "OTP Received: $otp\n\n";

echo "3. Verifying Code...\n";
$verify = callApi($baseUrl . '/password/verify-code', 'POST', [
    'email' => $email,
    'otp' => (string) $otp
]);
echo "Response Code: " . $verify['code'] . "\n";
echo "Response: " . $verify['body'] . "\n";

if ($verify['code'] !== 200) {
    echo "FAILURE: OTP verification failed.\n";
    exit(1);
}
echo "OTP Verified.\n\n";

echo "4. Resetting Password...\n";
$reset = callApi($baseUrl . '/password/reset', 'POST', [
    'email' => $email,
    'otp' => (string) $otp,
    'password' => $newPassword,
    'password_confirmation' => $newPassword
]);
echo "Response Code: " . $reset['code'] . "\n";
echo "Response: " . $reset['body'] . "\n";

if ($reset['code'] !== 200) {
    echo "FAILURE: Password reset failed.\n";
    exit(1);
}
echo "Password Reset Successful.\n\n";

echo "5. Logging in with NEW Password...\n";
$login = callApi($baseUrl . '/login', 'POST', [
    'email' => $email,
    'password' => $newPassword
]);
echo "Response Code: " . $login['code'] . "\n";

if ($login['code'] === 200) {
    echo "SUCCESS: Logged in with new password!\n";
} else {
    echo "FAILURE: Could not login with new password.\n";
    echo "Response: " . $login['body'] . "\n";
}
