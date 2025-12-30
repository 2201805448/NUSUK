<?php

$baseUrl = 'http://127.0.0.1:8000/api';
$email = 'admin_export_' . time() . '@example.com';
$password = 'password123';
$role = 'ADMIN';

function request($method, $url, $data = [], $token = null)
{
    global $baseUrl;
    $ch = curl_init($baseUrl . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Follow redirects to handle download responses if any
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $headers = ['Accept: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }

    // For POST, set Content-Type
    if ($method === 'POST') {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => $response, 'type' => $contentType];
}

echo "=== Export Reports Test Starting ===\n";

// 1. Register ADMIN
echo "\n1. Registering ADMIN user...\n";
$res = request('POST', '/register', [
    'full_name' => 'Admin Export',
    'email' => $email,
    'phone_number' => '9999999999',
    'password' => $password,
    'role' => $role
]);

if ($res['code'] !== 201) {
    echo "FAILED: Could not register admin. Code: " . $res['code'] . "\n";
    exit(1);
}
echo "SUCCESS: Admin registered.\n";

// 2. Login ADMIN
echo "\n2. Logging in...\n";
$res = request('POST', '/login', [
    'email' => $email,
    'password' => $password
]);
$data = json_decode($res['body'], true);
$token = $data['token'] ?? null;

if (!$token) {
    echo "FAILED: Login failed.\n";
    exit(1);
}
echo "SUCCESS: Logged in.\n";

// 3. Test Excel Export
echo "\n3. Testing Excel Export...\n";
$res = request('GET', '/reports/trips/export?format=excel', [], $token);
if ($res['code'] === 200) {
    echo "SUCCESS: Excel export response received.\n";
    // Check if content type matches excel
    // Usually: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
    echo "Content-Type: " . $res['type'] . "\n";
    if (strpos($res['type'], 'spreadsheet') !== false || strpos($res['type'], 'excel') !== false) {
        echo "Valid Excel Content-Type.\n";
        file_put_contents('test_export_trips.xlsx', $res['body']);
        echo "Saved to test_export_trips.xlsx\n";
    } else {
        echo "WARNING: Unexpected Content-Type for Excel.\n";
    }
} else {
    echo "FAILED: Excel Export - Code: " . $res['code'] . "\n";
    echo substr($res['body'], 0, 500) . "\n";
}

// 4. Test PDF Export
echo "\n4. Testing PDF Export...\n";
$res = request('GET', '/reports/trips/export?format=pdf', [], $token);
if ($res['code'] === 200) {
    echo "SUCCESS: PDF export response received.\n";
    echo "Content-Type: " . $res['type'] . "\n";
    if (strpos($res['type'], 'pdf') !== false) {
        echo "Valid PDF Content-Type.\n";
        file_put_contents('test_export_trips.pdf', $res['body']);
        echo "Saved to test_export_trips.pdf\n";
    } else {
        echo "WARNING: Unexpected Content-Type for PDF.\n";
    }
} else {
    echo "FAILED: PDF Export - Code: " . $res['code'] . "\n";
    echo substr($res['body'], 0, 500) . "\n";
}

// 5. Test CSV Export
echo "\n5. Testing CSV Export...\n";
$res = request('GET', '/reports/trips/export?format=csv', [], $token);
if ($res['code'] === 200) {
    echo "SUCCESS: CSV export response received.\n";
    echo "Content-Type: " . $res['type'] . "\n";
    // text/csv or text/plain
    file_put_contents('test_export_trips.csv', $res['body']);
    echo "Saved to test_export_trips.csv\n";
} else {
    echo "FAILED: CSV Export - Code: " . $res['code'] . "\n";
}

echo "\n=== Export Reports Test Complete ===\n";
