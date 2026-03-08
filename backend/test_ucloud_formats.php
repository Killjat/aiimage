<?php
/**
 * 测试 UCloud 不同的 API 格式
 */

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['UCLOUD_API_KEY'] ?? '';
$apiUrl = $_ENV['UCLOUD_API_URL'] ?? 'https://api.ucloud.cn/v1';

echo "=== 测试 UCloud API 格式 ===\n\n";

// 格式 1: 标准 OpenAI 格式 (已测试，失败)
echo "--- 格式 1: 标准 OpenAI 格式 ---\n";
echo "结果: 失败 (Missing Action)\n\n";

// 格式 2: UCloud 原生格式 (带 Action 参数)
echo "--- 格式 2: UCloud 原生格式 (GET 请求) ---\n";
$params = [
    'Action' => 'CreateAIImage',
    'PublicKey' => $apiKey,
    'Prompt' => 'a cute cat',
    'Model' => 'dall-e-3',
    'Size' => '1024x1024',
];

$url = $apiUrl . '?' . http_build_query($params);
echo "URL: {$url}\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$body = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP 状态码: {$httpCode}\n";
echo "响应: " . substr($body, 0, 500) . "\n\n";

// 格式 3: 检查是否有聊天 API
echo "--- 格式 3: 测试聊天 API ---\n";
$chatParams = [
    'Action' => 'CreateAIChat',
    'PublicKey' => $apiKey,
    'Model' => 'gpt-3.5-turbo',
    'Messages' => json_encode([['role' => 'user', 'content' => 'hello']]),
];

$chatUrl = $apiUrl . '?' . http_build_query($chatParams);
$ch2 = curl_init($chatUrl);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
$body2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

echo "HTTP 状态码: {$httpCode2}\n";
echo "响应: " . substr($body2, 0, 500) . "\n\n";

// 格式 4: 列出可用的 Actions
echo "--- 格式 4: 尝试获取 API 文档 ---\n";
$docUrl = str_replace('/v1', '/docs', $apiUrl);
echo "文档 URL: {$docUrl}\n";
echo "建议: 请访问 UCloud 控制台查看 API 文档\n\n";

echo "=== 测试完成 ===\n\n";
echo "💡 建议:\n";
echo "1. 访问 UCloud 控制台: https://console.ucloud.cn\n";
echo "2. 查找 AI 服务的 API 文档\n";
echo "3. 确认正确的 API 端点和请求格式\n";
echo "4. 检查是否需要使用 PublicKey 和 PrivateKey 进行签名\n";
