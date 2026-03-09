<?php

use App\Controllers\ChatController;
use App\Controllers\ModelsController;
use App\Controllers\ImageController;
use App\Controllers\AuthController;
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
    $group->get('/models', [$imageController, 'getImageModels']);
    $group->get('/quota', [$imageController, 'getQuota']);
    $group->get('/history', [$imageController, 'getHistory']);
});
