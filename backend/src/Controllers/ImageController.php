<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\OpenRouterService;

class ImageController
{
    private OpenRouterService $openRouterService;

    public function __construct(OpenRouterService $openRouterService)
    {
        $this->openRouterService = $openRouterService;
    }

    /**
     * 生成图片
     * POST /api/image/generate
     * 
     * 请求体:
     * {
     *   "prompt": "图片描述",
     *   "model": "模型ID（可选，默认使用图片生成模型）"
     * }
     */
    /**
     * 生成图片
     * POST /api/image/generate
     * 
     * 请求体:
     * {
     *   "prompt": "图片描述",
     *   "model": "模型ID（可选，默认使用 DALL-E 3）"
     * }
     * 
     * 注意：此接口专门用于图片生成，与聊天接口分离
     */
    public function generate(Request $request, Response $response): Response
    {
        try {
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
            $baseImage = $data['base_image'] ?? null;  // 上传的图片（base64）
            $aspectRatio = $data['aspect_ratio'] ?? null;  // 宽高比
            $imageSize = $data['image_size'] ?? null;  // 分辨率
            
            // 验证 prompt 长度
            if (strlen($prompt) < 3) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => '图片描述太短，请提供更详细的描述'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // 调用图片生成服务
            $result = $this->openRouterService->generateImage(
                $model, 
                $prompt, 
                $baseImage,
                $aspectRatio,
                $imageSize
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'image_url' => $result['image_url'],
                'model' => $result['model'],
                'prompt' => $result['prompt']
            ]));

            return $response->withHeader('Content-Type', 'application/json');

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
     * 获取支持图片生成的模型列表
     * GET /api/image/models
     */
    public function getImageModels(Request $request, Response $response): Response
    {
        try {
            // 获取所有模型
            $allModels = $this->openRouterService->getModels();
            
            // 筛选支持图片生成的模型
            $imageModels = array_filter($allModels, function($model) {
                // 检查模型是否支持图片生成
                // 通常图片生成模型的 ID 包含 dall-e, stable-diffusion, midjourney 等
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
