<?php
/**
 * Test frontend integration with image editing
 * Simulates what the frontend would send
 */

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['ALIBABA_BAILIAN_API_KEY'] ?? '';

if (empty($apiKey)) {
    die("❌ ALIBABA_BAILIAN_API_KEY not configured\n");
}

echo "🧪 Testing Frontend Integration with Image Editing\n";
echo "=" . str_repeat("=", 70) . "\n\n";

// Create a test image
echo "📸 Creating test image...\n";
$image = imagecreatetruecolor(400, 300);
$white = imagecolorallocate($image, 255, 255, 255);
$black = imagecolorallocate($image, 0, 0, 0);
imagefill($image, 0, 0, $white);
imagefilledellipse($image, 200, 150, 100, 100, $black);

ob_start();
imagejpeg($image);
$imageData = ob_get_clean();

$base64Image = 'data:image/jpeg;base64,' . base64_encode($imageData);
echo "✅ Test image created\n\n";

// Test 1: qwen-image-2.0 with image editing
echo "🧪 Test 1: qwen-image-2.0 with image editing\n";
echo "   Simulating frontend request...\n";

$client = new Client();

$requestBody = [
    'prompt' => 'Change the background to blue',
    'model' => 'qwen-image-2.0',
    'ref_image' => $base64Image,
    'size' => '1024*1024'
];

echo "   Request body size: " . strlen(json_encode($requestBody)) . " bytes\n";

try {
    $response = $client->post(
        'http://127.0.0.1:8080/api/image/generate/bailian',
        [
            'json' => $requestBody,
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'timeout' => 60
        ]
    );

    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);

    if ($data['success']) {
        echo "   ✅ Success!\n";
        echo "   Status: " . ($data['status'] ?? 'unknown') . "\n";
        if (isset($data['images'])) {
            echo "   Images: " . count($data['images']) . "\n";
            echo "   Image URL: " . substr($data['images'][0], 0, 80) . "...\n";
        }
        if (isset($data['prompt_extended'])) {
            echo "   Prompt extended: " . ($data['prompt_extended'] ? 'yes' : 'no') . "\n";
        }
    } else {
        echo "   ❌ Failed: " . ($data['error'] ?? 'unknown error') . "\n";
    }

} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: wan2.5-t2i-preview with image editing
echo "🧪 Test 2: wan2.5-t2i-preview with image editing\n";
echo "   Simulating frontend request...\n";

$requestBody = [
    'prompt' => 'Change the background to blue',
    'model' => 'wan2.5-t2i-preview',
    'ref_image' => $base64Image,
    'size' => '1024*1024'
];

try {
    $response = $client->post(
        'http://127.0.0.1:8080/api/image/generate/bailian',
        [
            'json' => $requestBody,
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'timeout' => 60
        ]
    );

    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);

    if ($data['success']) {
        echo "   ✅ Success!\n";
        echo "   Status: " . ($data['status'] ?? 'unknown') . "\n";
        if (isset($data['task_id'])) {
            echo "   Task ID: " . substr($data['task_id'], 0, 20) . "...\n";
        }
    } else {
        echo "   ❌ Failed: " . ($data['error'] ?? 'unknown error') . "\n";
    }

} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "✅ Integration test completed\n";
