#!/usr/bin/env php
<?php
/**
 * API Test Script for Football Products
 * Tests all CRUD operations on the Product API
 * 
 * Usage: php test_api.php
 */

$baseUrl = 'http://127.0.0.1:8000';
$apiEndpoint = '/api/products';

// Colors for terminal output
$reset = "\033[0m";
$green = "\033[32m";
$red = "\033[31m";
$yellow = "\033[33m";
$blue = "\033[34m";
$cyan = "\033[36m";

function makeRequest($method, $url, $data = null, $token = null) {
    global $reset, $green, $red, $yellow;
    
    $ch = curl_init($url);
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    
    // Add auth token if provided
    if ($token) {
        $headers[] = "Authorization: Bearer {$token}";
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true),
        'raw' => $response
    ];
}

function printTest($title) {
    global $cyan, $reset;
    echo "\n{$cyan}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$reset}\n";
    echo "{$cyan}📋 {$title}{$reset}\n";
    echo "{$cyan}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$reset}\n";
}

function printResult($result, $response) {
    global $green, $red, $yellow, $reset, $blue;
    
    echo "\n{$blue}Status Code:{$reset} {$response['code']}\n";
    
    if (isset($response['body']['message'])) {
        echo "{$blue}Message:{$reset} {$response['body']['message']}\n";
    }
    
    if (isset($response['body']['data'])) {
        echo "{$blue}Data:{$reset}\n";
        echo json_encode($response['body']['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
    
    if (in_array($response['code'], [200, 201, 204])) {
        echo "{$green}✅ Test passed{$reset}\n";
    } else {
        echo "{$red}❌ Test failed{$reset}\n";
    }
    
    return in_array($response['code'], [200, 201, 204]);
}

echo "\n";
echo "╔════════════════════════════════════════════╗\n";
echo "║  Football Product API Test Suite           ║\n";
echo "║  Base URL: {$baseUrl}                       ║\n";
echo "╚════════════════════════════════════════════╝\n";

// ============================================
// TEST 1: GET ALL PRODUCTS
// ============================================
printTest('Test 1: GET All Products');
$response = makeRequest('GET', $baseUrl . $apiEndpoint);
printResult('Get all products', $response);
$productCount = count($response['body']['data'] ?? []);
echo "{$blue}Products found:{$reset} {$productCount}\n";

// Store first product ID for testing
$firstProductId = null;
if (!empty($response['body']['data'])) {
    $firstProductId = $response['body']['data'][0]['id'];
    echo "{$blue}First product ID:{$reset} {$firstProductId}\n";
}

// ============================================
// TEST 2: CREATE NEW PRODUCT
// ============================================
printTest('Test 2: CREATE New Product');
$newProduct = [
    'name' => 'Crampons Nike Phantom Elite',
    'price' => 145.99,
    'stock' => 15,
    'category' => 'Chaussures',
    'brand' => 'Nike',
    'size' => '43',
    'image' => 'nike_phantom_elite.jpg'
];

echo "{$blue}Creating product:{$reset}\n";
echo json_encode($newProduct, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

$response = makeRequest('POST', $baseUrl . $apiEndpoint, $newProduct);
printResult('Create product', $response);

$createdProductId = null;
if (isset($response['body']['data']['id'])) {
    $createdProductId = $response['body']['data']['id'];
    echo "{$blue}Created product ID:{$reset} {$createdProductId}\n";
}

// ============================================
// TEST 3: GET PRODUCT BY ID
// ============================================
if ($firstProductId) {
    printTest('Test 3: GET Product by ID');
    echo "{$blue}Fetching product ID:{$reset} {$firstProductId}\n";
    
    $response = makeRequest('GET', $baseUrl . $apiEndpoint . '/' . $firstProductId);
    printResult('Get product by ID', $response);
}

// ============================================
// TEST 4: UPDATE PRODUCT
// ============================================
if ($createdProductId) {
    printTest('Test 4: UPDATE Product');
    
    $updateData = [
        'price' => 129.99,
        'stock' => 20,
    ];
    
    echo "{$blue}Updating product ID:{$reset} {$createdProductId}\n";
    echo "{$blue}Update data:{$reset}\n";
    echo json_encode($updateData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    
    $response = makeRequest('PUT', $baseUrl . $apiEndpoint . '/' . $createdProductId, $updateData);
    printResult('Update product', $response);
}

// ============================================
// TEST 5: DELETE PRODUCT
// ============================================
if ($createdProductId) {
    printTest('Test 5: DELETE Product');
    echo "{$blue}Deleting product ID:{$reset} {$createdProductId}\n";
    
    $response = makeRequest('DELETE', $baseUrl . $apiEndpoint . '/' . $createdProductId);
    printResult('Delete product', $response);
}

// ============================================
// SUMMARY
// ============================================
echo "\n";
echo "╔════════════════════════════════════════════╗\n";
echo "║  API Test Suite Complete ✅               ║\n";
echo "╚════════════════════════════════════════════╝\n";
echo "\n{$green}All tests completed! Check results above.{$reset}\n\n";
