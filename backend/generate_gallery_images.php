<?php
/**
 * 生成图片库初始数据
 * 使用阿里大模型生成20张多样化的图片
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

// 20个精心设计的提示词
$prompts = [
    // 风景类 (5张)
    "A serene mountain landscape at sunrise with golden light, misty valleys, and snow-capped peaks, professional photography, 8k resolution",
    "Tropical beach paradise with crystal clear turquoise water, white sand, palm trees, and vibrant sunset, cinematic lighting",
    "Ancient temple ruins in dense jungle, overgrown with vines, mysterious atmosphere, golden hour lighting, detailed architecture",
    "Northern lights dancing over a frozen lake, aurora borealis in vibrant green and purple, starry night sky, long exposure",
    "Desert sand dunes at dusk with warm orange and purple colors, minimalist composition, dramatic shadows",
    
    // 人物肖像类 (5张)
    "Portrait of a confident woman with artistic makeup, colorful paint splashes, studio lighting, professional photography",
    "Young man with cyberpunk style, neon lights, futuristic fashion, dark moody atmosphere, high contrast",
    "Elderly person with wise expression, warm natural lighting, detailed facial features, emotional depth",
    "Fashion model in elegant evening gown, dramatic lighting, luxury aesthetic, high fashion photography",
    "Child playing with soap bubbles in sunlight, joyful expression, soft natural lighting, warm colors",
    
    // 艺术创意类 (5张)
    "Abstract digital art with flowing liquid colors, geometric shapes, modern design, vibrant neon palette",
    "Steampunk airship flying through clouds, intricate mechanical details, brass and copper colors, fantasy art",
    "Underwater scene with bioluminescent creatures, coral reef, magical glowing lights, surreal atmosphere",
    "Enchanted forest with magical creatures, glowing mushrooms, fairy lights, fantasy illustration style",
    "Space scene with multiple planets, nebula, stars, cosmic dust, deep space exploration aesthetic",
    
    // 建筑城市类 (5张)
    "Modern futuristic city skyline at night, neon lights, flying vehicles, cyberpunk architecture, rain reflections",
    "Traditional Japanese temple with cherry blossoms, peaceful garden, wooden architecture, spring season",
    "Grand European cathedral interior, ornate details, stained glass windows, dramatic lighting, architectural photography",
    "Cozy coffee shop interior, warm lighting, wooden furniture, plants, comfortable atmosphere, hygge style",
    "Abandoned industrial warehouse, urban exploration, dramatic shadows, moody atmosphere, artistic composition"
];

// 模型配置
$models = [
    'wan2.6-t2i',
    'qwen-image-2.0-pro',
    'qwen-image-2.0',
    'qwen-image-max',
    'qwen-image-plus',
    'qwen-image'
];

// 初始化服务
$alibaiService = new AliBailianService();
$galleryService = new ImageGalleryService();

echo "开始生成图片库初始数据...\n";
echo "总共需要生成: " . count($prompts) . " 张图片\n";
echo "使用模型: " . implode(', ', $models) . "\n\n";

$successCount = 0;
$failCount = 0;
$results = [];

foreach ($prompts as $index => $prompt) {
    $modelIndex = $index % count($models);
    $model = $models[$modelIndex];
    
    echo "[" . ($index + 1) . "/" . count($prompts) . "] 生成图片: ";
    echo substr($prompt, 0, 50) . "...\n";
    echo "  使用模型: $model\n";
    
    try {
        // 调用阿里大模型生成图片
        $result = $alibaiService->generateImage($prompt, $model);
        
        if ($result['success'] && !empty($result['images'])) {
            $imageUrl = $result['images'][0];  // 取第一张图片
            
            // 保存到图片库
            $galleryService->saveImage(
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
                '系统生成的示例图片',  // description
                implode(',', array_slice(explode(' ', $prompt), 0, 5))  // tags
            );
            
            echo "  ✅ 成功保存到图片库\n";
            $successCount++;
            $results[] = [
                'prompt' => $prompt,
                'model' => $model,
                'status' => 'success',
                'image_url' => $imageUrl
            ];
        } else {
            echo "  ❌ 生成图片失败: " . ($result['error'] ?? 'Unknown error') . "\n";
            $failCount++;
            $results[] = [
                'prompt' => $prompt,
                'model' => $model,
                'status' => 'generation_failed',
                'error' => $result['error'] ?? 'Unknown error'
            ];
        }
    } catch (Exception $e) {
        echo "  ❌ 异常: " . $e->getMessage() . "\n";
        $failCount++;
        $results[] = [
            'prompt' => $prompt,
            'model' => $model,
            'status' => 'exception',
            'error' => $e->getMessage()
        ];
    }
    
    // 延迟以避免API限流
    sleep(2);
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "生成完成！\n";
echo "成功: $successCount\n";
echo "失败: $failCount\n";
echo "成功率: " . round(($successCount / count($prompts)) * 100, 1) . "%\n";
echo str_repeat("=", 60) . "\n";

// 保存结果到JSON文件
file_put_contents(
    __DIR__ . '/gallery_generation_results.json',
    json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo "\n详细结果已保存到: gallery_generation_results.json\n";
?>
