<?php

namespace App\Controllers;

use App\Services\OpenRouterService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ChatController
{
    private OpenRouterService $openRouterService;

    public function __construct()
    {
        $this->openRouterService = new OpenRouterService();
    }

    /**
     * Send chat message
     * POST /api/chat/send
     * 
     * Request body:
     * {
     *   "model": "grok-beta",
     *   "messages": [
     *     {"role": "user", "content": "Hello"}
     *   ]
     * }
     */
    public function send(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            // Validate input
            if (!isset($data['model']) || !isset($data['messages'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => '缺少必需参数: model 和 messages'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }

            // Call OpenRouter API
            $result = $this->openRouterService->chat(
                $data['model'],
                $data['messages']
            );

            // Extract assistant message
            $assistantMessage = $result['choices'][0]['message']['content'] ?? '';

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => $assistantMessage,
                'model' => $data['model'],
                'usage' => $result['usage'] ?? null
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
