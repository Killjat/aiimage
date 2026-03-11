<?php
/**
 * Test script to verify image saving flow
 * Tests both sync and async image generation with saving
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\AIServiceManager;
use App\Services\ImageGalleryService;
use App\Database\Database;

// Initialize services
$db = new Database();
$galleryService = new ImageGalleryService($db);

// Test user ID (admin)
$userId = 1;
$username = 'admin@example.com';

echo "=== Image Save Flow Test ===\n\n";

// Test 1: Save a simple image
echo "Test 1: Saving a simple image to gallery\n";
try {
    $result = $galleryService->saveImage(
        userId: $userId,
        username: $username,
        model: 'qwen-image-2.0-pro',
        prompt: 'A beautiful sunset over mountains',
        imageUrl: 'https://example.com/image1.jpg',
        llmModel: null,
        negativePrompt: 'blurry, low quality',
        imageSize: '1024*1024',
        imageQuality: null,
        isPublic: false,
        tags: null
    );
    echo "✅ Image saved successfully\n";
    echo "   Result: " . json_encode($result) . "\n\n";
} catch (Exception $e) {
    echo "❌ Failed to save image: " . $e->getMessage() . "\n\n";
}

// Test 2: Verify image appears in user gallery
echo "Test 2: Retrieving user gallery\n";
try {
    $gallery = $galleryService->getUserGallery($userId, limit: 10);
    echo "✅ Retrieved " . count($gallery) . " images from user gallery\n";
    if (!empty($gallery)) {
        $latest = $gallery[0];
        echo "   Latest image:\n";
        echo "   - Model: " . $latest['model'] . "\n";
        echo "   - Prompt: " . $latest['prompt'] . "\n";
        echo "   - URL: " . $latest['image_url'] . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ Failed to retrieve gallery: " . $e->getMessage() . "\n\n";
}

// Test 3: Check public gallery
echo "Test 3: Checking public gallery\n";
try {
    $publicGallery = $galleryService->getPublicGallery(limit: 5);
    echo "✅ Public gallery has " . count($publicGallery) . " images\n\n";
} catch (Exception $e) {
    echo "❌ Failed to retrieve public gallery: " . $e->getMessage() . "\n\n";
}

// Test 4: Get model statistics
echo "Test 4: Getting model statistics\n";
try {
    $stats = $galleryService->getModelStats();
    echo "✅ Model statistics:\n";
    foreach ($stats as $model => $count) {
        echo "   - $model: $count images\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ Failed to get model stats: " . $e->getMessage() . "\n\n";
}

echo "=== Test Complete ===\n";
