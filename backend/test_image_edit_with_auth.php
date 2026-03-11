<?php
/**
 * Test image editing with authenticated user (admin)
 */

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "🧪 Testing Image Editing with Authenticated User\n";
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
        echo "✅ Login successful\n";
        echo "   Token: " . substr($token, 0, 30) . "...\n\n";
    } else {
        die("❌ Login failed: " . json_encode($data) . "\n");
    }

} catch (\Exception $e) {
    die("❌ Exception: " . $e->getMessage() . "\n");
}

// Step 2: Create test image
echo "📸 Step 2: Creating test image...\n";

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

// Step 3: Test image editing with qwen-image-2.0
echo "🎨 Step 3: Testing image editing with qwen-image-2.0...\n";

$requestBody = [
    'prompt' => 'Change the background to blue, keep the subject',
    'model' => 'qwen-image-2.0',
    'ref_image' => $base64Image,
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
        echo "✅ Image generation successful!\n";
        echo "   Status: " . ($data['status'] ?? 'unknown') . "\n";
        if (isset($data['images'])) {
            echo "   Generated " . count($data['images']) . " image(s)\n";
            echo "   Image URL: " . substr($data['images'][0], 0, 80) . "...\n";
        }
        if (isset($data['prompt_extended'])) {
            echo "   Prompt extended: " . ($data['prompt_extended'] ? 'yes' : 'no') . "\n";
        }
        if (isset($data['quota'])) {
            echo "   Remaining quota: " . $data['quota']['remaining'] . "\n";
        }
    } else {
        echo "❌ Failed: " . ($data['error'] ?? 'unknown error') . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Step 4: Test image editing with wan2.5-t2i-preview
echo "🎨 Step 4: Testing image editing with wan2.5-t2i-preview...\n";

$requestBody = [
    'prompt' => 'Add sunglasses to the subject',
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
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ],
            'timeout' => 60
        ]
    );

    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);

    if ($data['success']) {
        echo "✅ Task created successfully!\n";
        echo "   Status: " . ($data['status'] ?? 'unknown') . "\n";
        if (isset($data['task_id'])) {
            echo "   Task ID: " . substr($data['task_id'], 0, 30) . "...\n";
        }
        if (isset($data['quota'])) {
            echo "   Remaining quota: " . $data['quota']['remaining'] . "\n";
        }
    } else {
        echo "❌ Failed: " . ($data['error'] ?? 'unknown error') . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "✅ Test completed\n";
