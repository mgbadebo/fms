<?php

/**
 * Farm Management System API Test Script
 * 
 * This script demonstrates the full API workflow:
 * 1. Authentication
 * 2. Creating a farm and related entities
 * 3. Creating a harvest lot
 * 4. Getting weight from scale
 * 5. Printing a label
 * 
 * Usage: php test-api.php
 */

$baseUrl = 'http://127.0.0.1:8000/api/v1';
$token = null;

// Colors for terminal output
$green = "\033[32m";
$blue = "\033[34m";
$yellow = "\033[33m";
$red = "\033[31m";
$reset = "\033[0m";

function printStep($step, $message) {
    global $green, $reset;
    echo "\n{$green}=== Step $step: $message ==={$reset}\n";
}

function printSuccess($message) {
    global $green, $reset;
    echo "{$green}✓{$reset} $message\n";
}

function printError($message) {
    global $red, $reset;
    echo "{$red}✗{$reset} $message\n";
}

function printInfo($message) {
    global $blue, $reset;
    echo "{$blue}ℹ{$reset} $message\n";
}

function makeRequest($method, $url, $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $headers[] = 'Content-Type: application/json';
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error, 'http_code' => 0];
    }
    
    return [
        'http_code' => $httpCode,
        'data' => json_decode($response, true),
        'raw' => $response
    ];
}

// Step 1: Login
printStep(1, "Authentication");
printInfo("Logging in as admin@fms.test...");

$loginResponse = makeRequest('POST', "$baseUrl/login", [
    'email' => 'admin@fms.test',
    'password' => 'password'
]);

if ($loginResponse['http_code'] === 200 && isset($loginResponse['data']['token'])) {
    $token = $loginResponse['data']['token'];
    printSuccess("Login successful!");
    printInfo("Token: " . substr($token, 0, 20) . "...");
} else {
    printError("Login failed!");
    printError("Response: " . $loginResponse['raw']);
    exit(1);
}

$authHeader = "Authorization: Bearer $token";

// Step 2: Create a Farm
printStep(2, "Creating Farm");
$farmData = [
    'name' => 'Test Farm ' . date('Y-m-d H:i:s'),
    'location' => '123 Test Farm Road',
    'description' => 'A test farm created by the API test script',
    'total_area' => 100.5,
    'area_unit' => 'hectares',
    'is_active' => true
];

$farmResponse = makeRequest('POST', "$baseUrl/farms", $farmData, [$authHeader]);

if ($farmResponse['http_code'] === 201 && isset($farmResponse['data']['data']['id'])) {
    $farmId = $farmResponse['data']['data']['id'];
    printSuccess("Farm created with ID: $farmId");
    $farmName = $farmResponse['data']['data']['name'];
} else {
    printError("Failed to create farm");
    printError("Response: " . $farmResponse['raw']);
    exit(1);
}

// Step 3: Create a Season
printStep(3, "Creating Season");
$seasonData = [
    'farm_id' => $farmId,
    'name' => '2024 Test Season',
    'start_date' => '2024-01-01',
    'end_date' => '2024-12-31',
    'status' => 'ACTIVE'
];

// We need to create a season via direct database or create a SeasonController
// For now, let's check if we can get existing seasons or create via tinker
printInfo("Note: Season creation requires a SeasonController. Creating via direct model access...");
printInfo("You can create seasons via: php artisan tinker");
printInfo("Or add a SeasonController to the API");

// Step 4: Get or Create a Crop
printStep(4, "Getting/Creating Crop");
printInfo("Note: Crop creation requires a CropController. Using existing crops...");
printInfo("You can create crops via: php artisan tinker");

// Step 5: Create a Field
printStep(5, "Creating Field");
// Note: We need a FieldController too. Let's create it via tinker for now
printInfo("Note: Field creation requires a FieldController.");
printInfo("For this demo, we'll assume you have existing data.");

// Step 6: Create a Scale Device
printStep(6, "Creating Scale Device");
$scaleDeviceData = [
    'farm_id' => $farmId,
    'name' => 'Test Scale Device',
    'connection_type' => 'MOCK',
    'connection_config' => ['unit' => 'kg'],
    'is_active' => true
];

$scaleDeviceResponse = makeRequest('POST', "$baseUrl/scale-devices", $scaleDeviceData, [$authHeader]);

if ($scaleDeviceResponse['http_code'] === 201 && isset($scaleDeviceResponse['data']['data']['id'])) {
    $scaleDeviceId = $scaleDeviceResponse['data']['data']['id'];
    printSuccess("Scale device created with ID: $scaleDeviceId");
} else {
    printError("Failed to create scale device");
    printError("Response: " . $scaleDeviceResponse['raw']);
    exit(1);
}

