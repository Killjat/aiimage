<?php
/**
 * Test complete image generation and saving flow
 * Simulates both sync and async scenarios
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Database\Database;
use App\Services\ImageGalleryService;

$db = new Database();
$galleryService = new ImageGalleryService($db);

echo "=== Complete Image Save Flow Test ===\n\n";

// Test data
$userId = 1;
$username = 'admin@example.com';
$testImages = [
    [
        'model' => 'qwen-image-2.0-pro',
        'prompt' => 'A serene mountain landscape with snow peaks',
        'imageUrl' => 'https://example.com/mountain.jpg',
        'size' => '1024*1024',
        'negativePrompt' => 'blurry, low quality'
    ],
    [
        'model' => 'wan2.5-t2i-preview',
        'prompt' => 'A futuristic city with flying cars',
        'imageUrl' => 'https://example.com/city.jpg',
        'size' => '1280*1280',
        'negativePrompt' => 'dark, gloomy'
    ],
    [
        'model' => 'black-forest-labs/flux.2-pro',
        'prompt' => 'A beautiful sunset over the ocean',
        'imageUrl' => 'https://example.com/sunset.jpg',
        'size' => '1344*768',
        'negativePrompt' => null
    ]
];

// Test 1: Save multiple images
echo "Test 1: Saving multiple images\n";
$savedIds = [];
foreach ($testImages as $index => $image) {
    try {
        $id = $galleryService->saveImage(
            userId: $userId,
            username: $username,
            model: $image['model'],
            prompt: $image['prompt'],
            imageUrl: $image['imageUrl'],
            llmModel: null,
            negativePrompt: $image['negativePrompt'],
            imageSize: $image['size'],
            imageQuality: null,
            isPublic: false,
            tags: null
        );
        $savedIds[] = $id;
        echo "  ✅ Image " . ($index + 1) . " saved (ID: $id)\n";
    } catch (Exception $e) {
        echo "  ❌ Failed to save image " . ($index + 1) . ": " . $e->getMessage() . "\n";
    }
}
echo "\n";

// Test 2: Verify images in user gallery
echo "Test 2: Verifying images in user gallery\n";
try {
    $userGallery = $galleryService->getUserGallery($userId, limit: 100);
    echo "  ✅ User gallery contains " . count($userGallery) . " images\n";
    
    // Check if our saved images are there
    $foundCount = 0;
    foreach ($userGallery as $img) {
        if (in_array($img['id'], $savedIds)) {
            $foundCount++;
        }
    }
    echo "  ✅ Found " . $foundCount . " of " . count($savedIds) . " saved images\n\n";
} catch (Exception $e) {
    echo "  ❌ Failed to retrieve user gallery: " . $e->getMessage() . "\n\n";
}

// Test 3: Get model statistics
echo "Test 3: Getting model statistics\n";
try {
    $stats = $galleryService->getModelStats();
    echo "  ✅ Model statistics:\n";
    
    // Count models in our test images
    $testModels = array_unique(array_column($testImages, 'model'));
    foreach ($testModels as $model) {
        $count = 0;
        foreach ($stats as $stat) {
            if ($stat['model'] === $model) {
                $count = $stat['count'];
                break;
            }
        }
        echo "    - $model: $count images\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "  ❌ Failed to get model stats: " . $e->getMessage() . "\n\n";
}

// Test 4: Search functionality
echo "Test 4: Testing search functionality\n";
try {
    $searchResults = $galleryService->searchImages('mountain', limit: 10);
    echo "  ✅ Search for 'mountain' found " . count($searchResults) . " results\n";
    
    $searchResults = $galleryService->searchImages('city', limit: 10);
    echo "  ✅ Search for 'city' found " . count($searchResults) . " results\n\n";
} catch (Exception $e) {
    echo "  ❌ Failed to search: " . $e->getMessage() . "\n\n";
}

// Test 5: Get search suggestions
echo "Test 5: Getting search suggestions\n";
try {
    $suggestions = $galleryService->getSearchSuggestions(limit: 10);
    echo "  ✅ Got " . count($suggestions) . " search suggestions\n";
    if (!empty($suggestions)) {
        echo "    Sample suggestions:\n";
        foreach (array_slice($suggestions, 0, 3) as $suggestion) {
            echo "      - " . $suggestion['keyword'] . " (" . $suggestion['count'] . " images)\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "  ❌ Failed to get suggestions: " . $e->getMessage() . "\n\n";
}

// Test 6: Verify database integrity
echo "Test 6: Verifying database integrity\n";
try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM image_gallery WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    echo "  ✅ User has " . $result['total'] . " total images in gallery\n\n";
} catch (Exception $e) {
    echo "  ❌ Failed to verify database: " . $e->getMessage() . "\n\n";
}

echo "=== Test Complete ===\n";
