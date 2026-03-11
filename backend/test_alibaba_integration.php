<?php

// 加载环境变量
$dotenv = __DIR__ . '/.env';
if (file_exists($dotenv)) {
    $lines = file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\AIServiceManager;
use App\Services\OpenRouterService;
use App\Services\DeepSeekService;
use App\Services\AliBailianService;

// 测试阿里巴巴模型集成
echo "=== 测试阿里巴巴模型集成 ===\n\n";

try {
    $openRouterService = new OpenRouterService();
    $deepSeekService = new DeepSeekService();
    $aliBailianService = new AliBailianService();
    $aiServiceManager = new AIServiceManager($openRouterService, $deepSeekService, $aliBailianService);
    
    // 1. 获取阿里巴巴图片生成模型
    echo "1. 获取阿里巴巴图片生成模型:\n";
    $aliBailianModels = $aiServiceManager->getAliBailianImageModels();
    echo "   找到 " . count($aliBailianModels) . " 个模型\n";
    foreach ($aliBailianModels as $model) {
        echo "   - " . $model['id'] . ": " . $model['name'] . "\n";
    }
    echo "\n";
    
    // 2. 测试模型识别
    echo "2. 测试模型识别:\n";
    $testModels = [
        'alibaba-wan2.6-t2i',
        'alibaba-qwen-image-2.0-pro',
        'black-forest-labs/flux.2-pro'
    ];
    
    foreach ($testModels as $model) {
        $provider = (new ReflectionMethod($aiServiceManager, 'getImageServiceProvider'))->invoke($aiServiceManager, $model);
        echo "   - $model => $provider\n";
    }
    echo "\n";
    
    // 3. 验证阿里巴巴模型配置
    echo "3. 验证阿里巴巴模型配置:\n";
    $supportedModels = $aliBailianService->getSupportedModels();
    echo "   支持的模型数: " . count($supportedModels) . "\n";
    
    // 统计已通过测试的模型
    $passedModels = ['wan2.6-t2i', 'qwen-image-2.0-pro', 'qwen-image-2.0', 'qwen-image-max', 'qwen-image-plus', 'qwen-image'];
    $passedCount = 0;
    foreach ($passedModels as $model) {
        if (isset($supportedModels[$model])) {
            $passedCount++;
            echo "   ✓ $model - " . $supportedModels[$model]['name'] . "\n";
        }
    }
    echo "   已通过测试的模型: $passedCount/" . count($passedModels) . "\n";
    echo "\n";
    
    echo "✅ 集成测试完成！\n";
    
} catch (Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
