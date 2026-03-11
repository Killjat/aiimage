<?php
/**
 * 本地测试图片生成功能
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\AliBailianService;
use App\Services\OpenRouterService;
use App\Services\AIServiceManager;

// 加载环境变量
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "🧪 本地图片生成功能测试\n";
echo "================================================\n\n";

// 测试 1: Alibaba 模型列表
echo "✓ 测试 1: 获取 Alibaba 支持的模型\n";
try {
    $aliBailianService = new AliBailianService();
    $models = $aliBailianService->getSupportedModels();
    echo "  ✅ 获取成功，共 " . count($models) . " 个模型\n";
    echo "  模型列表:\n";
    foreach (array_slice($models, 0, 3) as $modelId => $modelInfo) {
        echo "    - $modelId: " . $modelInfo['name'] . "\n";
    }
} catch (\Exception $e) {
    echo "  ❌ 失败: " . $e->getMessage() . "\n";
}
echo "\n";

// 测试 2: OpenRouter 模型列表
echo "✓ 测试 2: 获取 OpenRouter 图片生成模型\n";
try {
    $openRouterService = new OpenRouterService();
    $allModels = $openRouterService->getModels();
    
    // 筛选图片生成模型
    $imageModels = array_filter($allModels, function($model) {
        $modelId = strtolower($model['id']);
        return strpos($modelId, 'flux') !== false 
            || strpos($modelId, 'dall-e') !== false
            || strpos($modelId, 'stable-diffusion') !== false;
    });
    
    echo "  ✅ 获取成功，共 " . count($imageModels) . " 个图片生成模型\n";
    foreach (array_slice($imageModels, 0, 3) as $model) {
        echo "    - " . $model['id'] . "\n";
    }
} catch (\Exception $e) {
    echo "  ❌ 失败: " . $e->getMessage() . "\n";
}
echo "\n";

// 测试 3: AIServiceManager 获取 Alibaba 模型
echo "✓ 测试 3: AIServiceManager 获取 Alibaba 模型\n";
try {
    $openRouterService = new OpenRouterService();
    $aliBailianService = new AliBailianService();
    $aiServiceManager = new AIServiceManager(
        $openRouterService,
        new \App\Services\DeepSeekService(),
        $aliBailianService
    );
    
    $aliBailianModels = $aiServiceManager->getAliBailianImageModels();
    echo "  ✅ 获取成功，共 " . count($aliBailianModels) . " 个模型\n";
    foreach (array_slice($aliBailianModels, 0, 3) as $model) {
        echo "    - " . $model['id'] . ": " . $model['name'] . "\n";
    }
} catch (\Exception $e) {
    echo "  ❌ 失败: " . $e->getMessage() . "\n";
}
echo "\n";

// 测试 4: 测试 Alibaba 图片生成（qwen-image-2.0）
echo "✓ 测试 4: 测试 Alibaba 图片生成 (qwen-image-2.0)\n";
echo "  提示词: 一只可爱的小猫咪\n";
try {
    $aliBailianService = new AliBailianService();
    $result = $aliBailianService->generateImage(
        '一只可爱的小猫咪，坐在阳光下',
        'qwen-image-2.0',
        null,
        '<auto>',
        '1024*1024',
        1
    );
    
    if ($result['success']) {
        echo "  ✅ 生成成功\n";
        echo "  状态: " . $result['status'] . "\n";
        
        if (isset($result['images']) && !empty($result['images'])) {
            echo "  图片 URL: " . substr($result['images'][0], 0, 80) . "...\n";
        } elseif (isset($result['task_id'])) {
            echo "  任务 ID: " . $result['task_id'] . "\n";
            echo "  消息: " . $result['message'] . "\n";
        }
    } else {
        echo "  ❌ 生成失败: " . ($result['message'] ?? '未知错误') . "\n";
    }
} catch (\Exception $e) {
    echo "  ❌ 异常: " . $e->getMessage() . "\n";
}
echo "\n";

// 测试 5: 测试 OpenRouter 图片生成
echo "✓ 测试 5: 测试 OpenRouter 图片生成 (flux.2-pro)\n";
echo "  提示词: a cute cat\n";
try {
    $openRouterService = new OpenRouterService();
    $result = $openRouterService->generateImage(
        'black-forest-labs/flux.2-pro',
        'a cute cat sitting in sunlight'
    );
    
    if (isset($result['image_url'])) {
        echo "  ✅ 生成成功\n";
        echo "  图片 URL: " . substr($result['image_url'], 0, 80) . "...\n";
    } else {
        echo "  ⚠️  响应: " . json_encode($result) . "\n";
    }
} catch (\Exception $e) {
    echo "  ❌ 异常: " . $e->getMessage() . "\n";
}
echo "\n";

echo "================================================\n";
echo "✅ 测试完成\n";
echo "================================================\n";
