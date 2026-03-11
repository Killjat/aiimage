<?php
/**
 * Test the /api/image/save endpoint
 * Simulates frontend calling the save API after image generation
 */

require_once __DIR__ . '/vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

// Create app
$app = AppFactory::create();

// Load routes
require_once __DIR__ . '/src/routes.php';

// Test 1: Save image with valid token
echo "=== Testing /api/image/save Endpoint ===\n\n";

echo "Test 1: Save image with valid auth token\n";
try {
    // Create a mock request
    $streamFactory = new StreamFactory();
    $body = $streamFactory->createStream(json_encode([
        'model' => 'qwen-image-2.0-pro',
        'prompt' => 'A beautiful sunset over mountains',
        'imageUrl' => 'https://example.com/test-image.jpg',
        'size' => '1024*1024',
        'negativePrompt' => 'blurry, low quality'
    ]));
    
    $request = ServerRequestFactory::fromGlobals()
        ->withMethod('POST')
        ->withUri('http://127.0.0.1:8080/api/image/save')
        ->withBody($body)
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('Authorization', 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MSwidXNlcm5hbWUiOiJhZG1pbkBleGFtcGxlLmNvbSIsImVtYWlsIjoiYWRtaW5AZXhhbXBsZS5jb20iLCJpYXQiOjE3MDAwMDAwMDAsImV4cCI6OTk5OTk5OTk5OX0.test');
    
    // Run the app
    $response = $app->handle($request);
    
    $statusCode = $response->getStatusCode();
    $body = (string)$response->getBody();
    $data = json_decode($body, true);
    
    echo "Status: $statusCode\n";
    echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($statusCode === 200 && $data['success']) {
        echo "✅ Image save endpoint working correctly\n\n";
    } else {
        echo "⚠️  Unexpected response\n\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// Test 2: Save image without auth (guest)
echo "Test 2: Save image without auth (guest)\n";
try {
    $streamFactory = new StreamFactory();
    $body = $streamFactory->createStream(json_encode([
        'model' => 'qwen-image-2.0-pro',
        'prompt' => 'Test prompt',
        'imageUrl' => 'https://example.com/test.jpg'
    ]));
    
    $request = ServerRequestFactory::fromGlobals()
        ->withMethod('POST')
        ->withUri('http://127.0.0.1:8080/api/image/save')
        ->withBody($body)
        ->withHeader('Content-Type', 'application/json');
    
    $response = $app->handle($request);
    
    $statusCode = $response->getStatusCode();
    $body = (string)$response->getBody();
    $data = json_decode($body, true);
    
    echo "Status: $statusCode\n";
    echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($statusCode === 403) {
        echo "✅ Guest correctly denied from saving\n\n";
    } else {
        echo "⚠️  Unexpected response\n\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Save image without imageUrl
echo "Test 3: Save image without imageUrl (should fail)\n";
try {
    $streamFactory = new StreamFactory();
    $body = $streamFactory->createStream(json_encode([
        'model' => 'qwen-image-2.0-pro',
        'prompt' => 'Test prompt'
    ]));
    
    $request = ServerRequestFactory::fromGlobals()
        ->withMethod('POST')
        ->withUri('http://127.0.0.1:8080/api/image/save')
        ->withBody($body)
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('Authorization', 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MSwidXNlcm5hbWUiOiJhZG1pbkBleGFtcGxlLmNvbSIsImVtYWlsIjoiYWRtaW5AZXhhbXBsZS5jb20iLCJpYXQiOjE3MDAwMDAwMDAsImV4cCI6OTk5OTk5OTk5OX0.test');
    
    $response = $app->handle($request);
    
    $statusCode = $response->getStatusCode();
    $body = (string)$response->getBody();
    $data = json_decode($body, true);
    
    echo "Status: $statusCode\n";
    echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($statusCode === 400) {
        echo "✅ Missing imageUrl correctly rejected\n\n";
    } else {
        echo "⚠️  Unexpected response\n\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

echo "=== Test Complete ===\n";