// Step 7: Create a Label Template
printStep(7, "Creating Label Template");
$labelTemplateData = [
    'farm_id' => $farmId,
    'name' => 'Test Harvest Lot Label',
    'code' => 'TEST_HARVEST_LOT_LABEL_' . time(),
    'target_type' => 'HARVEST_LOT',
    'template_engine' => 'RAW',
    'template_body' => "Harvest Lot: {{code}}\nWeight: {{net_weight}} {{weight_unit}}\nTraceability ID: {{traceability_id}}\nField: {{field_name}}\nHarvested: {{harvested_at}}",
    'is_default' => false
];

$labelTemplateResponse = makeRequest('POST', "$baseUrl/label-templates", $labelTemplateData, [$authHeader]);

if ($labelTemplateResponse['http_code'] === 201 && isset($labelTemplateResponse['data']['data']['id'])) {
    $labelTemplateId = $labelTemplateResponse['data']['data']['id'];
    printSuccess("Label template created with ID: $labelTemplateId");
} else {
    printError("Failed to create label template");
    printError("Response: " . $labelTemplateResponse['raw']);
    exit(1);
}

// Step 8: Create a Harvest Lot (requires existing field and season)
printStep(8, "Creating Harvest Lot");
printInfo("Note: Harvest lot creation requires existing field_id and season_id.");
printInfo("For this demo, we'll show the structure but you may need to create these first.");

// Get existing farms to find related data
$farmsResponse = makeRequest('GET', "$baseUrl/farms", null, [$authHeader]);
if ($farmsResponse['http_code'] === 200 && !empty($farmsResponse['data']['data'])) {
    $firstFarm = $farmsResponse['data']['data'][0];
    printInfo("Found farm: " . $firstFarm['name']);
    
    // Try to create harvest lot if we can get field/season IDs
    // For now, just show the structure
    printInfo("To create a harvest lot, you need:");
    printInfo("  - field_id (create via FieldController or tinker)");
    printInfo("  - season_id (create via SeasonController or tinker)");
    printInfo("  - Optional: crop_plan_id, zone_id");
}

// Step 9: Test Scale Reading (if we have a harvest lot)
printStep(9, "Testing Scale Reading");
printInfo("Scale reading requires a context (HarvestLot, StorageUnit, or SalesOrder)");
printInfo("Example request structure:");
echo json_encode([
    'scale_device_id' => $scaleDeviceId,
    'context_type' => 'App\Models\HarvestLot',
    'context_id' => 1, // Would be actual harvest lot ID
    'unit' => 'kg'
], JSON_PRETTY_PRINT) . "\n";

// Step 10: Test Label Printing (if we have a harvest lot)
printStep(10, "Testing Label Printing");
printInfo("Label printing requires a target (HarvestLot, StorageUnit, or SalesOrder)");
printInfo("Example request structure:");
echo json_encode([
    'label_template_id' => $labelTemplateId,
    'target_type' => 'App\Models\HarvestLot',
    'target_id' => 1, // Would be actual harvest lot ID
    'printer_name' => 'Test Printer'
], JSON_PRETTY_PRINT) . "\n";

// Summary
echo "\n{$green}=== Test Summary ==={$reset}\n";
printSuccess("Authentication: Working");
printSuccess("Farm Creation: Working");
printSuccess("Scale Device Creation: Working");
printSuccess("Label Template Creation: Working");
printInfo("Note: Some endpoints require additional controllers (Season, Field, Crop)");
printInfo("You can create these entities via:");
printInfo("  1. Add controllers for Season, Field, Crop");
printInfo("  2. Use php artisan tinker");
printInfo("  3. Use the database seeder: php artisan db:seed");

echo "\n{$blue}Next Steps:{$reset}\n";
echo "1. Add controllers for Season, Field, Crop, Zone, CropPlan\n";
echo "2. Or use tinker to create test data:\n";
echo "   php artisan tinker\n";
echo "   \$farm = App\Models\Farm::first();\n";
echo "   \$season = App\Models\Season::create(['farm_id' => \$farm->id, 'name' => '2024', 'start_date' => '2024-01-01', 'end_date' => '2024-12-31', 'status' => 'ACTIVE']);\n";
echo "   \$field = App\Models\Field::create(['farm_id' => \$farm->id, 'name' => 'Field A', 'area' => 10.0]);\n";
echo "3. Then test the full workflow with harvest lots, scale readings, and label printing\n";

echo "\n{$green}Test script completed!{$reset}\n";

