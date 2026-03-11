<?php
/**
 * Test Flux models for image editing support
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

echo "🧪 Testing Flux Models for Image Editing Support\n";
echo "=" . str_repeat("=", 70) . "\n\n";

// Models to test
$modelsToTest = [
    'black-forest-labs/flux.2-pro' => 'Flux 2 Pro',
    'black-forest-labs/flux.2-flex' => 'Flux 2 Flex',
];

// Create a simple test image
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
echo "✅ Test image created: " . strlen($base64Image) . " bytes\n\n";

$client = new Client();
$results = [];

foreach ($modelsToTest as $modelId => $modelName) {
    echo "🧪 Testing: $modelName ($modelId)\n";
    
    // Test 1: Text-to-image (should work)
    echo "   Test 1: Text-to-image generation...\n";
    
    $requestBody = [
        'model' => $modelId,
        'messages' => [
            [
                'role' => 'user',
                'content' => 'Generate a simple image of a red circle on white background'
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

        if (isset($data['choices'][0]['message']['images'])) {
            echo "   ✅ Text-to-image: Success\n";
        } else {
            echo "   ❌ Text-to-image: No images in response\n";
        }

    } catch (\Exception $e) {
        echo "   ❌ Text-to-image: " . substr($e->getMessage(), 0, 80) . "\n";
    }
    
    // Test 2: Image editing (with reference image)
    echo "   Test 2: Image editing with reference image...\n";
    
    $requestBody = [
        'model' => $modelId,
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

        if (isset($data['choices'][0]['message']['images'])) {
            echo "   ✅ Image editing: Success\n";
            $results[$modelId] = [
                'status' => 'success',
                'name' => $modelName,
                'text_to_image' => true,
                'image_editing' => true
            ];
        } else if (isset($data['error'])) {
            $error = $data['error']['message'] ?? 'Unknown error';
            echo "   ❌ Image editing: " . substr($error, 0, 80) . "\n";
            $results[$modelId] = [
                'status' => 'failed',
                'name' => $modelName,
                'text_to_image' => true,
                'image_editing' => false,
                'error' => $error
            ];
        } else {
            echo "   ❌ Image editing: No images in response\n";
            $results[$modelId] = [
                'status' => 'failed',
                'name' => $modelName,
                'text_to_image' => true,
                'image_editing' => false,
                'error' => 'No images in response'
            ];
        }

    } catch (\Exception $e) {
        $error = $e->getMessage();
        echo "   ❌ Image editing: " . substr($error, 0, 80) . "\n";
        $results[$modelId] = [
            'status' => 'failed',
            'name' => $modelName,
            'text_to_image' => true,
            'image_editing' => false,
            'error' => $error
        ];
    }
    
    echo "\n";
}

// Summary
echo str_repeat("=", 70) . "\n";
echo "📊 Summary\n";
echo str_repeat("=", 70) . "\n\n";

foreach ($results as $modelId => $result) {
    $status = $result['status'] === 'success' ? '✅' : '❌';
    $name = $result['name'];
    
    echo "$status $name ($modelId)\n";
    echo "   Text-to-image: " . ($result['text_to_image'] ? '✅' : '❌') . "\n";
    echo "   Image editing: " . ($result['image_editing'] ? '✅' : '❌') . "\n";
    if (isset($result['error'])) {
        echo "   Error: " . substr($result['error'], 0, 100) . "\n";
    }
    echo "\n";
}

echo str_repeat("=", 70) . "\n";
echo "✅ Test completed\n";

// Save results
file_put_contents(
    __DIR__ . '/test_flux_image_edit_results.json',
    json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo "\n📄 Results saved to: test_flux_image_edit_results.json\n";
