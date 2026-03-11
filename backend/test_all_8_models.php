<?php
/**
 * 测试所有8个生图模型 - 6个阿里 + 2个Flux
 */

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Services\AliBailianService;
use App\Services\OpenRouterService;
use App\Services\AIServiceManager;

$aliBailianService = new AliBailianService();
$openRouterService = new OpenRouterService();
$deepSeekService = new \App\Services\DeepSeekService();
$aiServiceManager = new AIServiceManager($openRouterService, $deepSeekService, $aliBailianService);

$prompt = 'A beautiful sunset over mountains, oil painting style';

$models = [
    // 阿里模型
    ['id' => 'alibaba-wan2.6-t2i', 'name' => '万相 2.6', 'type' => 'alibaba'],
    ['id' => 'alibaba-qwen-image-2.0-pro', 'name' => '千问 2.0 Pro', 'type' => 'alibaba'],
    ['id' => 'alibaba-qwen-image-2.0', 'name' => '千问 2.0', 'type' => 'alibaba'],
    ['id' => 'alibaba-qwen-image-max', 'name' => '千问 Max', 'type' => 'alibaba'],
    ['id' => 'alibaba-qwen-image-plus', 'name' => '千问 Plus', 'type' => 'alibaba'],
    ['id' => 'alibaba-qwen-image', 'name' => '千问图像', 'type' => 'alibaba'],
    // OpenRouter Flux 模型
    ['id' => 'black-forest-labs/flux.2-pro', 'name' => 'Flux 2 Pro', 'type' => 'openrouter'],
    ['id' => 'black-forest-labs/flux.2-flex', 'name' => 'Flux 2 Flex', 'type' => 'openrouter'],
];

echo "=== 测试所有8个生图模型 ===\n\n";

$results = [];
$success_count = 0;
$fail_count = 0;

foreach ($models as $model) {
    $model_id = $model['id'];
    $model_name = $model['name'];
    $model_type = $model['type'];
    
    echo "测试: {$model_name} ({$model_id})\n";
    
    try {
        if ($model_type === 'alibaba') {
            // 阿里模型
            $alibaba_model = str_replace('alibaba-', '', $model_id);
            $result = $aliBailianService->generateImage(
                $prompt,
                $alibaba_model,
                null,
                '<auto>',
                '1024*1024',
                1
            );
            
            if ($result['success']) {
                if (isset($result['images']) && !empty($result['images'])) {
                    echo "✅ 成功 - 直接返回图片URL\n";
                    echo "   URL: " . substr($result['images'][0], 0, 80) . "...\n";
                    $results[$model_id] = 'success';
                    $success_count++;
                } elseif (isset($result['task_id'])) {
                    echo "⏳ 异步任务 - Task ID: " . $result['task_id'] . "\n";
                    $results[$model_id] = 'async';
                } else {
                    echo "❌ 失败 - 无效响应\n";
                    $results[$model_id] = 'failed';
                    $fail_count++;
                }
            } else {
                echo "❌ 失败 - " . ($result['message'] ?? '未知错误') . "\n";
                $results[$model_id] = 'failed';
                $fail_count++;
            }
        } else {
            // OpenRouter Flux 模型
            $result = $openRouterService->generateImage($model_id, $prompt);
            
            if (isset($result['image_url'])) {
                $url_type = strpos($result['image_url'], 'data:') === 0 ? 'base64' : 'http';
                $url_length = strlen($result['image_url']);
                echo "✅ 成功 - {$url_type} 格式\n";
                echo "   大小: " . number_format($url_length) . " 字符\n";
                echo "   前缀: " . substr($result['image_url'], 0, 60) . "...\n";
                $results[$model_id] = 'success';
                $success_count++;
            } else {
                echo "❌ 失败 - 无效响应\n";
                $results[$model_id] = 'failed';
                $fail_count++;
            }
        }
    } catch (\Exception $e) {
        echo "❌ 错误 - " . $e->getMessage() . "\n";
        $results[$model_id] = 'error';
        $fail_count++;
    }
    
    echo "\n";
}

echo "=== 测试结果总结 ===\n\n";

echo "阿里模型 (6个):\n";
$alibaba_models = array_filter($models, fn($m) => $m['type'] === 'alibaba');
foreach ($alibaba_models as $model) {
    $status = $results[$model['id']] ?? 'unknown';
    $icon = match($status) {
        'success' => '✅',
        'async' => '⏳',
        'failed' => '❌',
        'error' => '❌',
        default => '❓'
    };
    echo "  {$icon} {$model['name']}\n";
}

echo "\nOpenRouter Flux 模型 (2个):\n";
$flux_models = array_filter($models, fn($m) => $m['type'] === 'openrouter');
foreach ($flux_models as $model) {
    $status = $results[$model['id']] ?? 'unknown';
    $icon = match($status) {
        'success' => '✅',
        'async' => '⏳',
        'failed' => '❌',
        'error' => '❌',
        default => '❓'
    };
    echo "  {$icon} {$model['name']}\n";
}

echo "\n统计:\n";
echo "  成功: {$success_count}\n";
echo "  失败: {$fail_count}\n";
echo "  总计: " . count($models) . "\n";

// 保存结果
file_put_contents(__DIR__ . '/test_all_8_models_results.json', json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'results' => $results,
    'summary' => [
        'success' => $success_count,
        'failed' => $fail_count,
        'total' => count($models)
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "\n详细结果已保存到: backend/test_all_8_models_results.json\n";
