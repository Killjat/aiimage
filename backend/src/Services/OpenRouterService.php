<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class OpenRouterService
{
    private Client $client;
    private string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiKey = $_ENV['OPENROUTER_API_KEY'];
        $this->apiUrl = $_ENV['OPENROUTER_API_URL'];
        
        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'timeout' => 60.0,
            'headers' => [
                'User-Agent' => 'OpenRouter-Chat-System/1.0',
                'Accept' => 'application/json',
            ],
        ]);
    }
    
    /**
     * Get app URL for HTTP-Referer header
     */
    private function getAppUrl(): string
    {
        if (!isset($_ENV['APP_URL'])) {
            error_log('警告: APP_URL 环境变量未配置！请在 .env 文件中设置。');
            throw new \Exception('APP_URL 环境变量未配置');
        }
        return $_ENV['APP_URL'];
    }

    /**
     * Send chat message to OpenRouter API
     * 
     * @param string $model Model name (e.g., 'auto', 'openai/gpt-4', 'x-ai/grok-beta')
     * @param array $messages Array of message objects with 'role' and 'content'
     * @return array API response
     * @throws \Exception
     */
    public function chat(string $model, array $messages): array
    {
        try {
            // 直接使用 curl 确保 Accept header 正确发送
            $ch = curl_init($this->apiUrl . '/chat/completions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
                'HTTP-Referer: ' . $this->getAppUrl(),
                'X-Title: OpenRouter Chat System',
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'model' => $model,
                'messages' => $messages,
            ]));
            
            $body = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($httpCode !== 200) {
                error_log('OpenRouter Chat API HTTP Code: ' . $httpCode);
                error_log('OpenRouter Chat API Response: ' . substr($body, 0, 500));
                throw new \Exception('聊天请求失败: HTTP ' . $httpCode);
            }
            
            $data = json_decode($body, true);

            
            if ($data === null) {
                error_log('OpenRouter API 返回内容: ' . $body);
                throw new \Exception('API 返回数据解析失败: ' . json_last_error_msg());
            }
            
            return $data;
            
        } catch (\Exception $e) {
            error_log('OpenRouter API 错误: ' . $e->getMessage());
            throw new \Exception('OpenRouter API 调用失败: ' . $e->getMessage());
        }
    }

    /**
     * Get available models from OpenRouter
     * 
     * @return array
     * @throws \Exception
     */
    public function getModels(): array
    {
        try {
            // 使用原生 curl，Guzzle 在某些情况下不能正确发送 Accept header
            $ch = curl_init($this->apiUrl . '/models');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->apiKey,
                'Accept: application/json',
                'HTTP-Referer: ' . $this->getAppUrl(),
                'X-Title: OpenRouter Chat System',
            ]);
            
            $body = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            // 不需要 curl_close，PHP 8.0+ 会自动清理
            
            if ($httpCode !== 200) {
                error_log('OpenRouter Models API HTTP Code: ' . $httpCode);
                throw new \Exception('获取模型列表失败: HTTP ' . $httpCode);
            }
            
            $data = json_decode($body, true);
            
            if ($data === null || !isset($data['data'])) {
                error_log('OpenRouter Models API 返回内容: ' . substr($body, 0, 500));
                throw new \Exception('获取模型列表失败: 返回数据格式错误');
            }
            
            return $data['data'];
            
        } catch (\Exception $e) {
            error_log('OpenRouter Models API 错误: ' . $e->getMessage());
            throw new \Exception('获取模型列表失败: ' . $e->getMessage());
        }
    }

    /**
     * Generate image using OpenRouter API
     * 使用专门的图片生成模型通过聊天接口生成图片
     * 
     * @param string $model Model name (e.g., 'google/gemini-3.1-flash-image-preview')
     * @param string $prompt Image description
     * @param string|null $baseImage Base64 encoded image for editing (optional)
     * @param string|null $aspectRatio Aspect ratio (e.g., '16:9', '1:1')
     * @param string|null $imageSize Image size (e.g., '1K', '2K', '4K')
     * @return array API response with image_url
     * @throws \Exception
     */
    public function generateImage(
        string $model, 
        string $prompt, 
        ?string $baseImage = null,
        ?string $aspectRatio = null,
        ?string $imageSize = null
    ): array {
        try {
            // 记录使用的模型
            error_log("图片生成请求 - 模型: {$model}, 提示词: " . substr($prompt, 0, 50));
            
            // 使用聊天接口，但专门用于图片生成
            // 关键：需要设置 modalities 参数
            $ch = curl_init($this->apiUrl . '/chat/completions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
                'HTTP-Referer: ' . $this->getAppUrl(),
                'X-Title: OpenRouter Chat System - Image Generation',
            ]);
            
            // 判断模型类型，设置正确的 modalities
            $isGemini = strpos($model, 'google/gemini') !== false;
            $modalities = $isGemini ? ['image', 'text'] : ['image'];
            
            // 构建消息数组
            $messages = [];
            
            // 如果有上传的图片，添加到消息中（用于图片编辑）
            if ($baseImage) {
                $messages[] = [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $baseImage  // base64 data URL
                            ]
                        ],
                        [
                            'type' => 'text',
                            'text' => $prompt
                        ]
                    ]
                ];
            } else {
                // 纯文本生成
                $messages[] = [
                    'role' => 'user',
                    'content' => $prompt
                ];
            }
            
            // 构建请求体
            $requestBody = [
                'model' => $model,
                'messages' => $messages,
                // 关键参数：指定输出模式
                'modalities' => $modalities
            ];
            
            // 添加图片配置（如果提供）
            if ($aspectRatio || $imageSize) {
                $imageConfig = [];
                if ($aspectRatio) {
                    $imageConfig['aspect_ratio'] = $aspectRatio;
                }
                if ($imageSize) {
                    $imageConfig['image_size'] = $imageSize;
                }
                $requestBody['image_config'] = $imageConfig;
            }
            
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
            
            $body = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($httpCode !== 200) {
                error_log('OpenRouter Image Generation HTTP Code: ' . $httpCode);
                error_log('OpenRouter Image Generation Response: ' . substr($body, 0, 500));
                throw new \Exception('图片生成请求失败: HTTP ' . $httpCode);
            }
            
            $data = json_decode($body, true);
            
            if ($data === null) {
                error_log('OpenRouter Image Generation 返回内容: ' . substr($body, 0, 500));
                throw new \Exception('图片生成响应解析失败');
            }
            
            // 检查是否有 images 字段（OpenRouter 返回格式）
            if (isset($data['choices'][0]['message']['images']) && !empty($data['choices'][0]['message']['images'])) {
                // 图片以 base64 data URL 格式返回
                $imageData = $data['choices'][0]['message']['images'][0];
                
                // 处理不同的返回格式
                if (is_array($imageData)) {
                    // 如果是对象格式: {"type": "image_url", "image_url": {"url": "data:..."}}
                    if (isset($imageData['image_url']['url'])) {
                        $imageDataUrl = $imageData['image_url']['url'];
                    } elseif (isset($imageData['url'])) {
                        $imageDataUrl = $imageData['url'];
                    } else {
                        $imageDataUrl = json_encode($imageData);
                    }
                } else {
                    // 如果是字符串格式
                    $imageDataUrl = $imageData;
                }
                
                return [
                    'image_url' => $imageDataUrl,  // base64 data URL
                    'prompt' => $prompt,
                    'model' => $model,
                    'format' => 'base64'
                ];
            }
            
            // 如果没有 images 字段，检查文本内容中是否有图片 URL
            $content = $data['choices'][0]['message']['content'] ?? '';
            if (preg_match('/https?:\/\/[^\s\)]+\.(png|jpg|jpeg|gif|webp)/i', $content, $matches)) {
                return [
                    'image_url' => $matches[0],
                    'prompt' => $prompt,
                    'model' => $model,
                    'format' => 'url'
                ];
            }
            
            // 如果都没有，返回错误
            error_log("模型 {$model} 返回了文本而非图片: " . substr($content, 0, 200));
            throw new \Exception("模型 {$model} 不支持图片生成，返回了文本内容。请尝试使用其他模型（推荐：Nano Banana 2 或 Flux 2 Pro）");
            
        } catch (\Exception $e) {
            error_log('图片生成错误: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate API key
     * 
     * @return bool
     */
    public function validateApiKey(): bool
    {
        try {
            $this->chat('openai/gpt-3.5-turbo', [
                ['role' => 'user', 'content' => 'test']
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
