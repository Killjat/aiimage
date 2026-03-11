<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\AliBailianService;

// 加载环境变量
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$service = new AliBailianService();

// 安全的测试提示词
$prompt = "A beautiful orange cat sitting on a sunny windowsill, realistic, high quality";

// 所有支持的模型
$allModels = array_keys($service->getSupportedModels());

$results = [];
$taskIds = [];

echo "=== Testing all models ===\n\n";

foreach ($allModels as $model) {
    echo "Testing model: $model\n";
    
    try {
        $result = $service->generateImage($prompt, $model, null, '<auto>', '1024*1024', 1);
        
        if ($result['success']) {
            if (isset($result['task_id'])) {
                // 异步任务
                $results[$model] = [
                    'status' => 'task_created',
                    'task_id' => $result['task_id'],
                    'message' => $result['message']
                ];
                $taskIds[$model] = $result['task_id'];
                echo "  ✓ Task created: {$result['task_id']}\n";
            } else {
                // 同步结果
                $results[$model] = [
                    'status' => 'completed',
                    'images' => $result['images'] ?? [],
                    'message' => $result['message']
                ];
                echo "  ✓ Images generated: " . count($result['images'] ?? []) . "\n";
            }
        } else {
            $results[$model] = [
                'status' => 'failed',
                'message' => $result['message'] ?? 'Unknown error'
            ];
            echo "  ✗ Failed: {$result['message']}\n";
        }
    } catch (\Exception $e) {
        $results[$model] = [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
        echo "  ✗ Error: {$e->getMessage()}\n";
    }
    
    echo "\n";
}

// 等待异步任务完成
if (!empty($taskIds)) {
    echo "\n=== Waiting for async tasks to complete ===\n\n";
    sleep(5); // 等待 5 秒
    
    foreach ($taskIds as $model => $taskId) {
        echo "Checking task for $model: $taskId\n";
        
        try {
            $taskResult = $service->getTaskResult($taskId);
            
            if ($taskResult['success']) {
                if ($taskResult['status'] === 'completed') {
                    $results[$model]['status'] = 'completed';
                    $results[$model]['images'] = $taskResult['images'] ?? [];
                    echo "  ✓ Task completed: " . count($taskResult['images'] ?? []) . " images\n";
                } else {
                    $results[$model]['status'] = $taskResult['status'];
                    echo "  ⏳ Task status: {$taskResult['status']}\n";
                }
            } else {
                $results[$model]['status'] = 'failed';
                $results[$model]['error'] = $taskResult['message'];
                echo "  ✗ Task failed: {$taskResult['message']}\n";
            }
        } catch (\Exception $e) {
            $results[$model]['status'] = 'error';
            $results[$model]['error'] = $e->getMessage();
            echo "  ✗ Error: {$e->getMessage()}\n";
        }
        
        echo "\n";
    }
}

// 保存结果
file_put_contents(__DIR__ . '/test_all_updated_results.json', json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Results saved to test_all_updated_results.json\n";

// 打印总结
echo "\n=== Summary ===\n";
$completed = 0;
$processing = 0;
$failed = 0;

foreach ($results as $model => $result) {
    if ($result['status'] === 'completed') {
        $completed++;
    } elseif ($result['status'] === 'task_created' || $result['status'] === 'processing') {
        $processing++;
    } else {
        $failed++;
    }
}

echo "Completed: $completed\n";
echo "Processing: $processing\n";
echo "Failed: $failed\n";
echo "Total: " . count($results) . "\n";
