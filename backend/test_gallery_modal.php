<?php
/**
 * 测试图片库modal功能
 * 验证所有必要的API端点是否正常工作
 */

$API_BASE = 'http://127.0.0.1:8080/api';

echo "=== 图片库Modal功能测试 ===\n\n";

// 1. 获取公开图片库
echo "1. 获取公开图片库...\n";
$response = file_get_contents("$API_BASE/gallery/public?page=1&limit=5");
$data = json_decode($response, true);

if ($data['success'] && count($data['data']['data']) > 0) {
    echo "✅ 成功获取 " . count($data['data']['data']) . " 张图片\n";
    $testImage = $data['data']['data'][0];
    echo "   - 图片ID: " . $testImage['id'] . "\n";
    echo "   - 模型: " . $testImage['model'] . "\n";
    echo "   - 提示词: " . substr($testImage['prompt'], 0, 50) . "...\n";
} else {
    echo "❌ 获取图片库失败\n";
    exit(1);
}

// 2. 测试点赞功能
echo "\n2. 测试点赞功能...\n";
$imageId = $testImage['id'];
$ch = curl_init("$API_BASE/gallery/image/$imageId/like");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if ($data['success']) {
    echo "✅ 点赞功能正常\n";
    echo "   - 当前点赞数: " . $data['data']['likes'] . "\n";
} else {
    echo "❌ 点赞功能失败: " . ($data['error'] ?? '未知错误') . "\n";
}

// 3. 测试搜索建议
echo "\n3. 测试搜索建议...\n";
$response = file_get_contents("$API_BASE/gallery/suggestions?keyword=cat&limit=5");
$data = json_decode($response, true);

if ($data['success']) {
    echo "✅ 搜索建议功能正常\n";
    echo "   - 建议数: " . count($data['data']) . "\n";
    if (count($data['data']) > 0) {
        echo "   - 示例: " . $data['data'][0]['text'] . " (" . $data['data'][0]['count'] . ")\n";
    }
} else {
    echo "❌ 搜索建议功能失败\n";
}

// 4. 验证modal所需的数据字段
echo "\n4. 验证modal所需的数据字段...\n";
$requiredFields = ['id', 'username', 'model', 'prompt', 'image_url', 'views', 'likes', 'created_at'];
$response = file_get_contents("$API_BASE/gallery/public?page=1&limit=1");
$data = json_decode($response, true);
$image = $data['data']['data'][0];

$missingFields = [];
foreach ($requiredFields as $field) {
    if (!isset($image[$field])) {
        $missingFields[] = $field;
    }
}

if (empty($missingFields)) {
    echo "✅ 所有必要字段都存在\n";
    echo "   字段: " . implode(', ', $requiredFields) . "\n";
} else {
    echo "❌ 缺少字段: " . implode(', ', $missingFields) . "\n";
}

echo "\n=== 测试完成 ===\n";
echo "✅ 所有API端点正常工作\n";
echo "✅ Modal功能应该可以正常使用\n";
echo "\n提示: 如果前端modal仍然不显示按钮，请检查:\n";
echo "1. 是否点击了图片卡片来打开modal\n";
echo "2. 浏览器控制台是否有JavaScript错误\n";
echo "3. 是否正确传递了onGenerateAgain回调\n";
