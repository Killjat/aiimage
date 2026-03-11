<?php
/**
 * Test Qwen-Image-2.0 with reference image (image editing)
 * This tests if the multimodal endpoint correctly handles reference images
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

echo "🧪 Testing Qwen-Image-2.0 with Reference Image\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Step 1: Create a simple test image (1x1 pixel PNG)
echo "📸 Step 1: Creating test reference image...\n";
$testImagePath = '/tmp/test_image.png';

// Create a simple 100x100 red PNG
$image = imagecreatetruecolor(100, 100);
$red = imagecolorallocate($image, 255, 0, 0);
imagefill($image, 0, 0, $red);
imagepng($image, $testImagePath);
imagedestroy($image);

// Read and encode as base64
$imageData = file_get_contents($testImagePath);
$base64Image = 'data:image/png;base64,' . base64_encode($imageData);

echo "✅ Test image created: " . strlen($base64Image) . " bytes\n";
echo "   Format: " . substr($base64Image, 0, 50) . "...\n\n";

// Step 2: Test multimodal endpoint with reference image
echo "📤 Step 2: Calling Qwen-Image-2.0 multimodal endpoint...\n";

$client = new Client();

$requestData = [
    'model' => 'qwen-image-2.0',
    'input' => [
        'messages' => [
            [
                'role' => 'user',
                'content' => [
                    [
                        'text' => 'Change the background to blue'
                    ],
                    [
                        'image' => $base64Image
                    ]
                ]
            ]
        ]
    ],
    'parameters' => [
        'n' => 1,
        'size' => '1024*1024',
        'prompt_extend' => true
    ]
];

echo "📋 Request payload:\n";
echo json_encode($requestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

try {
    $response = $client->post(
        'https://dashscope.aliyuncs.com/api/v1/services/aigc/multimodal-generation/generation',
        [
            'json' => $requestData,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30
        ]
    );

    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);

    echo "✅ API Response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

    // Check for errors
    if (isset($data['code']) && $data['code'] !== '200') {
        echo "❌ API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
        if (isset($data['code'])) {
            echo "   Code: " . $data['code'] . "\n";
        }
    } else if (isset($data['output']['choices'])) {
        echo "✅ Success! Generated " . count($data['output']['choices']) . " image(s)\n";
        
        foreach ($data['output']['choices'] as $i => $choice) {
            if (isset($choice['message']['content'])) {
                foreach ($choice['message']['content'] as $content) {
                    if (isset($content['image'])) {
                        echo "   Image " . ($i + 1) . ": " . substr($content['image'], 0, 50) . "...\n";
                    }
                }
            }
        }
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    if ($e instanceof \GuzzleHttp\Exception\ClientException) {
        echo "Response: " . $e->getResponse()->getBody()->getContents() . "\n";
    }
}

// Cleanup
unlink($testImagePath);

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ Test completed\n";
