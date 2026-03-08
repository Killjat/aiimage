<?php

namespace App\Controllers;

use App\Services\OpenRouterService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ModelsController
{
    private OpenRouterService $openRouterService;

    public function __construct()
    {
        $this->openRouterService = new OpenRouterService();
    }

    /**
     * Get available models from OpenRouter
     * GET /api/models
     */
    public function list(Request $request, Response $response): Response
    {
        try {
            $models = $this->openRouterService->getModels();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'models' => $models
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
