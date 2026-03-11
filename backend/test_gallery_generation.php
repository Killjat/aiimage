<?php
/**
 * 测试图片库生成 - 生成3张图片验证流程
 */

require_once __DIR__ . '/vendor/autoload.php';

// 加载 .env 文件
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require_once __DIR__ . '/src/Database/Database.php';
require_once __DIR__ . '/src/Services/AliBailianService.php';
require_once __DIR__ . '/src/Services/ImageGalleryService.php';

use App\Services\AliBailianService;
use App\Services\ImageGalleryService;
$testPrompts = [
    "A serene mountain landscape at sunrise with golden light, misty valleys, and snow-capped peaks, professional photography, 8k resolution",
    "Portrait of a confident woman with artistic makeup, colorful paint splashes, studio lighting, professional photography",
    "Modern futuristic city skyline at night, neon lights, flying vehicles, cyberpunk architecture, rain reflections"
];

$models = [
    'wan2.6-t2i',
    'qwen-image-2.0-pro',
    'qwen-image-2.0'
];

$alibaiService = new AliBailianService();
$galleryService = new ImageGalleryService();

echo "开始测试图片库生成流程...\n";
echo "将生成 " . count($testPrompts) . " 张测试图片\n\n";

$results = [];

foreach ($testPrompts as $index => $prompt) {
    $model = $models[$index];
    
    echo "[测试 " . ($index + 1) . "] 提示词: " . substr($prompt, 0, 60) . "...\n";
    echo "模型: $model\n";
    
    try {
        // 生成图片
        echo "  → 调用API生成图片...\n";
        $result = $alibaiService->generateImage($prompt, $model);
        
        if ($result['success'] && !empty($result['images'])) {
            $imageUrl = $result['images'][0];  // 取第一张图片
            echo "  ✅ 生成成功\n";
            echo "  → 图片URL: " . substr($imageUrl, 0, 80) . "...\n";
            
            // 保存到数据库
            echo "  → 保存到数据库...\n";
            
            $saveResult = $galleryService->saveImage(
                1,  // user_id
                'System',  // username
                $model,  // model
                $prompt,  // prompt
                $imageUrl,  // image_url
                'Alibaba Bailian',  // llm_model
                'low quality, blurry, distorted',  // negative_prompt
                '1024x1024',  // image_size
                'high',  // image_quality
                true,  // is_public
                '测试生成的示例图片',  // description
                'test,sample,alibaba'  // tags
            );
            
            if ($saveResult) {
                echo "  ✅ 保存成功\n";
                $results[] = [
                    'index' => $index + 1,
                    'status' => 'success',
                    'model' => $model,
                    'image_url' => $imageUrl
                ];
            } else {
                echo "  ❌ 保存失败\n";
                $results[] = [
                    'index' => $index + 1,
                    'status' => 'save_failed',
                    'model' => $model
                ];
            }
        } else {
            echo "  ❌ 生成失败: " . ($result['error'] ?? 'Unknown error') . "\n";
            $results[] = [
                'index' => $index + 1,
                'status' => 'generation_failed',
                'model' => $model,
                'error' => $result['error'] ?? 'Unknown error'
            ];
        }
    } catch (Exception $e) {
        echo "  ❌ 异常: " . $e->getMessage() . "\n";
        $results[] = [
            'index' => $index + 1,
            'status' => 'exception',
            'model' => $model,
            'error' => $e->getMessage()
        ];
    }
    
    echo "\n";
    sleep(2);
}

echo str_repeat("=", 60) . "\n";
echo "测试完成！\n";

$successCount = count(array_filter($results, fn($r) => $r['status'] === 'success'));
echo "成功: $successCount / " . count($testPrompts) . "\n";

if ($successCount === count($testPrompts)) {
    echo "\n✅ 所有测试通过！可以运行完整的生成脚本。\n";
    echo "执行: php generate_gallery_images.php\n";
} else {
    echo "\n⚠️ 部分测试失败，请检查错误信息。\n";
}

echo str_repeat("=", 60) . "\n";

// 保存测试结果
file_put_contents(
    __DIR__ . '/test_gallery_generation_results.json',
    json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo "\n测试结果已保存到: test_gallery_generation_results.json\n";
?>
