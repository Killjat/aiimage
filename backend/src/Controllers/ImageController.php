<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\AIServiceManager;
use App\Services\OpenRouterService;
use App\Services\DeepSeekService;
use App\Services\UCloudService;
use App\Services\AuthService;
use App\Services\QuotaService;

class ImageController
{
    private AIServiceManager $aiServiceManager;
    private AuthService $authService;
    private QuotaService $quotaService;

    public function __construct()
    {
        $openRouterService = new OpenRouterService();
        $deepSeekService = new DeepSeekService();
        $ucloudService = new UCloudService();
        $this->aiServiceManager = new AIServiceManager($openRouterService, $deepSeekService, $ucloudService);
        $this->authService = new AuthService();
        $this->quotaService = new QuotaService();
    }

    /**
     * Get user ID or guest IP from request
     * Returns ['type' => 'user'|'guest', 'id' => userId|IP]
     */
    private function getIdentifier(Request $request): array
    {
        try {
            $authHeader = $request->getHeaderLine('Authorization');
            if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
                $payload = $this->authService->verifyToken($token);
                if (isset($payload['user_id'])) {
                    return ['type' => 'user', 'id' => $payload['user_id']];
                }
            }
        } catch (\Exception $e) {
            // Token invalid, fall through to guest
        }

        // 游客模式：使用 IP 地址
        $serverParams = $request->getServerParams();
        $ip = $serverParams['REMOTE_ADDR'] ?? 'unknown';
        return ['type' => 'guest', 'id' => $ip];
    }

    /**
     * 生成图片
     * POST /api/image/generate
     * 
     * 请求体:
     * {
     *   "prompt": "图片描述",
     *   "model": "模型ID（可选）"
     * }
     */
    public function generate(Request $request, Response $response): Response
    {
        try {
            // 获取用户或游客标识
            $identifier = $this->getIdentifier($request);
            $isGuest = ($identifier['type'] === 'guest');
            $id = $identifier['id'];

            // 检查配额
            $hasQuota = $isGuest 
                ? $this->quotaService->hasGuestImageQuota($id)
                : $this->quotaService->hasImageQuota($id);

            if (!$hasQuota) {
                $quota = $isGuest
                    ? $this->quotaService->getGuestImageQuota($id)
                    : $this->quotaService->getImageQuota($id);
                    
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => $isGuest ? '游客配额已用完，请登录获取更多配额' : '图片生成配额已用完',
                    'quota' => $quota
                ]));
                return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            }

            $data = $request->getParsedBody();
            
            // 如果 getParsedBody 为空，尝试手动解析
            if (empty($data)) {
                $body = (string) $request->getBody();
                $data = json_decode($body, true);
            }
            
            // 验证输入
            if (empty($data['prompt'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => '请提供图片描述'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $prompt = trim($data['prompt']);
            $model = $data['model'] ?? 'google/gemini-3.1-flash-image-preview';
            $baseImage = $data['base_image'] ?? null;
            $aspectRatio = $data['aspect_ratio'] ?? null;
            $imageSize = $data['image_size'] ?? null;
            
            // 验证 prompt 长度
            if (strlen($prompt) < 3) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => '图片描述太短，请提供更详细的描述'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // 扣除配额
            $quotaUsed = $isGuest
                ? $this->quotaService->useGuestImageQuota($id)
                : $this->quotaService->useImageQuota($id);

            if (!$quotaUsed) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => '配额扣除失败，请重试'
                ]));
                return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
            }

            try {
                // 调用图片生成服务
                $result = $this->aiServiceManager->generateImage(
                    $model, 
                    $prompt, 
                    $baseImage,
                    $aspectRatio,
                    $imageSize
                );

                // 记录成功的生成（仅对已登录用户）
                if (!$isGuest) {
                    $this->quotaService->recordImageGeneration(
                        $id,
                        $model,
                        $prompt,
                        $result['image_url'],
                        $aspectRatio,
                        $imageSize,
                        'success'
                    );
                }

                // 获取剩余配额
                $quota = $isGuest
                    ? $this->quotaService->getGuestImageQuota($id)
                    : $this->quotaService->getImageQuota($id);

                $response->getBody()->write(json_encode([
                    'success' => true,
                    'image_url' => $result['image_url'],
                    'model' => $result['model'],
                    'prompt' => $result['prompt'],
                    'quota' => $quota
                ]));

                return $response->withHeader('Content-Type', 'application/json');

            } catch (\Exception $e) {
                // 图片生成失败，记录失败但不退还配额（防止滥用）
                if (!$isGuest) {
                    $this->quotaService->recordImageGeneration(
                        $id,
                        $model,
                        $prompt,
                        null,
                        $aspectRatio,
                        $imageSize,
                        'failed',
                        $e->getMessage()
                    );
                }

                throw $e;
            }

        } catch (\Exception $e) {
            error_log('图片生成控制器错误: ' . $e->getMessage());
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * 获取用户配额信息
     * GET /api/image/quota
     */
    public function getQuota(Request $request, Response $response): Response
    {
        try {
            $identifier = $this->getIdentifier($request);
            $isGuest = ($identifier['type'] === 'guest');
            $id = $identifier['id'];

            $quota = $isGuest
                ? $this->quotaService->getGuestImageQuota($id)
                : $this->quotaService->getImageQuota($id);

            $response->getBody()->write(json_encode([
                'success' => true,
                'quota' => $quota,
                'is_guest' => $isGuest
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            error_log('获取配额错误: ' . $e->getMessage());
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * 获取用户图片生成历史
     * GET /api/image/history
     */
    public function getHistory(Request $request, Response $response): Response
    {
        try {
            $userId = $this->getUserIdFromRequest($request);
            if (!$userId) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => '请先登录'
                ]));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }

            $queryParams = $request->getQueryParams();
            $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 20;
            $offset = isset($queryParams['offset']) ? (int)$queryParams['offset'] : 0;

            $history = $this->quotaService->getImageHistory($userId, $limit, $offset);

            $response->getBody()->write(json_encode([
                'success' => true,
                'history' => $history
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            error_log('获取历史记录错误: ' . $e->getMessage());
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * 获取支持图片生成的模型列表
     * GET /api/image/models
     */
    public function getImageModels(Request $request, Response $response): Response
    {
        try {
            $openRouterService = new OpenRouterService();
            $allModels = $openRouterService->getModels();
            
            // 筛选支持图片生成的模型
            $imageModels = array_filter($allModels, function($model) {
                $modelId = strtolower($model['id']);
                return strpos($modelId, 'dall-e') !== false
                    || strpos($modelId, 'stable-diffusion') !== false
                    || strpos($modelId, 'midjourney') !== false
                    || strpos($modelId, 'imagen') !== false
                    || (isset($model['architecture']['modality']) 
                        && strpos($model['architecture']['modality'], 'image') !== false
                        && strpos($model['architecture']['modality'], 'text->image') !== false);
            });

            $response->getBody()->write(json_encode([
                'success' => true,
                'models' => array_values($imageModels)
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            error_log('获取图片模型错误: ' . $e->getMessage());
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
