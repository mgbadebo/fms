<?php

/**
 * Simple API Test Script for Farm Management System
 * 
 * Usage: php test-api-simple.php
 */

$baseUrl = 'http://127.0.0.1:8000/api/v1';

echo "\n=== Farm Management System API Test ===\n\n";

// Step 1: Login
echo "Step 1: Logging in...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'admin@fms.test',
    'password' => 'password'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "ERROR: $error\n";
    exit(1);
}

if ($httpCode !== 200) {
    echo "Login failed! HTTP Code: $httpCode\n";
    echo "Response: $response\n";
    exit(1);
}

$data = json_decode($response, true);
if (!isset($data['token'])) {
    echo "ERROR: No token in response\n";
    echo "Response: $response\n";
    exit(1);
}

$token = $data['token'];
echo "✓ Login successful! Token: " . substr($token, 0, 30) . "...\n\n";

// Step 2: Create a Farm
echo "Step 2: Creating a farm...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/farms");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'name' => 'Test Farm ' . date('H:i:s'),
    'location' => '123 Test Road',
    'description' => 'Created by test script',
    'total_area' => 100.5,
    'area_unit' => 'hectares',
    'is_active' => true
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $token"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 201) {
    $farmData = json_decode($response, true);
    $farmId = $farmData['data']['id'] ?? null;
    echo "✓ Farm created! ID: $farmId\n\n";
} else {
    echo "✗ Failed to create farm. HTTP Code: $httpCode\n";
    echo "Response: $response\n\n";
}

// Step 3: Get all farms
echo "Step 3: Getting all farms...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/farms");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $token"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $farmsData = json_decode($response, true);
    $farmCount = count($farmsData['data'] ?? []);
    echo "✓ Retrieved $farmCount farm(s)\n\n";
} else {
    echo "✗ Failed to get farms. HTTP Code: $httpCode\n\n";
}

// Step 4: Create a Scale Device
echo "Step 4: Creating a scale device...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/scale-devices");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'farm_id' => $farmId ?? 1,
    'name' => 'Test Scale Device',
    'connection_type' => 'MOCK',
    'connection_config' => ['unit' => 'kg'],
    'is_active' => true
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $token"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 201) {
    $scaleData = json_decode($response, true);
    $scaleId = $scaleData['data']['id'] ?? null;
    echo "✓ Scale device created! ID: $scaleId\n\n";
} else {
    echo "✗ Failed to create scale device. HTTP Code: $httpCode\n";
    echo "Response: $response\n\n";
}

// Step 5: Create a Label Template
echo "Step 5: Creating a label template...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/label-templates");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'farm_id' => $farmId ?? 1,
    'name' => 'Test Label Template',
    'code' => 'TEST_LABEL_' . time(),
    'target_type' => 'HARVEST_LOT',
    'template_engine' => 'RAW',
    'template_body' => "Harvest Lot: {{code}}\nWeight: {{net_weight}} {{weight_unit}}\nTraceability: {{traceability_id}}",
    'is_default' => false
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $token"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 201) {
    $labelData = json_decode($response, true);
    $labelId = $labelData['data']['id'] ?? null;
    echo "✓ Label template created! ID: $labelId\n\n";
} else {
    echo "✗ Failed to create label template. HTTP Code: $httpCode\n";
    echo "Response: $response\n\n";
}

echo "=== Test Complete ===\n";
echo "\nNext steps:\n";
echo "1. Create a season, field, and crop plan (via tinker or add controllers)\n";
echo "2. Create a harvest lot\n";
echo "3. Test scale reading: POST /api/v1/scale-readings\n";
echo "4. Test label printing: POST /api/v1/labels/print\n";
echo "\n";

