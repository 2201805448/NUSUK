<?php

$baseUrl = 'http://127.0.0.1:8000/api';
$email = 'change_pass_' . time() . '@example.com';
$originalPassword = 'password123';
$newPassword = 'newpassword999';

// Helper function for cURL
function callApi($url, $method = 'POST', $data = [], $token = null)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

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
    'full_name' => 'Change Password Tester',
    'email' => $email,
    'phone_number' => '5556667777',
    'password' => $originalPassword,
    'role' => 'USER'
]);

if ($reg['code'] !== 201) {
    echo "Registration failed: " . $reg['body'] . "\n";
    exit(1);
}
$token = json_decode($reg['body'], true)['token'];
echo "User registered. Token: " . substr($token, 0, 10) . "...\n\n";

echo "2. Attempting Change Password (Wrong Current Password)...\n";
$fail = callApi($baseUrl . '/password/change', 'POST', [
    'current_password' => 'wrongpassword',
    'new_password' => $newPassword,
    'new_password_confirmation' => $newPassword
], $token);
echo "Response Code: " . $fail['code'] . "\n";
echo "Response: " . $fail['body'] . "\n";

if ($fail['code'] !== 400) {
    echo "FAILURE: Should have failed with 400.\n";
    exit(1);
}
echo "Correctly rejected wrong password.\n\n";

echo "3. Changing Password (Correct Credentials)...\n";
$success = callApi($baseUrl . '/password/change', 'POST', [
    'current_password' => $originalPassword,
    'new_password' => $newPassword,
    'new_password_confirmation' => $newPassword
], $token);
echo "Response Code: " . $success['code'] . "\n";
echo "Response: " . $success['body'] . "\n";

if ($success['code'] !== 200) {
    echo "FAILURE: Password change failed.\n";
    exit(1);
}
echo "Password Changed Successfully.\n\n";

echo "4. Logging out...\n";
callApi($baseUrl . '/logout', 'POST', [], $token);

echo "5. Login with OLD Password (Should Fail)...\n";
$loginOld = callApi($baseUrl . '/login', 'POST', [
    'email' => $email,
    'password' => $originalPassword
]);
echo "Response Code: " . $loginOld['code'] . "\n";
if ($loginOld['code'] !== 401) {
    echo "FAILURE: Old password still works!\n";
    exit(1);
}
echo "Old password rejected (Correct).\n\n";

echo "6. Login with NEW Password (Should Succeed)...\n";
$loginNew = callApi($baseUrl . '/login', 'POST', [
    'email' => $email,
    'password' => $newPassword
]);
echo "Response Code: " . $loginNew['code'] . "\n";
if ($loginNew['code'] === 200) {
    echo "SUCCESS: Logged in with new password!\n";
} else {
    echo "FAILURE: New password login failed.\n";
    echo "Response: " . $loginNew['body'] . "\n";
}
