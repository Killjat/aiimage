<?php
/**
 * 测试 OpenRouter 图片生成模型
 */

require_once __DIR__ . '/vendor/autoload.php';

// 加载环境变量
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['OPENROUTER_API_KEY'];
$apiUrl = $_ENV['OPENROUTER_API_URL'];

// 要测试的图片生成模型
$imageModels = [
    'openai/gpt-image-1',
    'openai/gpt-5-image',
    'google/gemini-2.5-flash-image-preview',
    'google/gemini-3.1-flash-image-preview',
    'black-forest-labs/flux.2-pro',
    'black-forest-labs/flux.2-flex',
];

echo "=== 测试 OpenRouter 图片生成模型 ===\n\n";

foreach ($imageModels as $model) {
    echo "测试模型: $model\n";
    echo str_repeat('-', 50) . "\n";
    
    $data = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'user',
                'content' => 'Generate a beautiful sunset over mountains'
            ]
        ],
        'modalities' => ['image']
    ];
    
    $ch = curl_init($apiUrl . '/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
        'HTTP-Referer: http://localhost:8080',
        'X-Title: AI Image Test'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['images'])) {
            $images = $result['choices'][0]['message']['images'];
            echo "✅ 成功! 生成了 " . count($images) . " 张图片\n";
            
            // 保存第一张图片
            if (is_array($images[0])) {
                echo "图片格式: " . ($images[0]['format'] ?? 'unknown') . "\n";
                echo "图片 URL: " . substr($images[0]['url'] ?? '', 0, 100) . "...\n";
                
                // 如果是 base64，保存到文件
                if (isset($images[0]['url']) && strpos($images[0]['url'], 'data:image') === 0) {
                    $imageData = $images[0]['url'];
                    $filename = __DIR__ . '/test_output_' . str_replace(['/', '.'], '_', $model) . '.html';
                    file_put_contents($filename, "<html><body><h1>$model</h1><img src='$imageData' /></body></html>");
                    echo "图片已保存到: $filename\n";
                }
            } else {
                echo "图片数据长度: " . strlen($images[0]) . " 字节\n";
                // 保存 base64 图片
                $filename = __DIR__ . '/test_output_' . str_replace(['/', '.'], '_', $model) . '.html';
                file_put_contents($filename, "<html><body><h1>$model</h1><img src='$images[0]' /></body></html>");
                echo "图片已保存到: $filename\n";
            }
        } elseif (isset($result['choices'][0]['message']['content'])) {
            echo "⚠️  返回了文本内容，但没有图片\n";
            echo "内容: " . substr($result['choices'][0]['message']['content'], 0, 100) . "...\n";
        } else {
            echo "❌ 响应格式不符合预期\n";
            echo "响应: " . substr($response, 0, 200) . "...\n";
        }
    } else {
        $error = json_decode($response, true);
        echo "❌ 失败 (HTTP $httpCode)\n";
        if (isset($error['error']['message'])) {
            echo "错误: " . $error['error']['message'] . "\n";
        } else {
            echo "响应: " . substr($response, 0, 200) . "...\n";
        }
    }
    
    echo "\n";
    sleep(2); // 避免请求过快
}

echo "=== 测试完成 ===\n";
