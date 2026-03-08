<?php

namespace App\Controllers;

use App\Services\AIServiceManager;
use App\Services\OpenRouterService;
use App\Services\DeepSeekService;
use App\Services\UCloudService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ModelsController
{
    private AIServiceManager $aiServiceManager;

    public function __construct()
    {
        $openRouterService = new OpenRouterService();
        $deepSeekService = new DeepSeekService();
        $ucloudService = new UCloudService();
        $this->aiServiceManager = new AIServiceManager($openRouterService, $deepSeekService, $ucloudService);
    }

    /**
     * Get all available models (OpenRouter + DeepSeek)
     * GET /api/models
     */
    public function list(Request $request, Response $response): Response
    {
        try {
            $models = $this->aiServiceManager->getAllModels();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'models' => $models,
                'count' => count($models)
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
    
    /**
     * Get UCloud models only
     * GET /api/models/ucloud
     */
    public function listUCloud(Request $request, Response $response): Response
    {
        try {
            $models = $this->aiServiceManager->getUCloudModels();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'models' => $models,
                'count' => count($models),
                'provider' => 'ucloud'
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
