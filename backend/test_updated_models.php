<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\AliBailianService;

// 加载环境变量
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$service = new AliBailianService();

// 测试提示词
$prompt = "Trump holding the sacred torch facing Ahmadinejad";

// 要测试的模型
$modelsToTest = [
    'wan2.6-t2i',           // 新端点
    'wan2.2-t2i-flash',     // 旧端点
    'wanx-v1',              // 旧端点
    'qwen-image-2.0-pro',   // 新端点
    'qwen-image-2.0',       // 新端点
];

$results = [];

foreach ($modelsToTest as $model) {
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

// 保存结果
file_put_contents(__DIR__ . '/test_updated_results.json', json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Results saved to test_updated_results.json\n";
