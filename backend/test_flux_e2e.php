<?php
/**
 * 端到端测试 - 测试完整的图片生成流程
 */

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Services\OpenRouterService;
use App\Services\DeepSeekService;
use App\Services\AliBailianService;
use App\Services\AIServiceManager;

$openRouterService = new OpenRouterService();
$deepSeekService = new DeepSeekService();
$aliBailianService = new AliBailianService();
$aiServiceManager = new AIServiceManager($openRouterService, $deepSeekService, $aliBailianService);

echo "=== 端到端测试 - Flux 模型 ===\n\n";

// 测试1: 直接调用 OpenRouterService
echo "测试1: 直接调用 OpenRouterService\n";
echo "模型: black-forest-labs/flux.2-pro\n";
$prompt = 'A beautiful sunset over mountains';

try {
    $result = $openRouterService->generateImage('black-forest-labs/flux.2-pro', $prompt);
    echo "✅ 成功\n";
    echo "   返回字段: " . implode(', ', array_keys($result)) . "\n";
    echo "   image_url 长度: " . strlen($result['image_url']) . "\n";
    echo "   image_url 前缀: " . substr($result['image_url'], 0, 50) . "...\n";
} catch (\Exception $e) {
    echo "❌ 失败: " . $e->getMessage() . "\n";
}

echo "\n";

// 测试2: 通过 AIServiceManager 调用
echo "测试2: 通过 AIServiceManager 调用\n";
echo "模型: black-forest-labs/flux.2-pro\n";

try {
    $result = $aiServiceManager->generateImage('black-forest-labs/flux.2-pro', $prompt);
    echo "✅ 成功\n";
    echo "   返回字段: " . implode(', ', array_keys($result)) . "\n";
    echo "   image_url 长度: " . strlen($result['image_url']) . "\n";
    echo "   image_url 前缀: " . substr($result['image_url'], 0, 50) . "...\n";
} catch (\Exception $e) {
    echo "❌ 失败: " . $e->getMessage() . "\n";
}

echo "\n";

// 测试3: 模拟 HTTP 请求
echo "测试3: 模拟 HTTP 请求\n";
echo "模型: black-forest-labs/flux.2-pro\n";

try {
    $result = $aiServiceManager->generateImage('black-forest-labs/flux.2-pro', $prompt);
    
    // 模拟后端返回的 JSON
    $response = [
        'success' => true,
        'image_url' => $result['image_url'],
        'model' => $result['model'],
        'prompt' => $result['prompt'],
        'quota' => [
            'total' => 10,
            'used' => 1,
            'remaining' => 9
        ]
    ];
    
    echo "✅ 成功\n";
    echo "   响应大小: " . strlen(json_encode($response)) . " 字节\n";
    echo "   image_url 在响应中: " . (isset($response['image_url']) ? '是' : '否') . "\n";
    
    // 检查 JSON 编码是否成功
    $json = json_encode($response);
    if ($json === false) {
        echo "❌ JSON 编码失败: " . json_last_error_msg() . "\n";
    } else {
        echo "   JSON 编码: ✓\n";
        
        // 检查是否能解码
        $decoded = json_decode($json, true);
        if ($decoded === null) {
            echo "❌ JSON 解码失败\n";
        } else {
            echo "   JSON 解码: ✓\n";
            echo "   解码后 image_url 长度: " . strlen($decoded['image_url']) . "\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ 失败: " . $e->getMessage() . "\n";
}

echo "\n=== 测试完成 ===\n";
