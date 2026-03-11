<?php
/**
 * 测试图片库功能
 */

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Services\ImageGalleryService;

$galleryService = new ImageGalleryService();

echo "=== 测试图片库功能 ===\n\n";

// 测试数据
$testImages = [
    [
        'userId' => 1,
        'username' => 'user1',
        'model' => 'alibaba-wan2.6-t2i',
        'llmModel' => 'gpt-4',
        'prompt' => 'A beautiful sunset over mountains',
        'imageUrl' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        'imageSize' => '1024*1024',
        'imageQuality' => 'high',
        'tags' => 'sunset,nature,landscape'
    ],
    [
        'userId' => 1,
        'username' => 'user1',
        'model' => 'black-forest-labs/flux.2-pro',
        'llmModel' => 'claude-3',
        'prompt' => 'A futuristic city at night',
        'imageUrl' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        'imageSize' => '1024*1024',
        'imageQuality' => 'high',
        'tags' => 'city,night,futuristic'
    ],
    [
        'userId' => 2,
        'username' => 'user2',
        'model' => 'alibaba-qwen-image-2.0-pro',
        'llmModel' => null,
        'prompt' => 'A cute cat playing with yarn',
        'imageUrl' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        'imageSize' => '1024*1024',
        'imageQuality' => 'medium',
        'tags' => 'cat,cute,animal'
    ]
];

// 测试1: 保存图片
echo "测试1: 保存图片\n";
$savedIds = [];
foreach ($testImages as $index => $image) {
    try {
        $id = $galleryService->saveImage(
            userId: $image['userId'],
            username: $image['username'],
            model: $image['model'],
            prompt: $image['prompt'],
            imageUrl: $image['imageUrl'],
            llmModel: $image['llmModel'],
            imageSize: $image['imageSize'],
            imageQuality: $image['imageQuality'],
            tags: $image['tags']
        );
        $savedIds[] = $id;
        echo "  ✅ 图片 " . ($index + 1) . " 保存成功 (ID: {$id})\n";
    } catch (\Exception $e) {
        echo "  ❌ 图片 " . ($index + 1) . " 保存失败: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// 测试2: 获取公开图片库
echo "测试2: 获取公开图片库\n";
try {
    $result = $galleryService->getPublicGallery(1, 10);
    echo "  ✅ 获取成功\n";
    echo "     总数: " . $result['total'] . "\n";
    echo "     页数: " . $result['pages'] . "\n";
    echo "     当前页数据: " . count($result['data']) . "\n";
} catch (\Exception $e) {
    echo "  ❌ 获取失败: " . $e->getMessage() . "\n";
}

echo "\n";

// 测试3: 获取用户的图片库
echo "测试3: 获取用户的图片库\n";
try {
    $result = $galleryService->getUserGallery(1, 1, 10);
    echo "  ✅ 获取成功\n";
    echo "     用户1的图片数: " . $result['total'] . "\n";
    echo "     当前页数据: " . count($result['data']) . "\n";
} catch (\Exception $e) {
    echo "  ❌ 获取失败: " . $e->getMessage() . "\n";
}

echo "\n";

// 测试4: 获取单个图片
if (!empty($savedIds)) {
    echo "测试4: 获取单个图片\n";
    try {
        $image = $galleryService->getImage($savedIds[0]);
        if ($image) {
            echo "  ✅ 获取成功\n";
            echo "     ID: " . $image['id'] . "\n";
            echo "     模型: " . $image['model'] . "\n";
            echo "     大模型: " . ($image['llm_model'] ?? 'N/A') . "\n";
            echo "     提示词: " . substr($image['prompt'], 0, 50) . "...\n";
        } else {
            echo "  ❌ 图片不存在\n";
        }
    } catch (\Exception $e) {
        echo "  ❌ 获取失败: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// 测试5: 增加浏览次数
if (!empty($savedIds)) {
    echo "测试5: 增加浏览次数\n";
    try {
        $success = $galleryService->incrementViews($savedIds[0]);
        if ($success) {
            echo "  ✅ 增加成功\n";
            $image = $galleryService->getImage($savedIds[0]);
            echo "     当前浏览次数: " . $image['views'] . "\n";
        } else {
            echo "  ❌ 增加失败\n";
        }
    } catch (\Exception $e) {
        echo "  ❌ 操作失败: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// 测试6: 增加点赞数
if (!empty($savedIds)) {
    echo "测试6: 增加点赞数\n";
    try {
        $success = $galleryService->incrementLikes($savedIds[0]);
        if ($success) {
            echo "  ✅ 增加成功\n";
            $image = $galleryService->getImage($savedIds[0]);
            echo "     当前点赞数: " . $image['likes'] . "\n";
        } else {
            echo "  ❌ 增加失败\n";
        }
    } catch (\Exception $e) {
        echo "  ❌ 操作失败: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// 测试7: 搜索图片
echo "测试7: 搜索图片\n";
try {
    $result = $galleryService->searchImages('sunset', 1, 10);
    echo "  ✅ 搜索成功\n";
    echo "     关键词: " . $result['keyword'] . "\n";
    echo "     结果数: " . $result['total'] . "\n";
    echo "     当前页数据: " . count($result['data']) . "\n";
} catch (\Exception $e) {
    echo "  ❌ 搜索失败: " . $e->getMessage() . "\n";
}

echo "\n";

// 测试8: 获取模型统计
echo "测试8: 获取模型统计\n";
try {
    $stats = $galleryService->getModelStats();
    echo "  ✅ 获取成功\n";
    echo "     模型数: " . count($stats) . "\n";
    foreach ($stats as $stat) {
        echo "     - " . $stat['model'] . ": " . $stat['count'] . " 张 (" . $stat['users'] . " 个用户)\n";
    }
} catch (\Exception $e) {
    echo "  ❌ 获取失败: " . $e->getMessage() . "\n";
}

echo "\n";

// 测试9: 获取大模型统计
echo "测试9: 获取大模型统计\n";
try {
    $stats = $galleryService->getLLMStats();
    echo "  ✅ 获取成功\n";
    echo "     大模型数: " . count($stats) . "\n";
    foreach ($stats as $stat) {
        echo "     - " . ($stat['llm_model'] ?? 'N/A') . ": " . $stat['count'] . " 张 (" . $stat['users'] . " 个用户)\n";
    }
} catch (\Exception $e) {
    echo "  ❌ 获取失败: " . $e->getMessage() . "\n";
}

echo "\n";

// 测试10: 更新图片
if (!empty($savedIds)) {
    echo "测试10: 更新图片\n";
    try {
        $success = $galleryService->updateImage(
            imageId: $savedIds[0],
            userId: 1,
            description: 'Updated description',
            tags: 'sunset,nature,updated'
        );
        if ($success) {
            echo "  ✅ 更新成功\n";
            $image = $galleryService->getImage($savedIds[0]);
            echo "     描述: " . $image['description'] . "\n";
            echo "     标签: " . $image['tags'] . "\n";
        } else {
            echo "  ❌ 更新失败\n";
        }
    } catch (\Exception $e) {
        echo "  ❌ 操作失败: " . $e->getMessage() . "\n";
    }
}

echo "\n=== 测试完成 ===\n";
