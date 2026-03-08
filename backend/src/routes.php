<?php

use App\Controllers\ChatController;
use App\Controllers\ModelsController;
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
