<?php
/**
 * 从阿里百练 API 获取所有可用的图片生成模型
 */

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['ALIBABA_BAILIAN_API_KEY'] ?? '';

if (empty($apiKey)) {
    echo "错误: 未配置 ALIBABA_BAILIAN_API_KEY\n";
    exit(1);
}

echo "=== 从阿里百练获取所有图片生成模型 ===\n\n";

// 尝试调用模型列表 API
$urls = [
    'https://dashscope.aliyuncs.com/api/v1/models',
    'https://dashscope.aliyuncs.com/api/v1/models?type=image',
];

foreach ($urls as $url) {
    echo "尝试 URL: $url\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ 请求失败: $error\n\n";
        continue;
    }
    
    if ($httpCode === 200) {
        echo "✅ 成功 (HTTP $httpCode)\n";
        $data = json_decode($response, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            exit(0);
        } else {
            echo "❌ JSON 解析失败\n";
        }
    } else {
        echo "❌ HTTP $httpCode\n";
        echo "响应: " . substr($response, 0, 200) . "\n\n";
    }
}

echo "\n=== 尝试直接调用文生图 API 获取模型信息 ===\n\n";

// 尝试调用文生图 API 来获取模型信息
$testModels = [
    'wan2.6-t2i',
    'wan2.5-t2i-preview',
    'wan2.2-t2i-flash',
    'wanx-v1',
    'stable-diffusion-v1.5',
    'stable-diffusion-xl',
    'stable-diffusion-3.5-large'
];

$availableModels = [];

foreach ($testModels as $model) {
    echo "测试模型: $model ... ";
    
    $requestData = [
        'model' => $model,
        'input' => ['prompt' => 'test'],
        'parameters' => ['n' => 1, 'size' => '1024*1024']
    ];
    
    $ch = curl_init('https://dashscope.aliyuncs.com/api/v1/services/aigc/text2image/image-synthesis');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
        'X-DashScope-Async: enable'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if ($httpCode === 200 && isset($data['output']['task_id'])) {
        echo "✅ 可用\n";
        $availableModels[] = $model;
    } else {
        $error = $data['message'] ?? $data['code'] ?? '未知错误';
        echo "❌ 不可用 ($error)\n";
    }
}

echo "\n=== 可用的模型 ===\n";
echo json_encode($availableModels, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
