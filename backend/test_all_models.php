<?php
/**
 * 测试所有阿里百练图片生成模型
 * 提示词: 特朗普拿着圣火令对着内贾德
 */

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['ALIBABA_BAILIAN_API_KEY'] ?? '';

if (empty($apiKey)) {
    echo "错误: 未配置 ALIBABA_BAILIAN_API_KEY\n";
    exit(1);
}

$prompt = "Trump holding the sacred torch facing Ahmadinejad, cinematic lighting, high quality, detailed";
$negativePrompt = "blurry, low quality, distorted";

$models = [
    // 万相系列
    'wan2.6-t2i' => ['size' => '1280*1280', 'style' => '<auto>'],
    'wan2.5-t2i-preview' => ['size' => '1280*1280', 'style' => '<auto>'],
    'wan2.2-t2i-flash' => ['size' => '1024*1024', 'style' => '<auto>'],
    'wanx-v1' => ['size' => '1024*1024', 'style' => '<photography>'],
    
    // Stable Diffusion 系列
    'stable-diffusion-v1.5' => ['size' => '1024*1024'],
    'stable-diffusion-xl' => ['size' => '1024*1024'],
    'stable-diffusion-3.5-large' => ['size' => '1024*1024'],
    
    // 千问图像系列
    'qwen-image-2.0-pro' => ['size' => '1024*1024'],
    'qwen-image-2.0' => ['size' => '1024*1024'],
    'qwen-image-max' => ['size' => '1024*1024'],
    'qwen-image-plus' => ['size' => '1024*1024'],
    'qwen-image' => ['size' => '1024*1024'],
];

echo "=== 测试所有阿里百练图片生成模型 ===\n";
echo "提示词: $prompt\n";
echo "反向提示词: $negativePrompt\n\n";

$results = [];

foreach ($models as $model => $config) {
    echo "测试模型: $model ... ";
    
    $requestData = [
        'model' => $model,
        'input' => [
            'prompt' => $prompt,
            'negative_prompt' => $negativePrompt
        ],
        'parameters' => [
            'n' => 1,
            'size' => $config['size']
        ]
    ];
    
    // 如果是 wanx-v1，添加风格参数
    if ($model === 'wanx-v1') {
        $requestData['parameters']['style'] = $config['style'];
    }
    
    $ch = curl_init('https://dashscope.aliyuncs.com/api/v1/services/aigc/text2image/image-synthesis');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
        'X-DashScope-Async: enable'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ 请求失败: $error\n";
        $results[$model] = ['status' => 'error', 'message' => $error];
        continue;
    }
    
    $data = json_decode($response, true);
    
    if ($httpCode === 200 && isset($data['output']['task_id'])) {
        $taskId = $data['output']['task_id'];
        echo "✅ 任务创建成功\n";
        echo "   任务ID: $taskId\n";
        
        $results[$model] = [
            'status' => 'created',
            'task_id' => $taskId,
            'model' => $model,
            'size' => $config['size']
        ];
        
        // 等待任务完成（最多等待 60 秒）
        echo "   等待生成中";
        for ($i = 0; $i < 60; $i++) {
            sleep(1);
            echo ".";
            
            $ch = curl_init("https://dashscope.aliyuncs.com/api/v1/tasks/$taskId");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            
            $taskResponse = curl_exec($ch);
            curl_close($ch);
            
            $taskData = json_decode($taskResponse, true);
            
            if (isset($taskData['output']['task_status'])) {
                $status = $taskData['output']['task_status'];
                
                if ($status === 'SUCCEEDED') {
                    if (isset($taskData['output']['results'][0]['url'])) {
                        $imageUrl = $taskData['output']['results'][0]['url'];
                        echo "\n   ✅ 生成成功\n";
                        echo "   图片URL: $imageUrl\n";
                        
                        $results[$model]['status'] = 'completed';
                        $results[$model]['image_url'] = $imageUrl;
                        break;
                    }
                } elseif ($status === 'FAILED') {
                    echo "\n   ❌ 生成失败\n";
                    $results[$model]['status'] = 'failed';
                    $results[$model]['error'] = $taskData['output']['message'] ?? '未知错误';
                    break;
                }
            }
        }
        
        if (!isset($results[$model]['status']) || $results[$model]['status'] === 'created') {
            echo "\n   ⏱️  超时\n";
            $results[$model]['status'] = 'timeout';
        }
    } else {
        $errorMsg = $data['message'] ?? $data['code'] ?? '未知错误';
        echo "❌ 失败 ($errorMsg)\n";
        $results[$model] = ['status' => 'error', 'message' => $errorMsg];
    }
    
    echo "\n";
}

echo "\n=== 测试结果总结 ===\n\n";

$completed = 0;
$failed = 0;
$timeout = 0;

foreach ($results as $model => $result) {
    $status = $result['status'];
    
    if ($status === 'completed') {
        echo "✅ $model\n";
        echo "   URL: " . substr($result['image_url'], 0, 80) . "...\n";
        $completed++;
    } elseif ($status === 'timeout') {
        echo "⏱️  $model (超时)\n";
        $timeout++;
    } else {
        echo "❌ $model (" . ($result['message'] ?? $result['error'] ?? '未知错误') . ")\n";
        $failed++;
    }
}

echo "\n统计:\n";
echo "成功: $completed\n";
echo "失败: $failed\n";
echo "超时: $timeout\n";
echo "总计: " . count($results) . "\n";

// 保存结果到文件
file_put_contents(__DIR__ . '/test_results.json', json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "\n详细结果已保存到: backend/test_results.json\n";
