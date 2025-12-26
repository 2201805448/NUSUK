<?php

$baseUrl = 'http://127.0.0.1:8000/api';
$email = 'admin_test_' . time() . '@example.com';
$password = 'password123';
$role = 'ADMIN';

function request($method, $url, $data = [], $token = null)
{
    global $baseUrl;
    $ch = curl_init($baseUrl . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if (!empty($data) || $method === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

echo "=== User Management Test Starting ===\n";

// 1. Register ADMIN
echo "\n1. Registering ADMIN user ($email)...\n";
$res = request('POST', '/register', [
    'full_name' => 'Admin Tester',
    'email' => $email,
    'phone_number' => '9999999999',
    'password' => $password,
    'role' => $role
]);

if ($res['code'] !== 201) {
    echo "FAILED: Could not register admin. Code: " . $res['code'] . "\n";
    print_r($res['body']);
    exit(1);
}
echo "SUCCESS: Admin registered.\n";

// 2. Login ADMIN
echo "\n2. Logging in...\n";
$res = request('POST', '/login', [
    'email' => $email,
    'password' => $password
]);

if ($res['code'] !== 200 || !isset($res['body']['token'])) {
    echo "FAILED: Login failed.\n";
    exit(1);
}
$token = $res['body']['token'];
echo "SUCCESS: Logged in. Token received.\n";

// 3. Test GET /stats
echo "\n3. Testing GET /stats...\n";
$res = request('GET', '/stats', [], $token);
if ($res['code'] === 200) {
    echo "SUCCESS: Stats received.\n";
    print_r($res['body']);
} else {
    echo "FAILED: GET /stats - Code: " . $res['code'] . "\n";
    print_r($res['body']);
}

// 4. Test GET /users
echo "\n4. Testing GET /users...\n";
$res = request('GET', '/users', [], $token);
if ($res['code'] === 200) {
    echo "SUCCESS: Users list received. Count: " . count($res['body']['data'] ?? []) . "\n";
} else {
    echo "FAILED: GET /users - Code: " . $res['code'] . "\n";
}

// 5. Test POST /users (Create new user)
echo "\n5. Testing POST /users (Create User)...\n";
$newUserEmail = 'user_created_' . time() . '@example.com';
$res = request('POST', '/users', [
    'full_name' => 'Created User',
    'email' => $newUserEmail,
    'phone_number' => '1231231234',
    'password' => 'password123',
    'role' => 'USER',
    'account_status' => 'ACTIVE'
], $token);

if ($res['code'] === 201) {
    echo "SUCCESS: User created.\n";
    $createdUserId = $res['body']['user']['id'];
} else {
    echo "FAILED: POST /users - Code: " . $res['code'] . "\n";
    print_r($res['body']);
    exit(1); // Cannot proceed if creation failed
}

// 6. Test GET /users/{id}
echo "\n6. Testing GET /users/$createdUserId...\n";
$res = request('GET', "/users/$createdUserId", [], $token);
if ($res['code'] === 200 && $res['body']['email'] === $newUserEmail) {
    echo "SUCCESS: Retrieved correct user.\n";
} else {
    echo "FAILED: GET /users/{id} - Code: " . $res['code'] . "\n";
}

// 7. Test PUT /users/{id}
echo "\n7. Testing PUT /users/$createdUserId...\n";
$res = request('PUT', "/users/$createdUserId", [
    'full_name' => 'Updated User Name'
], $token);
if ($res['code'] === 200 && $res['body']['user']['full_name'] === 'Updated User Name') {
    echo "SUCCESS: User updated.\n";
} else {
    echo "FAILED: PUT /users/{id} - Code: " . $res['code'] . "\n";
    print_r($res['body']);
}

// 8. Test PATCH /users/{id}/status
echo "\n8. Testing PATCH /users/$createdUserId/status...\n";
$res = request('PATCH', "/users/$createdUserId/status", [
    'status' => 'BLOCKED'
], $token);
if ($res['code'] === 200 && $res['body']['user']['account_status'] === 'BLOCKED') {
    echo "SUCCESS: User status updated to BLOCKED.\n";
} else {
    echo "FAILED: PATCH /users/{id}/status - Code: " . $res['code'] . "\n";
    print_r($res['body']);
}

// 9. Test DELETE /users/{id}
echo "\n9. Testing DELETE /users/$createdUserId...\n";
$res = request('DELETE', "/users/$createdUserId", [], $token);
if ($res['code'] === 200) {
    echo "SUCCESS: User deleted.\n";
} else {
    echo "FAILED: DELETE /users/{id} - Code: " . $res['code'] . "\n";
}

// 10. Verify Deletion
echo "\n10. Verifying Deletion...\n";
$res = request('GET', "/users/$createdUserId", [], $token);
if ($res['code'] === 404) {
    echo "SUCCESS: User not found (as expected).\n";
} else {
    echo "FAILED: User still exists or other error. Code: " . $res['code'] . "\n";
}

echo "\n=== User Management Test Complete ===\n";
