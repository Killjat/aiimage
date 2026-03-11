<?php
/**
 * 测试所有 8 个图片生成模型
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\AliBailianService;
use App\Services\OpenRouterService;

// 加载环境变量
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "🧪 测试所有 8 个图片生成模型\n";
echo "================================================\n\n";

// Alibaba 模型
$alibabaModels = [
    'wan2.6-t2i' => '万相 2.6',
    'qwen-image-2.0-pro' => '千问图像 2.0 Pro',
    'qwen-image-2.0' => '千问图像 2.0',
    'qwen-image-max' => '千问图像 Max',
    'qwen-image-plus' => '千问图像 Plus',
    'qwen-image' => '千问图像',
];

// OpenRouter 模型
$openRouterModels = [
    'black-forest-labs/flux-2-pro' => 'Flux 2 Pro',
    'black-forest-labs/flux-2-flex' => 'Flux 2 Flex',
];

$testPrompt = '一只可爱的小猫咪，坐在阳光下';
$successCount = 0;
$failCount = 0;

// 测试 Alibaba 模型
echo "📍 测试 Alibaba 模型 (6 个)\n";
echo "---\n";

try {
    $aliBailianService = new AliBailianService();
    
    foreach ($alibabaModels as $modelId => $modelName) {
        echo "测试: $modelName ($modelId)\n";
        
        try {
            $result = $aliBailianService->generateImage(
                $testPrompt,
                $modelId,
                null,
                '<auto>',
                '1024*1024',
                1
            );
            
            if ($result['success']) {
                if (isset($result['images']) && !empty($result['images'])) {
                    echo "  ✅ 成功 - 同步生成\n";
                    $successCount++;
                } elseif (isset($result['task_id'])) {
                    echo "  ✅ 成功 - 异步任务 (ID: " . substr($result['task_id'], 0, 20) . "...)\n";
                    $successCount++;
                } else {
                    echo "  ⚠️  成功但格式异常\n";
                    $failCount++;
                }
            } else {
                echo "  ❌ 失败: " . ($result['message'] ?? '未知错误') . "\n";
                $failCount++;
            }
        } catch (\Exception $e) {
            echo "  ❌ 异常: " . $e->getMessage() . "\n";
            $failCount++;
        }
    }
} catch (\Exception $e) {
    echo "❌ Alibaba 服务初始化失败: " . $e->getMessage() . "\n";
    $failCount += count($alibabaModels);
}

echo "\n";

// 测试 OpenRouter 模型
echo "📍 测试 OpenRouter 模型 (2 个)\n";
echo "---\n";

try {
    $openRouterService = new OpenRouterService();
    
    foreach ($openRouterModels as $modelId => $modelName) {
        echo "测试: $modelName ($modelId)\n";
        
        try {
            $result = $openRouterService->generateImage(
                $modelId,
                $testPrompt
            );
            
            if (isset($result['image_url'])) {
                $urlPreview = substr($result['image_url'], 0, 50);
                if (strlen($result['image_url']) > 50) {
                    $urlPreview .= "...";
                }
                echo "  ✅ 成功 - 图片 URL: $urlPreview\n";
                $successCount++;
            } else {
                echo "  ⚠️  响应格式异常: " . json_encode($result) . "\n";
                $failCount++;
            }
        } catch (\Exception $e) {
            echo "  ❌ 异常: " . $e->getMessage() . "\n";
            $failCount++;
        }
    }
} catch (\Exception $e) {
    echo "❌ OpenRouter 服务初始化失败: " . $e->getMessage() . "\n";
    $failCount += count($openRouterModels);
}

echo "\n";
echo "================================================\n";
echo "📊 测试结果\n";
echo "================================================\n";
echo "✅ 成功: $successCount\n";
echo "❌ 失败: $failCount\n";
echo "📈 成功率: " . round(($successCount / 8) * 100, 1) . "%\n";
echo "\n";

if ($successCount === 8) {
    echo "🎉 所有模型都能正常生图！\n";
} elseif ($successCount >= 6) {
    echo "✅ 大部分模型正常，可以使用\n";
} else {
    echo "⚠️  模型成功率较低，需要检查配置\n";
}
