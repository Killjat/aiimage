<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\AIServiceManager;
use App\Services\OpenRouterService;
use App\Services\DeepSeekService;
use App\Services\AliBailianService;
use App\Services\AuthService;
use App\Services\QuotaService;
use App\Services\ImageGalleryService;

class ImageController
{
    private AIServiceManager $aiServiceManager;
    private AliBailianService $aliBailianService;
    private AuthService $authService;
    private QuotaService $quotaService;
    private ImageGalleryService $galleryService;

    public function __construct()
    {
        $openRouterService = new OpenRouterService();
        $deepSeekService = new DeepSeekService();
        $aliBailianService = new AliBailianService();
        $this->aiServiceManager = new AIServiceManager($openRouterService, $deepSeekService, $aliBailianService);
        $this->aliBailianService = $aliBailianService;
        $this->authService = new AuthService();
        $this->quotaService = new QuotaService();
        $this->galleryService = new ImageGalleryService();
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
                // 判断是否是阿里模型
                $isAlibaba = strpos($model, 'alibaba-') === 0;
                
                if ($isAlibaba) {
                    // 阿里模型：移除前缀并调用阿里服务
                    $alibabaModel = substr($model, 8); // 移除 'alibaba-' 前缀
                    $size = $data['size'] ?? '1024*1024';
                    $negativePrompt = $data['negative_prompt'] ?? null;
                    $style = $data['style'] ?? '<auto>';
                    
                    $result = $this->aliBailianService->generateImage(
                        $prompt,
                        $alibabaModel,
                        $negativePrompt,
                        $style,
                        $size,
                        1
                    );
                    
                    if ($result['success']) {
                        // 处理阿里的响应格式
                        if (($result['status'] ?? null) === 'completed' && isset($result['images'])) {
                            $imageUrl = $result['images'][0] ?? null;
                        } else {
                            $imageUrl = $result['task_id'] ?? null;
                        }
                        
                        $result = [
                            'image_url' => $imageUrl,
                            'model' => $model,
                            'prompt' => $prompt
                        ];
                    } else {
                        throw new \Exception($result['message'] ?? '阿里生图失败');
                    }
                } else {
                    // OpenRouter 模型：调用 AIServiceManager
                    $result = $this->aiServiceManager->generateImage(
                        $model, 
                        $prompt, 
                        $baseImage,
                        $aspectRatio,
                        $imageSize
                    );
                }

                // 记录成功的生成（仅对已登录用户）
                if (!$isGuest) {
                    // 确保 image_url 是字符串
                    $imageUrl = is_array($result['image_url']) ? json_encode($result['image_url']) : $result['image_url'];
                    $this->quotaService->recordImageGeneration(
                        $id,
                        $model,
                        $prompt,
                        $imageUrl,
                        $aspectRatio,
                        $imageSize,
                        'success'
                    );
                }

                // 获取剩余配额
                $quota = $isGuest
                    ? $this->quotaService->getGuestImageQuota($id)
                    : $this->quotaService->getImageQuota($id);

                // 保存到图片库（仅对已登录用户）
                if (!$isGuest) {
                    try {
                        $user = $this->authService->getUserById($id);
                        $username = $user['username'] ?? 'Unknown';
                        
                        $this->galleryService->saveImage(
                            userId: $id,
                            username: $username,
                            model: $model,
                            prompt: $prompt,
                            imageUrl: $result['image_url'],
                            llmModel: null,  // 可以根据需要添加大模型信息
                            negativePrompt: null,
                            imageSize: $imageSize,
                            imageQuality: null,
                            isPublic: true,  // 默认公开
                            tags: null
                        );
                    } catch (\Exception $e) {
                        error_log('Failed to save image to gallery: ' . $e->getMessage());
                        // 不影响主流程
                    }
                }

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
     * 阿里百练图片生成
     * POST /api/image/generate/bailian
     */
    public function generateBailian(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            $identifier = $this->getIdentifier($request);
            $isGuest = $identifier['type'] === 'guest';
            $id = $identifier['id'];

            // 检查配额
            $quota = $isGuest
                ? $this->quotaService->getGuestImageQuota($id)
                : $this->quotaService->getImageQuota($id);

            if ($quota['remaining'] <= 0) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => $isGuest ? '游客每日图片生成次数已用完' : '图片生成配额已用完'
                ]));
                return $response->withStatus(429)->withHeader('Content-Type', 'application/json');
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
            $model = $data['model'] ?? 'wanx-v1';
            $negativePrompt = $data['negative_prompt'] ?? null;
            $style = $data['style'] ?? '<auto>';
            $size = $data['size'] ?? '1024*1024';
            $refImage = $data['ref_image'] ?? null;
            $refStrength = $data['ref_strength'] ?? 1.0;
            $refMode = $data['ref_mode'] ?? 'repaint';
            
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
                // 调用阿里百练图片生成服务
                $result = $this->aliBailianService->generateImage(
                    $prompt,
                    $model,
                    $negativePrompt,
                    $style,
                    $size,
                    1,
                    $refImage,
                    $refStrength,
                    $refMode
                );

                if ($result['success']) {
                    // 获取剩余配额
                    $quota = $isGuest
                        ? $this->quotaService->getGuestImageQuota($id)
                        : $this->quotaService->getImageQuota($id);

                    // 检查是同步还是异步响应
                    if (($result['status'] ?? null) === 'completed' && isset($result['images'])) {
                        // 同步响应 - 直接返回图片
                        if (!$isGuest) {
                            $this->quotaService->recordImageGeneration(
                                $id,
                                'alibaba/' . $model,
                                $prompt,
                                null,
                                $size,
                                null,
                                'completed'
                            );
                        }

                        $response->getBody()->write(json_encode([
                            'success' => true,
                            'status' => 'completed',
                            'images' => $result['images'],
                            'message' => $result['message'],
                            'quota' => $quota
                        ]));
                    } else {
                        // 异步响应 - 返回任务ID
                        if (!$isGuest) {
                            $this->quotaService->recordImageGeneration(
                                $id,
                                'alibaba/' . $model,
                                $prompt,
                                $result['task_id'] ?? null,
                                $size,
                                null,
                                'processing'
                            );
                        }

                        $response->getBody()->write(json_encode([
                            'success' => true,
                            'task_id' => $result['task_id'] ?? null,
                            'status' => 'processing',
                            'message' => $result['message'],
                            'quota' => $quota
                        ]));
                    }

                    return $response->withHeader('Content-Type', 'application/json');
                } else {
                    throw new \Exception($result['message'] ?? '生成失败');
                }

            } catch (\Exception $e) {
                // 图片生成失败，记录失败但不退还配额（防止滥用）
                if (!$isGuest) {
                    $this->quotaService->recordImageGeneration(
                        $id,
                        'alibaba/' . $model,
                        $prompt,
                        null,
                        $size,
                        null,
                        'failed',
                        $e->getMessage()
                    );
                }

                throw $e;
            }

        } catch (\Exception $e) {
            error_log('阿里百练图片生成错误: ' . $e->getMessage());
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * 查询阿里百练任务结果
     * GET /api/image/bailian/task/{taskId}
     */
    public function getBailianTaskResult(Request $request, Response $response, array $args): Response
    {
        try {
            $taskId = $args['taskId'] ?? '';
            
            if (empty($taskId)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => '缺少任务ID'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $result = $this->aliBailianService->getTaskResult($taskId);

            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            error_log('查询阿里百练任务结果错误: ' . $e->getMessage());
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * 获取阿里百练支持的配置
     * GET /api/image/bailian/config
     */
    public function getBailianConfig(Request $request, Response $response): Response
    {
        try {
            $response->getBody()->write(json_encode([
                'success' => true,
                'config' => [
                    'models' => $this->aliBailianService->getSupportedModels(),
                    'sizes' => $this->aliBailianService->getSupportedSizes(),
                    'styles' => $this->aliBailianService->getSupportedStyles(),
                    'provider' => 'alibaba_bailian'
                ]
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            error_log('获取阿里百练配置错误: ' . $e->getMessage());
            
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
            $models = [];
            
            // 获取阿里巴巴图片生成模型
            try {
                $aliBailianModels = $this->aiServiceManager->getAliBailianImageModels();
                foreach ($aliBailianModels as $model) {
                    $models[] = $model;
                }
            } catch (\Exception $e) {
                error_log('获取阿里巴巴图片模型失败: ' . $e->getMessage());
            }
            
            // 硬编码 OpenRouter 的两个生图模型
            $openRouterImageModels = [
                [
                    'id' => 'google/gemini-3.1-flash-image-preview',
                    'name' => 'Gemini 3.1 Flash Image',
                    'provider' => 'openrouter',
                    'description' => 'Google最新图片生成模型，质量优秀'
                ],
                [
                    'id' => 'openai/gpt-5-image-mini',
                    'name' => 'GPT-5 Image Mini',
                    'provider' => 'openrouter',
                    'description' => 'OpenAI GPT-5图片生成，性价比版本'
                ]
            ];
            
            foreach ($openRouterImageModels as $model) {
                $models[] = $model;
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'models' => $models,
                'count' => count($models)
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
