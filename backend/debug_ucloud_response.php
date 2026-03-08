<?php
/**
 * UCloud API 响应调试脚本
 * 用于查看 UCloud API 的实际返回内容
 */

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['UCLOUD_API_KEY'] ?? '';
$apiUrl = $_ENV['UCLOUD_API_URL'] ?? 'https://api.ucloud.cn/v1';

echo "=== UCloud API 响应调试 ===\n\n";
echo "API URL: {$apiUrl}\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n\n";

// 测试图片生成 API
echo "--- 测试图片生成 API ---\n";
$ch = curl_init($apiUrl . '/images/generations');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json',
    'Accept: application/json',
]);

$requestBody = [
    'model' => 'dall-e-3',
    'prompt' => 'a cute cat',
    'n' => 1,
    'size' => '1024x1024',
    'quality' => 'standard'
];

echo "请求体:\n";
echo json_encode($requestBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));

$body = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP 状态码: {$httpCode}\n\n";
echo "响应内容:\n";
echo $body . "\n\n";

if ($httpCode === 200) {
    $data = json_decode($body, true);
    if ($data) {
        echo "解析后的 JSON:\n";
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        
        echo "数据结构分析:\n";
        echo "- 是否有 'data' 字段: " . (isset($data['data']) ? '是' : '否') . "\n";
        if (isset($data['data'])) {
            echo "- data 数组长度: " . count($data['data']) . "\n";
            if (count($data['data']) > 0) {
                echo "- data[0] 的键: " . implode(', ', array_keys($data['data'][0])) . "\n";
                echo "- 是否有 'url': " . (isset($data['data'][0]['url']) ? '是' : '否') . "\n";
                echo "- 是否有 'b64_json': " . (isset($data['data'][0]['b64_json']) ? '是' : '否') . "\n";
            }
        }
        
        // 检查其他可能的字段
        echo "\n所有顶级字段: " . implode(', ', array_keys($data)) . "\n";
    }
}

curl_close($ch);

echo "\n=== 调试完成 ===\n";
