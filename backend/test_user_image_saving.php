<?php
/**
 * Test if user-generated images are saved to the gallery
 */

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "🧪 Testing User Image Saving to Gallery\n";
echo "=" . str_repeat("=", 70) . "\n\n";

// Step 1: Login as admin
echo "🔐 Step 1: Logging in as admin...\n";

$client = new Client();

try {
    $response = $client->post(
        'http://127.0.0.1:8080/api/auth/login',
        [
            'json' => [
                'email' => 'admin@example.com',
                'password' => 'admin123456'
            ],
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]
    );

    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);

    if (isset($data['token'])) {
        $token = $data['token'];
        $userId = $data['user']['id'];
        echo "✅ Login successful\n";
        echo "   User ID: $userId\n";
        echo "   Token: " . substr($token, 0, 30) . "...\n\n";
    } else {
        die("❌ Login failed\n");
    }

} catch (\Exception $e) {
    die("❌ Exception: " . $e->getMessage() . "\n");
}

// Step 2: Generate an image
echo "🎨 Step 2: Generating an image...\n";

$requestBody = [
    'prompt' => 'A beautiful sunset over the ocean',
    'model' => 'qwen-image-2.0',
    'size' => '1024*1024'
];

try {
    $response = $client->post(
        'http://127.0.0.1:8080/api/image/generate/bailian',
        [
            'json' => $requestBody,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ],
            'timeout' => 60
        ]
    );

    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);

    if ($data['success']) {
        echo "✅ Image generated successfully\n";
        if (isset($data['images'])) {
            echo "   Image URL: " . substr($data['images'][0], 0, 80) . "...\n";
        }
    } else {
        echo "❌ Failed: " . ($data['error'] ?? 'unknown error') . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Step 3: Check if image was saved to gallery
echo "📸 Step 3: Checking if image was saved to gallery...\n";

try {
    $response = $client->get(
        "http://127.0.0.1:8080/api/gallery/user/$userId",
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ]
        ]
    );

    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);

    if ($data['success']) {
        $images = $data['data']['data'] ?? [];
        echo "✅ Gallery retrieved successfully\n";
        echo "   Total images: " . count($images) . "\n";
        
        if (count($images) > 0) {
            echo "   Latest image:\n";
            $latest = $images[0];
            echo "     - Model: " . ($latest['model'] ?? 'unknown') . "\n";
            echo "     - Prompt: " . substr($latest['prompt'] ?? '', 0, 50) . "...\n";
            echo "     - Created: " . ($latest['created_at'] ?? 'unknown') . "\n";
        }
    } else {
        echo "❌ Failed: " . ($data['error'] ?? 'unknown error') . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "✅ Test completed\n";
