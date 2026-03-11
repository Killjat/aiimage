<?php
/**
 * 测试远程服务器上的 Alibaba 图片生成功能
 */

// 加载环境变量
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\AliBailianService;

echo "🧪 测试远程 Alibaba 图片生成功能\n";
echo "================================================\n\n";

try {
    // 初始化服务
    $aliBailianService = new AliBailianService();
    
    // 测试 1: 验证 API Key
    echo "✓ 测试 1: 验证 API Key 配置\n";
    $apiKey = $_ENV['ALIBABA_BAILIAN_API_KEY'] ?? '';
    if (empty($apiKey)) {
        echo "  ❌ API Key 未配置\n";
        exit(1);
    }
    echo "  ✅ API Key 已配置: " . substr($apiKey, 0, 10) . "...\n\n";
    
    // 测试 2: 获取支持的模型
    echo "✓ 测试 2: 获取支持的模型\n";
    $models = $aliBailianService->getSupportedModels();
    echo "  ✅ 支持 " . count($models) . " 个模型\n";
    echo "  模型列表:\n";
    foreach (array_slice($models, 0, 5) as $modelId => $modelInfo) {
        echo "    - $modelId: " . $modelInfo['name'] . "\n";
    }
    echo "\n";
    
    // 测试 3: 使用 qwen-image-2.0 生成图片（同步）
    echo "✓ 测试 3: 生成图片 (qwen-image-2.0)\n";
    echo "  提示词: 一只可爱的小猫咪\n";
    
    $result = $aliBailianService->generateImage(
        '一只可爱的小猫咪，坐在阳光下',
        'qwen-image-2.0',
        null,
        '<auto>',
        '1024*1024',
        1
    );
    
    if ($result['success']) {
        echo "  ✅ 图片生成成功\n";
        echo "  状态: " . $result['status'] . "\n";
        
        if (isset($result['images']) && !empty($result['images'])) {
            echo "  图片 URL: " . substr($result['images'][0], 0, 50) . "...\n";
        } elseif (isset($result['task_id'])) {
            echo "  任务 ID: " . $result['task_id'] . "\n";
            echo "  消息: " . $result['message'] . "\n";
        }
    } else {
        echo "  ❌ 生成失败: " . ($result['message'] ?? '未知错误') . "\n";
        exit(1);
    }
    
    echo "\n";
    echo "================================================\n";
    echo "✅ 所有测试通过！\n";
    echo "================================================\n";
    echo "\n远程 Alibaba 图片生成功能正常运行。\n";
    
} catch (\Exception $e) {
    echo "❌ 测试失败: " . $e->getMessage() . "\n";
    echo "\n堆栈跟踪:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
