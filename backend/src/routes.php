<?php

use App\Controllers\ChatController;
use App\Controllers\ModelsController;
use App\Controllers\ImageController;
use App\Controllers\AuthController;
use App\Controllers\WebAnalysisController;
use App\Controllers\NotteController;
use App\Controllers\GalleryController;
use Slim\Routing\RouteCollectorProxy;

// Auth routes (public)
$app->group('/api/auth', function (RouteCollectorProxy $group) {
    $authController = new AuthController();
    
    $group->post('/register', [$authController, 'register']);
    $group->post('/login', [$authController, 'login']);
    $group->post('/logout', [$authController, 'logout']);
    $group->get('/me', [$authController, 'me']);
});

// Models routes
$app->group('/api/models', function (RouteCollectorProxy $group) {
    $modelsController = new ModelsController();
    
    // Get all models (OpenRouter + DeepSeek)
    $group->get('', [$modelsController, 'list']);
    
    // Get DeepSeek models only
    $group->get('/deepseek', [$modelsController, 'listDeepSeek']);
    
    // Get OpenRouter models only
    $group->get('/openrouter', [$modelsController, 'listOpenRouter']);
});

// Chat routes
$app->group('/api/chat', function (RouteCollectorProxy $group) {
    $chatController = new ChatController();
    
    $group->post('/send', [$chatController, 'send']);
});

// Image generation routes
$app->group('/api/image', function (RouteCollectorProxy $group) {
    $imageController = new ImageController();
    
    $group->post('/generate', [$imageController, 'generate']);
    $group->post('/generate/bailian', [$imageController, 'generateBailian']);
    $group->get('/bailian/task/{taskId}', [$imageController, 'getBailianTaskResult']);
    $group->get('/bailian/config', [$imageController, 'getBailianConfig']);
    $group->get('/models', [$imageController, 'getImageModels']);
    $group->get('/quota', [$imageController, 'getQuota']);
    $group->get('/history', [$imageController, 'getHistory']);
    $group->post('/save', [$imageController, 'saveImage']);
});

// Website analysis routes
$app->group('/api/analyze', function (RouteCollectorProxy $group) {
    $webAnalysisController = new WebAnalysisController();
    
    $group->post('/website', [$webAnalysisController, 'analyzeWebsite']);
    $group->get('/reasoning-models', [$webAnalysisController, 'getReasoningModels']);
});

// Notte automation routes
$app->group('/api/notte', function (RouteCollectorProxy $group) {
    $notteController = new NotteController();
    
    // 基础功能
    $group->post('/scrape', [$notteController, 'scrape']);
    $group->post('/scrape/structured', [$notteController, 'scrapeStructured']);
    $group->post('/agent/run', [$notteController, 'runAgent']);
    
    // 预定义监控任务
    $group->get('/monitor/tasks', [$notteController, 'getMonitorTasks']);
    $group->get('/monitor/anthropic/news', [$notteController, 'monitorAnthropicNews']);
    $group->get('/monitor/anthropic/pricing', [$notteController, 'monitorAnthropicPricing']);
    $group->get('/monitor/clawhub/skills', [$notteController, 'monitorClawHubSkills']);
});

// Image gallery routes
$app->group('/api/gallery', function (RouteCollectorProxy $group) {
    $galleryController = new GalleryController();
    
    $group->get('/public', [$galleryController, 'getPublicGallery']);
    $group->get('/user/{userId}', [$galleryController, 'getUserGallery']);
    $group->get('/image/{imageId}', [$galleryController, 'getImage']);
    $group->get('/search', [$galleryController, 'searchImages']);
    $group->get('/suggestions', [$galleryController, 'getSearchSuggestions']);
    $group->post('/image/{imageId}/like', [$galleryController, 'likeImage']);
    $group->get('/stats/models', [$galleryController, 'getModelStats']);
    $group->get('/stats/llm', [$galleryController, 'getLLMStats']);
});
