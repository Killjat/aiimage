<?php

use App\Controllers\ChatController;
use App\Controllers\ModelsController;
use App\Controllers\ImageController;
use Slim\Routing\RouteCollectorProxy;

// Models routes
$app->get('/api/models', function ($request, $response) {
    $controller = new ModelsController();
    return $controller->list($request, $response);
});

// Chat routes
$app->group('/api/chat', function (RouteCollectorProxy $group) {
    $chatController = new ChatController();
    
    $group->post('/send', [$chatController, 'send']);
});

// Image generation routes
$app->group('/api/image', function (RouteCollectorProxy $group) {
    $openRouterService = new \App\Services\OpenRouterService();
    $imageController = new ImageController($openRouterService);
    
    $group->post('/generate', [$imageController, 'generate']);
    $group->get('/models', [$imageController, 'getImageModels']);
});
