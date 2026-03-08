<?php
/**
 * UCloud 图片生成测试脚本
 * 
 * 使用方法:
 * php test_ucloud_image.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// 加载环境变量
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Services\UCloudService;

echo "=== UCloud 图片生成测试 ===\n\n";

// 检查 API Key
$apiKey = $_ENV['UCLOUD_API_KEY'] ?? '';
if (empty($apiKey) || $apiKey === 'your_ucloud_api_key_here') {
    echo "❌ 错误: UCLOUD_API_KEY 未配置\n";
    echo "请在 backend/.env 文件中设置 UCLOUD_API_KEY\n\n";
    echo "获取 API Key:\n";
    echo "1. 访问 UCloud 控制台: https://console.ucloud.cn\n";
    echo "2. 进入 AI 服务管理\n";
    echo "3. 创建 API Key\n";
    exit(1);
}

echo "✅ API Key 已配置\n";
echo "API URL: " . ($_ENV['UCLOUD_API_URL'] ?? 'https://api.ucloud.cn/v1') . "\n\n";

// 创建服务实例
$ucloudService = new UCloudService();

// 测试 1: 获取模型列表
echo "--- 测试 1: 获取模型列表 ---\n";
try {
    $models = $ucloudService->getModels();
    echo "✅ 成功获取 " . count($models) . " 个模型\n\n";
    
    echo "图片生成模型:\n";
    foreach ($models as $model) {
        if (isset($model['supports_image_generation']) && $model['supports_image_generation']) {
            echo "  - {$model['id']}: {$model['name']}\n";
            if (isset($model['description'])) {
                echo "    {$model['description']}\n";
            }
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "⚠️  获取模型列表失败: " . $e->getMessage() . "\n";
    echo "使用默认模型列表\n\n";
}

// 测试 2: 生成图片 (DALL-E 3)
echo "--- 测试 2: 使用 DALL-E 3 生成图片 ---\n";
$prompt = "一只可爱的橘色猫咪坐在窗台上，温暖的阳光";
echo "提示词: {$prompt}\n";
echo "模型: dall-e-3\n";
echo "尺寸: 1024x1024\n";
echo "质量: standard\n\n";

try {
    echo "正在生成图片...\n";
    $result = $ucloudService->generateImage(
        'dall-e-3',
        $prompt,
        '1024x1024',
        'standard'
    );
    
    echo "✅ 图片生成成功!\n";
    echo "图片 URL: " . substr($result['image_url'], 0, 100) . "...\n";
    echo "格式: {$result['format']}\n";
    echo "模型: {$result['model']}\n\n";
    
    // 保存图片信息到文件
    $outputFile = __DIR__ . '/test_output_ucloud_dalle3.json';
    file_put_contents($outputFile, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "图片信息已保存到: {$outputFile}\n\n";
    
} catch (Exception $e) {
    echo "❌ 图片生成失败: " . $e->getMessage() . "\n\n";
}

// 测试 3: 生成图片 (DALL-E 2)
echo "--- 测试 3: 使用 DALL-E 2 生成图片 ---\n";
$prompt2 = "a beautiful sunset over mountains";
echo "提示词: {$prompt2}\n";
echo "模型: dall-e-2\n";
echo "尺寸: 512x512\n\n";

try {
    echo "正在生成图片...\n";
    $result2 = $ucloudService->generateImage(
        'dall-e-2',
        $prompt2,
        '512x512',
        'standard'
    );
    
    echo "✅ 图片生成成功!\n";
    echo "图片 URL: " . substr($result2['image_url'], 0, 100) . "...\n";
    echo "格式: {$result2['format']}\n";
    echo "模型: {$result2['model']}\n\n";
    
    // 保存图片信息到文件
    $outputFile2 = __DIR__ . '/test_output_ucloud_dalle2.json';
    file_put_contents($outputFile2, json_encode($result2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "图片信息已保存到: {$outputFile2}\n\n";
    
} catch (Exception $e) {
    echo "❌ 图片生成失败: " . $e->getMessage() . "\n\n";
}

// 测试 4: 生成图片 (Stable Diffusion XL)
echo "--- 测试 4: 使用 Stable Diffusion XL 生成图片 ---\n";
$prompt3 = "cyberpunk city at night, neon lights, futuristic";
echo "提示词: {$prompt3}\n";
echo "模型: stable-diffusion-xl\n";
echo "尺寸: 1024x1024\n\n";

try {
    echo "正在生成图片...\n";
    $result3 = $ucloudService->generateImage(
        'stable-diffusion-xl',
        $prompt3,
        '1024x1024',
        'standard'
    );
    
    echo "✅ 图片生成成功!\n";
    echo "图片 URL: " . substr($result3['image_url'], 0, 100) . "...\n";
    echo "格式: {$result3['format']}\n";
    echo "模型: {$result3['model']}\n\n";
    
    // 保存图片信息到文件
    $outputFile3 = __DIR__ . '/test_output_ucloud_sdxl.json';
    file_put_contents($outputFile3, json_encode($result3, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "图片信息已保存到: {$outputFile3}\n\n";
    
} catch (Exception $e) {
    echo "❌ 图片生成失败: " . $e->getMessage() . "\n\n";
}

echo "=== 测试完成 ===\n\n";

echo "📝 测试总结:\n";
echo "- 如果所有测试都成功，说明 UCloud API 配置正确\n";
echo "- 如果测试失败，请检查:\n";
echo "  1. API Key 是否正确\n";
echo "  2. API URL 是否正确\n";
echo "  3. 网络连接是否正常\n";
echo "  4. UCloud 账户是否有足够的配额\n\n";

echo "💡 提示:\n";
echo "- 生成的图片信息保存在 test_output_ucloud_*.json 文件中\n";
echo "- 如果是 base64 格式，可以复制到浏览器查看\n";
echo "- 如果是 URL 格式，可以直接在浏览器中打开\n";
