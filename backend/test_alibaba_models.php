<?php
/**
 * 测试阿里百练所有图片生成模型
 */

require_once __DIR__ . '/vendor/autoload.php';

// 加载环境变量
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Services\AliBailianService;

$service = new AliBailianService();

echo "=== 阿里百练图片生成模型测试 ===\n\n";

// 获取所有支持的模型
$models = $service->getSupportedModels();

echo "支持的模型数量: " . count($models) . "\n\n";

foreach ($models as $modelId => $modelInfo) {
    echo "模型: " . $modelId . "\n";
    echo "名称: " . $modelInfo['name'] . "\n";
    echo "描述: " . $modelInfo['desc'] . "\n";
    echo "类别: " . $modelInfo['category'] . "\n";
    echo "特性: " . implode(', ', $modelInfo['features']) . "\n";
    echo "支持尺寸: " . implode(', ', $modelInfo['sizes']) . "\n";
    echo "支持风格: " . ($modelInfo['styles'] ? '是' : '否') . "\n";
    echo "\n";
}

// 测试生成图片（仅测试 wanx-v1，因为其他模型可能需要特殊配置）
echo "=== 测试图片生成 ===\n\n";

try {
    echo "测试模型: wanx-v1\n";
    echo "提示词: A beautiful sunset over mountains\n";
    
    $result = $service->generateImage(
        'A beautiful sunset over mountains',
        'wanx-v1',
        'blurry, low quality',
        '<photography>',
        '1024*1024'
    );
    
    if ($result['success']) {
        echo "✅ 成功! 任务ID: " . $result['task_id'] . "\n";
        echo "消息: " . $result['message'] . "\n";
    } else {
        echo "❌ 失败: " . ($result['message'] ?? '未知错误') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ 异常: " . $e->getMessage() . "\n";
}

echo "\n=== 测试完成 ===\n";
