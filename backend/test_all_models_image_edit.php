<?php
/**
 * Test all models that support image editing
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

echo "🧪 Testing Image Editing Support for All Models\n";
echo "=" . str_repeat("=", 70) . "\n\n";

// Models to test - those that use multimodal endpoint
$modelsToTest = [
    'wan2.6-t2i' => '万相 2.6',
    'qwen-image-2.0-pro' => '千问 2.0 Pro',
    'qwen-image-2.0' => '千问 2.0',
    'qwen-image-max' => '千问 Max',
    'qwen-image-plus' => '千问 Plus',
    'qwen-image' => '千问图像',
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
    
    $requestData = [
        'model' => $modelId,
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
            $error = $data['message'] ?? 'Unknown error';
            echo "   ❌ Error: $error\n";
            $results[$modelId] = [
                'status' => 'failed',
                'error' => $error,
                'name' => $modelName
            ];
        } else if (isset($data['output']['choices'])) {
            $imageUrl = null;
            foreach ($data['output']['choices'] as $choice) {
                if (isset($choice['message']['content'])) {
                    foreach ($choice['message']['content'] as $content) {
                        if (isset($content['image'])) {
                            $imageUrl = $content['image'];
                            break 2;
                        }
                    }
                }
            }
            
            if ($imageUrl) {
                echo "   ✅ Success! Generated image URL\n";
                $results[$modelId] = [
                    'status' => 'success',
                    'url' => $imageUrl,
                    'name' => $modelName
                ];
            } else {
                echo "   ❌ No image in response\n";
                $results[$modelId] = [
                    'status' => 'failed',
                    'error' => 'No image in response',
                    'name' => $modelName
                ];
            }
        } else {
            echo "   ❌ Invalid response format\n";
            $results[$modelId] = [
                'status' => 'failed',
                'error' => 'Invalid response format',
                'name' => $modelName
            ];
        }

    } catch (\Exception $e) {
        $error = $e->getMessage();
        echo "   ❌ Exception: $error\n";
        $results[$modelId] = [
            'status' => 'failed',
            'error' => $error,
            'name' => $modelName
        ];
    }
    
    echo "\n";
}

// Summary
echo str_repeat("=", 70) . "\n";
echo "📊 Summary\n";
echo str_repeat("=", 70) . "\n\n";

$successCount = 0;
$failureCount = 0;

foreach ($results as $modelId => $result) {
    $status = $result['status'] === 'success' ? '✅' : '❌';
    $name = $result['name'];
    
    if ($result['status'] === 'success') {
        echo "$status $name ($modelId)\n";
        echo "   Status: Supports image editing\n";
        $successCount++;
    } else {
        echo "$status $name ($modelId)\n";
        echo "   Status: Failed - " . $result['error'] . "\n";
        $failureCount++;
    }
    echo "\n";
}

echo str_repeat("=", 70) . "\n";
echo "📈 Results: $successCount successful, $failureCount failed\n";
echo str_repeat("=", 70) . "\n";

// Save results to file
file_put_contents(
    __DIR__ . '/test_models_image_edit_results.json',
    json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo "\n✅ Results saved to: test_models_image_edit_results.json\n";
