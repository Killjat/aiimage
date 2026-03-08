<?php

use App\Controllers\ChatController;
use App\Controllers\ModelsController;
use App\Controllers\ImageController;
use Slim\Routing\RouteCollectorProxy;

// Models routes
$app->group('/api/models', function (RouteCollectorProxy $group) {
    $modelsController = new ModelsController();
    
    // Get all models (OpenRouter + DeepSeek + UCloud)
    $group->get('', [$modelsController, 'list']);
    
    // Get DeepSeek models only
    $group->get('/deepseek', [$modelsController, 'listDeepSeek']);
    
    // Get OpenRouter models only
    $group->get('/openrouter', [$modelsController, 'listOpenRouter']);
    
    // Get UCloud models only
    $group->get('/ucloud', [$modelsController, 'listUCloud']);
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
});
