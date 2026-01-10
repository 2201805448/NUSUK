<?php

$baseUrl = 'http://127.0.0.1:8000/api';
$email = 'update_profile_' . time() . '@example.com';
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
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => $response];
}

echo "1. Registering User ($email)...\n";
$reg = callApi($baseUrl . '/register', 'POST', [
    'full_name' => 'Original Name',
    'email' => $email,
    'phone_number' => '1111111111',
    'password' => $password,
    'role' => 'USER'
]);

$body = json_decode($reg['body'], true);
if ($reg['code'] !== 201) {
    echo "FAILURE: Registration Failed.\n" . $reg['body'];
    exit(1);
}
$token = $body['token'];
echo "User Registered.\n\n";

echo "2. Updating Profile (Name & Phone)...\n";
$update = callApi($baseUrl . '/user/profile', 'PUT', [
    'full_name' => 'Updated Name',
    'phone_number' => '9999999999',
    'role' => 'ADMIN' // Should be ignored
], $token);

echo "Response Code: " . $update['code'] . "\n";
echo "Response: " . $update['body'] . "\n";

if ($update['code'] !== 200) {
    echo "FAILURE: Update failed.\n";
    exit(1);
}

$updatedUser = json_decode($update['body'], true)['user'];

if ($updatedUser['full_name'] === 'Updated Name' && $updatedUser['phone_number'] === '9999999999') {
    echo "SUCCESS: Name and Phone updated.\n";
} else {
    echo "FAILURE: Fields not updated.\n";
    exit(1);
}

if ($updatedUser['role'] === 'USER') {
    echo "SUCCESS: Role change ignored (Security check passed).\n";
} else {
    echo "FAILURE: Role was changed! Security Vulnerability!\n";
    exit(1);
}
