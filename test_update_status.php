<?php

$baseUrl = 'http://127.0.0.1:8000/api';
$adminEmail = 'status_tester_admin_' . time() . '@example.com';
$userEmail = 'status_tester_user_' . time() . '@example.com';
$password = 'password123';

function callApi($url, $method = 'GET', $data = [], $token = null)
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
        if (!empty($data))
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        if (!empty($data))
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

echo "=== Testing PATCH /users/{id}/status ===\n\n";

// 1. Register Admin
echo "1. Registering Admin ($adminEmail)...\n";
$res = callApi($baseUrl . '/register', 'POST', [
    'full_name' => 'Admin Tester',
    'email' => $adminEmail,
    'password' => $password,
    'phone_number' => '1234567890',
    'role' => 'ADMIN'
]);

if ($res['code'] !== 201) {
    echo "FAILED: Could not register admin.\n";
    print_r($res['body']);
    exit(1);
}
$token = $res['body']['token'];
echo "SUCCESS: Admin registered.\n\n";

// 2. Create Target User
echo "2. Creating Target User...\n";
$res = callApi($baseUrl . '/users', 'POST', [
    'full_name' => 'Target User',
    'email' => $userEmail,
    'phone_number' => '0987654321',
    'password' => 'password123',
    'role' => 'USER',
    'account_status' => 'ACTIVE'
], $token);

if ($res['code'] !== 201) {
    echo "FAILED: Could not create user.\n";
    print_r($res['body']);
    exit(1);
}
$userId = $res['body']['user']['user_id'];
echo "SUCCESS: User created (ID: $userId) with status ACTIVE.\n\n";

// 3. Test PATCH Status -> BLOCKED
echo "3. Testing PATCH status to BLOCKED...\n";
$res = callApi($baseUrl . "/users/$userId/status", 'PATCH', [
    'status' => 'BLOCKED'
], $token);

echo "Response Code: " . $res['code'] . "\n";
if ($res['code'] === 200 && $res['body']['user']['account_status'] === 'BLOCKED') {
    echo "SUCCESS: Status updated to BLOCKED.\n\n";
} else {
    echo "FAILED: Status update failed.\n";
    print_r($res['body']);
    exit(1);
}

// 4. Test PATCH Status -> ACTIVE
echo "4. Testing PATCH status back to ACTIVE...\n";
$res = callApi($baseUrl . "/users/$userId/status", 'PATCH', [
    'status' => 'ACTIVE'
], $token);

echo "Response Code: " . $res['code'] . "\n";
if ($res['code'] === 200 && $res['body']['user']['account_status'] === 'ACTIVE') {
    echo "SUCCESS: Status updated back to ACTIVE.\n\n";
} else {
    echo "FAILED: Status update failed.\n";
    print_r($res['body']);
    exit(1);
}

// 5. Cleanup
echo "5. Cleaning up (Deleting User)...\n";
callApi($baseUrl . "/users/$userId", 'DELETE', [], $token);
echo "Done.\n";
