<?php

namespace App\Controllers;

use App\Services\AIServiceManager;
use App\Services\OpenRouterService;
use App\Services\DeepSeekService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ModelsController
{
    private AIServiceManager $aiServiceManager;

    public function __construct()
    {
        $openRouterService = new OpenRouterService();
        $deepSeekService = new DeepSeekService();
        $aliBailianService = new \App\Services\AliBailianService();
        $this->aiServiceManager = new AIServiceManager($openRouterService, $deepSeekService, $aliBailianService);
    }

    /**
     * Get all available models (OpenRouter + DeepSeek)
     * GET /api/models
     * GET /api/models?chat_only=true  (只返回适合聊天的模型)
     */
    public function list(Request $request, Response $response): Response
    {
        try {
            // 检查是否只需要聊天模型
            $queryParams = $request->getQueryParams();
            $chatOnly = isset($queryParams['chat_only']) && $queryParams['chat_only'] === 'true';
            
            $models = $this->aiServiceManager->getAllModels($chatOnly);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'models' => $models,
                'count' => count($models),
                'chat_only' => $chatOnly
            ]));

            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get DeepSeek models only
     * GET /api/models/deepseek
     */
    public function listDeepSeek(Request $request, Response $response): Response
    {
        try {
            $models = $this->aiServiceManager->getDeepSeekModels();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'models' => $models,
                'count' => count($models),
                'provider' => 'deepseek'
            ]));

            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get OpenRouter models only
     * GET /api/models/openrouter
     */
    public function listOpenRouter(Request $request, Response $response): Response
    {
        try {
            $models = $this->aiServiceManager->getOpenRouterModels();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'models' => $models,
                'count' => count($models),
                'provider' => 'openrouter'
            ]));

            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
    

}
