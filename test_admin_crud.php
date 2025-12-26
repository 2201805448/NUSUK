<?php

$baseUrl = 'http://127.0.0.1:8000/api';
$adminEmail = 'admin_' . time() . '@example.com';
$userEmail = 'user_' . time() . '@example.com';
$newUserEmail = 'created_by_admin_' . time() . '@example.com';
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

    // Convert method to proper cURL option
    if ($method === 'GET') {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    } elseif ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (!empty($data))
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if (!empty($data))
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    } elseif ($method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        if (!empty($data))
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => $response];
}

// 1. Setup: Register Regular User
echo "1. Registering Regular User ($userEmail)...\n";
$regUser = callApi($baseUrl . '/register', 'POST', [
    'full_name' => 'Regular User',
    'email' => $userEmail,
    'phone_number' => '1111111111',
    'password' => $password,
    'role' => 'USER'
]);
$body = json_decode($regUser['body'], true);
if ($regUser['code'] !== 201) {
    echo "FAILURE: User Registration Failed.\n" . $regUser['body'];
    exit(1);
}
$userToken = $body['token'];
echo "Regular User Registered.\n\n";

// 2. Setup: Register Admin
echo "2. Registering Admin ($adminEmail)...\n";
$regAdmin = callApi($baseUrl . '/register', 'POST', [
    'full_name' => 'System Admin',
    'email' => $adminEmail,
    'phone_number' => '9999999999',
    'password' => $password,
    'role' => 'ADMIN'
]);
$body = json_decode($regAdmin['body'], true);
if ($regAdmin['code'] !== 201) {
    echo "FAILURE: Admin Registration Failed.\n" . $regAdmin['body'];
    exit(1);
}
$adminToken = $body['token'];
echo "Admin Registered.\n\n";

// 3. Test Access Control (Access Denied)
echo "3. Testing Forbidden Access (Regular User -> POST /users)...\n";
$fail = callApi($baseUrl . '/users', 'POST', [], $userToken);
echo "Response Code: " . $fail['code'] . "\n";
if ($fail['code'] !== 403) {
    echo "FAILURE: Regular user was not forbidden!\n";
    exit(1);
}
echo "SUCCESS: Access correctly denied.\n\n";

// 4. Test Create User (Admin)
echo "4. Admin Creating New User...\n";
$create = callApi($baseUrl . '/users', 'POST', [
    'full_name' => 'Created By Admin',
    'email' => $newUserEmail,
    'phone_number' => '5555555555',
    'password' => 'secretpass',
    'role' => 'PILGRIM',
    'account_status' => 'ACTIVE'
], $adminToken);
echo "Response Code: " . $create['code'] . "\n";
$createdUser = json_decode($create['body'], true)['user'] ?? null;
if ($create['code'] !== 201 || !$createdUser) {
    echo "FAILURE: Could not create user.\n" . $create['body'];
    exit(1);
}
$newUserId = $createdUser['user_id'];
echo "SUCCESS: User created (ID: $newUserId).\n\n";

// 5. Test Read User (Admin)
echo "5. Admin Reading User Details...\n";
$read = callApi($baseUrl . '/users/' . $newUserId, 'GET', [], $adminToken);
if ($read['code'] === 200) {
    echo "SUCCESS: Retrieved user details.\n\n";
} else {
    echo "FAILURE: Could not read user.\n";
    exit(1);
}

// 6. Test Update User (Admin)
echo "6. Admin Updating User...\n";
$update = callApi($baseUrl . '/users/' . $newUserId, 'PUT', [
    'full_name' => 'Updated Name',
    'account_status' => 'BLOCKED'
], $adminToken);
echo "Response Code: " . $update['code'] . "\n";
$updatedUser = json_decode($update['body'], true)['user'];
if ($updatedUser['full_name'] === 'Updated Name' && $updatedUser['account_status'] === 'BLOCKED') {
    echo "SUCCESS: User updated successfully.\n\n";
} else {
    echo "FAILURE: Update failed.\n";
    exit(1);
}

// 7. Test Delete User (Admin)
echo "7. Admin Deleting User...\n";
$delete = callApi($baseUrl . '/users/' . $newUserId, 'DELETE', [], $adminToken);
echo "Response Code: " . $delete['code'] . "\n";
if ($delete['code'] === 200) {
    echo "SUCCESS: User deleted.\n\n";
} else {
    echo "FAILURE: Delete failed.\n";
    exit(1);
}

// 8. Verify Deletion
echo "8. Verifying Deletion...\n";
$check = callApi($baseUrl . '/users/' . $newUserId, 'GET', [], $adminToken);
if ($check['code'] === 404) {
    echo "SUCCESS: User not found (404) as expected.\n";
} else {
    echo "FAILURE: User still exists or other error.\n";
}
