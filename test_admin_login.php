<?php
/**
 * Test Admin Login with role-based redirect
 * 
 * Tests login with admin credentials and verifies the redirect_url is set correctly
 */

$baseUrl = 'http://127.0.0.1:8000/api';

echo "=== Testing Admin Login with Role-Based Redirect ===\n\n";

// Admin credentials
$credentials = [
    'email' => 'doaa@gmail.com',
    'password' => '0924576189'
];

echo "Attempting login with:\n";
echo "  Email: {$credentials['email']}\n";
echo "  Password: ********\n\n";

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($credentials));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n";
echo "Response:\n";

$data = json_decode($response, true);
if ($data) {
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

    // Verify the role and redirect_url
    if (isset($data['role'])) {
        echo "Role: {$data['role']}\n";
    } else {
        echo "WARNING: Role not found in response!\n";
    }

    if (isset($data['redirect_url'])) {
        echo "Redirect URL: {$data['redirect_url']}\n";

        if ($data['role'] === 'ADMIN' && $data['redirect_url'] === '/admin/dashboard') {
            echo "\n✓ SUCCESS: Admin user correctly gets /admin/dashboard redirect\n";
        } elseif ($data['role'] !== 'ADMIN') {
            echo "\n✓ Info: Non-admin user, redirect is {$data['redirect_url']}\n";
        } else {
            echo "\n✗ ERROR: Admin should redirect to /admin/dashboard but got {$data['redirect_url']}\n";
        }
    } else {
        echo "WARNING: redirect_url not found in response!\n";
    }
} else {
    echo $response . "\n";
}

echo "\n=== Test Complete ===\n";
