<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\AliBailianService;

// 加载环境变量
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$service = new AliBailianService();

echo "=== 阿里百练模型集成完整测试 ===\n\n";

// 测试提示词
$testPrompts = [
    'safe' => 'A beautiful sunset over the ocean, realistic, high quality',
    'cat' => 'A cute orange cat sitting on a sunny windowsill, realistic, high quality',
    'nature' => 'A serene mountain landscape with snow-capped peaks and a clear blue sky'
];

// 模型分类
$models = [
    'sync' => [
        'wan2.6-t2i',
        'qwen-image-2.0-pro',
        'qwen-image-2.0',
        'qwen-image-max',
        'qwen-image-plus',
        'qwen-image'
    ],
    'async' => [
        'wan2.5-t2i-preview',
        'wan2.2-t2i-flash',
        'wanx-v1'
    ],
    'unavailable' => [
        'stable-diffusion-v1.5',
        'stable-diffusion-xl',
        'stable-diffusion-3.5-large',
        'qwen-image-edit-plus'
    ]
];

$results = [
    'sync' => [],
    'async' => [],
    'unavailable' => []
];

// 测试同步模型
echo "1. 测试同步模型（应该立即返回图片）\n";
echo str_repeat("-", 50) . "\n";

foreach ($models['sync'] as $model) {
    echo "测试 $model... ";
    
    try {
        $result = $service->generateImage($testPrompts['safe'], $model);
        
        if ($result['success'] && $result['status'] === 'completed' && !empty($result['images'])) {
            echo "✅ 成功\n";
            $results['sync'][$model] = 'success';
        } else {
            echo "❌ 失败\n";
            $results['sync'][$model] = 'failed';
        }
    } catch (\Exception $e) {
        echo "❌ 错误: " . substr($e->getMessage(), 0, 50) . "\n";
        $results['sync'][$model] = 'error';
    }
}

echo "\n";

// 测试异步模型
echo "2. 测试异步模型（应该返回 task_id）\n";
echo str_repeat("-", 50) . "\n";

$taskIds = [];

foreach ($models['async'] as $model) {
    echo "测试 $model... ";
    
    try {
        $result = $service->generateImage($testPrompts['safe'], $model);
        
        if ($result['success'] && isset($result['task_id'])) {
            echo "✅ 任务创建成功 (ID: " . substr($result['task_id'], 0, 8) . "...)\n";
            $results['async'][$model] = 'task_created';
            $taskIds[$model] = $result['task_id'];
        } else {
            echo "❌ 失败\n";
            $results['async'][$model] = 'failed';
        }
    } catch (\Exception $e) {
        echo "❌ 错误: " . substr($e->getMessage(), 0, 50) . "\n";
        $results['async'][$model] = 'error';
    }
}

echo "\n";

// 等待异步任务完成
if (!empty($taskIds)) {
    echo "3. 等待异步任务完成（等待 5 秒）\n";
    echo str_repeat("-", 50) . "\n";
    
    sleep(5);
    
    foreach ($taskIds as $model => $taskId) {
        echo "查询 $model... ";
        
        try {
            $taskResult = $service->getTaskResult($taskId);
            
            if ($taskResult['success'] && $taskResult['status'] === 'completed' && !empty($taskResult['images'])) {
                echo "✅ 完成\n";
                $results['async'][$model] = 'completed';
            } else {
                echo "⏳ 处理中\n";
                $results['async'][$model] = 'processing';
            }
        } catch (\Exception $e) {
            echo "❌ 错误: " . substr($e->getMessage(), 0, 50) . "\n";
            $results['async'][$model] = 'error';
        }
    }
}

echo "\n";

// 测试不可用的模型
echo "4. 测试不可用的模型（应该返回错误）\n";
echo str_repeat("-", 50) . "\n";

foreach ($models['unavailable'] as $model) {
    echo "测试 $model... ";
    
    try {
        $result = $service->generateImage($testPrompts['safe'], $model);
        
        if (!$result['success']) {
            echo "✅ 正确返回错误\n";
            $results['unavailable'][$model] = 'error_as_expected';
        } else {
            echo "❌ 意外成功\n";
            $results['unavailable'][$model] = 'unexpected_success';
        }
    } catch (\Exception $e) {
        echo "✅ 正确抛出异常\n";
        $results['unavailable'][$model] = 'exception_as_expected';
    }
}

echo "\n";

// 打印总结
echo "=== 测试总结 ===\n";
echo str_repeat("=", 50) . "\n";

$syncSuccess = count(array_filter($results['sync'], fn($v) => $v === 'success'));
$asyncSuccess = count(array_filter($results['async'], fn($v) => $v === 'completed'));
$unavailableCorrect = count(array_filter($results['unavailable'], fn($v) => strpos($v, 'expected') !== false));

echo "同步模型: $syncSuccess/" . count($models['sync']) . " 成功\n";
echo "异步模型: $asyncSuccess/" . count($models['async']) . " 完成\n";
echo "不可用模型: $unavailableCorrect/" . count($models['unavailable']) . " 正确处理\n";

$totalSuccess = $syncSuccess + $asyncSuccess + $unavailableCorrect;
$totalTests = count($models['sync']) + count($models['async']) + count($models['unavailable']);

echo "\n总体: $totalSuccess/$totalTests 测试通过\n";

if ($totalSuccess === $totalTests) {
    echo "\n✅ 所有测试通过！\n";
} else {
    echo "\n⚠️ 部分测试失败\n";
}

// 保存详细结果
file_put_contents(__DIR__ . '/test_comprehensive_results.json', json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'results' => $results,
    'summary' => [
        'sync_success' => $syncSuccess,
        'sync_total' => count($models['sync']),
        'async_success' => $asyncSuccess,
        'async_total' => count($models['async']),
        'unavailable_correct' => $unavailableCorrect,
        'unavailable_total' => count($models['unavailable']),
        'total_success' => $totalSuccess,
        'total_tests' => $totalTests
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "\n详细结果已保存到 test_comprehensive_results.json\n";
