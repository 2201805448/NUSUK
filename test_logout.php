<?php

$baseUrl = 'http://127.0.0.1:8000/api';
$email = 'test_logout_' . time() . '@example.com';
$password = 'password123';

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
    } else { // GET
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => $response];
}

echo "1. Registering user ($email) to get token...\n";
$regResponse = callApi($baseUrl . '/register', 'POST', [
    'full_name' => 'Logout Tester',
    'email' => $email,
    'phone_number' => '9876543210',
    'password' => $password,
    'role' => 'USER'
]);

if ($regResponse['code'] !== 201) {
    echo "Registration failed. " . $regResponse['body'] . "\n";
    exit(1);
}

$token = json_decode($regResponse['body'], true)['token'];
echo "Token received: " . substr($token, 0, 10) . "...\n\n";

echo "2. Testing Access WITH Token (Expect 200)...\n";
$userResponse = callApi($baseUrl . '/user', 'GET', [], $token);
echo "Response Code: " . $userResponse['code'] . "\n";
if ($userResponse['code'] === 200) {
    echo "SUCCESS: User accessed protected route.\n\n";
} else {
    echo "FAILURE: Could not access protected route.\n";
    exit(1);
}

echo "3. Logging Out...\n";
$logoutResponse = callApi($baseUrl . '/logout', 'POST', [], $token);
echo "Response Code: " . $logoutResponse['code'] . "\n";
echo "Response: " . $logoutResponse['body'] . "\n\n";

if ($logoutResponse['code'] === 200) {
    echo "SUCCESS: Logout request successful.\n\n";
} else {
    echo "FAILURE: Logout request failed.\n";
    exit(1);
}

echo "4. Testing Access AFTER Logout (Expect 401)...\n";
$revokedResponse = callApi($baseUrl . '/user', 'GET', [], $token);
echo "Response Code: " . $revokedResponse['code'] . "\n";
echo "Response: " . $revokedResponse['body'] . "\n";

if ($revokedResponse['code'] === 401) {
    echo "SUCCESS: Token successfully revoked! (401 Unauthorized)\n";
} else {
    echo "FAILURE: Token still active or other error.\n";
}
