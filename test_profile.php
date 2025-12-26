<?php

$baseUrl = 'http://127.0.0.1:8000/api';
$email = 'profile_test_' . time() . '@example.com';
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
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => $response];
}

echo "1. Registering User ($email)...\n";
$reg = callApi($baseUrl . '/register', 'POST', [
    'full_name' => 'Profile Tester',
    'email' => $email,
    'phone_number' => '1234567890',
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

echo "2. Fetching Profile...\n";
$profile = callApi($baseUrl . '/profile', 'GET', [], $token);
echo "Response Code: " . $profile['code'] . "\n";
echo "Response: " . $profile['body'] . "\n";

if ($profile['code'] !== 200) {
    echo "FAILURE: Could not fetch profile.\n";
    exit(1);
}

$data = json_decode($profile['body'], true);
if (isset($data['user']['email']) && $data['user']['email'] === $email) {
    echo "SUCCESS: Profile contains correct user info.\n";
} else {
    echo "FAILURE: Profile data mismatch.\n";
}

if (isset($data['services_history'])) {
    echo "SUCCESS: Services history key present (Empty array expected for new user).\n";
} else {
    echo "FAILURE: Missing services_history key.\n";
}
