<?php
/**
 * 测试 OpenRouter Flux 模型图片生成
 */

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Services\OpenRouterService;

$openRouterService = new OpenRouterService();

$models = [
    'black-forest-labs/flux.2-pro',
    'black-forest-labs/flux.2-flex'
];

$prompt = 'A beautiful sunset over mountains, oil painting style, high quality';

echo "=== 测试 OpenRouter Flux 模型 ===\n\n";

foreach ($models as $model) {
    echo "测试模型: $model\n";
    
    try {
        $result = $openRouterService->generateImage($model, $prompt);
        
        if (isset($result['image_url'])) {
            echo "✅ 成功\n";
            echo "   格式: " . ($result['format'] ?? 'unknown') . "\n";
            echo "   图片URL长度: " . strlen($result['image_url']) . " 字符\n";
            
            // 检查是否是base64
            if (strpos($result['image_url'], 'data:image') === 0) {
                echo "   ✓ 返回 base64 data URL\n";
                // 显示前100个字符
                echo "   前100字符: " . substr($result['image_url'], 0, 100) . "...\n";
            } elseif (strpos($result['image_url'], 'http') === 0) {
                echo "   ✓ 返回 HTTP URL\n";
                echo "   URL: " . $result['image_url'] . "\n";
            }
        } else {
            echo "❌ 失败: 没有返回 image_url\n";
            echo "   响应: " . json_encode($result) . "\n";
        }
    } catch (\Exception $e) {
        echo "❌ 错误: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=== 测试完成 ===\n";
