<?php
/**
 * 检查 UCloud UModelVerse 实际支持的模型
 */

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['UCLOUD_API_KEY'] ?? '';
$apiUrl = $_ENV['UCLOUD_API_URL'] ?? 'https://api.modelverse.cn/v1';

echo "=== UCloud UModelVerse 模型列表 ===\n\n";

$ch = curl_init($apiUrl . '/models');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Accept: application/json',
]);

$body = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode === 200) {
    $data = json_decode($body, true);
    
    if (isset($data['data'])) {
        echo "总共 " . count($data['data']) . " 个模型\n\n";
        
        // 查找图片相关的模型
        echo "=== 搜索图片生成相关模型 ===\n";
        $imageModels = [];
        
        foreach ($data['data'] as $model) {
            $modelId = $model['id'] ?? '';
            $modelName = strtolower($modelId);
            
            // 搜索可能的图片生成模型关键词
            if (strpos($modelName, 'image') !== false ||
                strpos($modelName, 'vision') !== false ||
                strpos($modelName, 'dall') !== false ||
                strpos($modelName, 'stable') !== false ||
                strpos($modelName, 'diffusion') !== false ||
                strpos($modelName, 'midjourney') !== false ||
                strpos($modelName, 'flux') !== false ||
                strpos($modelName, 'sd') !== false) {
                $imageModels[] = $model;
            }
        }
        
        if (count($imageModels) > 0) {
            echo "找到 " . count($imageModels) . " 个可能的图片相关模型:\n\n";
            foreach ($imageModels as $model) {
                echo "- ID: " . ($model['id'] ?? 'N/A') . "\n";
                if (isset($model['owned_by'])) {
                    echo "  提供商: " . $model['owned_by'] . "\n";
                }
                if (isset($model['created'])) {
                    echo "  创建时间: " . date('Y-m-d H:i:s', $model['created']) . "\n";
                }
                echo "\n";
            }
        } else {
            echo "❌ 未找到图片生成相关的模型\n\n";
        }
        
        // 显示前 20 个模型作为参考
        echo "=== 前 20 个模型示例 ===\n";
        for ($i = 0; $i < min(20, count($data['data'])); $i++) {
            $model = $data['data'][$i];
            echo ($i + 1) . ". " . ($model['id'] ?? 'N/A') . "\n";
        }
        
        // 保存完整模型列表到文件
        file_put_contents(
            __DIR__ . '/ucloud_models_list.json',
            json_encode($data['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        echo "\n✅ 完整模型列表已保存到: ucloud_models_list.json\n";
        
    } else {
        echo "❌ 响应格式不正确\n";
        echo $body . "\n";
    }
} else {
    echo "❌ 请求失败: HTTP {$httpCode}\n";
    echo $body . "\n";
}

echo "\n=== 检查完成 ===\n";
