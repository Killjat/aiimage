<?php
/**
 * Test real image editing with Qwen-Image-2.0
 * Upload a real image and test if the generated image is related to the original
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

echo "🧪 Testing Real Image Editing with Qwen-Image-2.0\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Step 1: Download a sample image from the internet
echo "📸 Step 1: Downloading sample image...\n";

$imageUrl = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Cat03.jpg/1200px-Cat03.jpg';
$imageData = @file_get_contents($imageUrl);

if (!$imageData) {
    // Fallback: create a simple test image
    echo "   ⚠️  Could not download from internet, creating test image...\n";
    $image = imagecreatetruecolor(400, 300);
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    imagefill($image, 0, 0, $white);
    
    // Draw a simple circle (representing a cat head)
    imagefilledellipse($image, 200, 150, 100, 100, $black);
    
    ob_start();
    imagejpeg($image);
    $imageData = ob_get_clean();
    imagedestroy($image);
}

$base64Image = 'data:image/jpeg;base64,' . base64_encode($imageData);
echo "✅ Image loaded: " . strlen($base64Image) . " bytes\n";
echo "   Format: " . substr($base64Image, 0, 50) . "...\n\n";

// Step 2: Test 1 - Change background
echo "📤 Step 2: Test 1 - Change background to blue\n";
echo "   Prompt: 'Change the background to blue, keep the subject'\n";

$client = new Client();

$requestData = [
    'model' => 'qwen-image-2.0',
    'input' => [
        'messages' => [
            [
                'role' => 'user',
                'content' => [
                    [
                        'text' => 'Change the background to blue, keep the subject'
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

try {
    $response = $client->post(
        'https://dashscope.aliyuncs.com/api/v1/services/aigc/multimodal-generation/generation',
        [
            'json' => $requestData,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 60
        ]
    );

    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);

    if (isset($data['code']) && $data['code'] !== '200') {
        echo "❌ API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
    } else if (isset($data['output']['choices'])) {
        $imageUrl1 = null;
        foreach ($data['output']['choices'] as $choice) {
            if (isset($choice['message']['content'])) {
                foreach ($choice['message']['content'] as $content) {
                    if (isset($content['image'])) {
                        $imageUrl1 = $content['image'];
                        break 2;
                    }
                }
            }
        }
        
        if ($imageUrl1) {
            echo "✅ Generated image URL:\n";
            echo "   " . substr($imageUrl1, 0, 80) . "...\n";
            echo "   Full URL saved to: /tmp/test_result_1.txt\n";
            file_put_contents('/tmp/test_result_1.txt', $imageUrl1);
        }
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Step 3: Test 2 - Add sunglasses
echo "📤 Step 3: Test 2 - Add sunglasses\n";
echo "   Prompt: 'Add cool sunglasses to the subject'\n";

$requestData['input']['messages'][0]['content'][0]['text'] = 'Add cool sunglasses to the subject';

try {
    $response = $client->post(
        'https://dashscope.aliyuncs.com/api/v1/services/aigc/multimodal-generation/generation',
        [
            'json' => $requestData,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 60
        ]
    );

    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);

    if (isset($data['code']) && $data['code'] !== '200') {
        echo "❌ API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
    } else if (isset($data['output']['choices'])) {
        $imageUrl2 = null;
        foreach ($data['output']['choices'] as $choice) {
            if (isset($choice['message']['content'])) {
                foreach ($choice['message']['content'] as $content) {
                    if (isset($content['image'])) {
                        $imageUrl2 = $content['image'];
                        break 2;
                    }
                }
            }
        }
        
        if ($imageUrl2) {
            echo "✅ Generated image URL:\n";
            echo "   " . substr($imageUrl2, 0, 80) . "...\n";
            echo "   Full URL saved to: /tmp/test_result_2.txt\n";
            file_put_contents('/tmp/test_result_2.txt', $imageUrl2);
        }
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Step 4: Test 3 - Change style to oil painting
echo "📤 Step 4: Test 3 - Change to oil painting style\n";
echo "   Prompt: 'Convert to oil painting style'\n";

$requestData['input']['messages'][0]['content'][0]['text'] = 'Convert to oil painting style';

try {
    $response = $client->post(
        'https://dashscope.aliyuncs.com/api/v1/services/aigc/multimodal-generation/generation',
        [
            'json' => $requestData,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 60
        ]
    );

    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);

    if (isset($data['code']) && $data['code'] !== '200') {
        echo "❌ API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
    } else if (isset($data['output']['choices'])) {
        $imageUrl3 = null;
        foreach ($data['output']['choices'] as $choice) {
            if (isset($choice['message']['content'])) {
                foreach ($choice['message']['content'] as $content) {
                    if (isset($content['image'])) {
                        $imageUrl3 = $content['image'];
                        break 2;
                    }
                }
            }
        }
        
        if ($imageUrl3) {
            echo "✅ Generated image URL:\n";
            echo "   " . substr($imageUrl3, 0, 80) . "...\n";
            echo "   Full URL saved to: /tmp/test_result_3.txt\n";
            file_put_contents('/tmp/test_result_3.txt', $imageUrl3);
        }
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "✅ Test completed\n";
echo "\n📋 Summary:\n";
echo "   Test 1 (Blue background): /tmp/test_result_1.txt\n";
echo "   Test 2 (Add sunglasses): /tmp/test_result_2.txt\n";
echo "   Test 3 (Oil painting): /tmp/test_result_3.txt\n";
echo "\n💡 Open these URLs in a browser to verify if the generated images\n";
echo "   are related to the original image and follow the prompts.\n";
