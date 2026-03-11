<?php
/**
 * Test Flux models with detailed error reporting
 */

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['OPENROUTER_API_KEY'] ?? '';

if (empty($apiKey)) {
    die("❌ OPENROUTER_API_KEY not configured\n");
}

echo "🧪 Testing Flux Models - Detailed\n";
echo "=" . str_repeat("=", 70) . "\n\n";

echo "API Key: " . substr($apiKey, 0, 20) . "...\n\n";

// Create a simple test image
$image = imagecreatetruecolor(400, 300);
$white = imagecolorallocate($image, 255, 255, 255);
$black = imagecolorallocate($image, 0, 0, 0);
imagefill($image, 0, 0, $white);
imagefilledellipse($image, 200, 150, 100, 100, $black);

ob_start();
imagejpeg($image);
$imageData = ob_get_clean();

$base64Image = 'data:image/jpeg;base64,' . base64_encode($imageData);

$client = new Client();

// Test 1: Simple text-to-image with Flux 2 Pro
echo "Test 1: Flux 2 Pro - Text-to-image\n";

$requestBody = [
    'model' => 'black-forest-labs/flux.2-pro',
    'messages' => [
        [
            'role' => 'user',
            'content' => 'Generate a simple image of a red circle'
        ]
    ],
    'modalities' => ['image']
];

try {
    $response = $client->post(
        'https://openrouter.ai/api/v1/chat/completions',
        [
            'json' => $requestBody,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => 'http://127.0.0.1:8080',
                'X-Title' => 'Image Editing Test'
            ],
            'timeout' => 60
        ]
    );

    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);

    echo "✅ Response received\n";
    echo "Status: " . (isset($data['choices']) ? 'Has choices' : 'No choices') . "\n";
    
    if (isset($data['choices'][0]['message']['images'])) {
        echo "✅ Has images\n";
    } else {
        echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    if ($e instanceof \GuzzleHttp\Exception\ClientException) {
        echo "Response: " . $e->getResponse()->getBody()->getContents() . "\n";
    }
}

echo "\n";

// Test 2: Image editing with Flux 2 Pro
echo "Test 2: Flux 2 Pro - Image editing\n";

$requestBody = [
    'model' => 'black-forest-labs/flux.2-pro',
    'messages' => [
        [
            'role' => 'user',
            'content' => [
                [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => $base64Image
                    ]
                ],
                [
                    'type' => 'text',
                    'text' => 'Change the background to blue'
                ]
            ]
        ]
    ],
    'modalities' => ['image']
];

try {
    $response = $client->post(
        'https://openrouter.ai/api/v1/chat/completions',
        [
            'json' => $requestBody,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => 'http://127.0.0.1:8080',
                'X-Title' => 'Image Editing Test'
            ],
            'timeout' => 60
        ]
    );

    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);

    echo "✅ Response received\n";
    echo "Status: " . (isset($data['choices']) ? 'Has choices' : 'No choices') . "\n";
    
    if (isset($data['choices'][0]['message']['images'])) {
        echo "✅ Has images\n";
    } else {
        echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    if ($e instanceof \GuzzleHttp\Exception\ClientException) {
        $response = $e->getResponse()->getBody()->getContents();
        echo "Response: " . $response . "\n";
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "✅ Test completed\n";
