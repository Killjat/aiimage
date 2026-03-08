<?php

namespace App\Services;

class UCloudService
{
    private string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiKey = $_ENV['UCLOUD_API_KEY'] ?? '';
        // UCloud UModelVerse 提供兼容 OpenAI 的 API 端点
        $this->apiUrl = $_ENV['UCLOUD_API_URL'] ?? 'https://api.modelverse.cn/v1';
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
     * Send chat message to UCloud AI API
     * 
     * @param string $model Model name (e.g., 'gpt-3.5-turbo', 'gpt-4')
     * @param array $messages Array of message objects with 'role' and 'content'
     * @return array API response
     * @throws \Exception
     */
    public function chat(string $model, array $messages): array
    {
        try {
            if (empty($this->apiKey)) {
                throw new \Exception('UCloud API Key 未配置');
            }

            $ch = curl_init($this->apiUrl . '/chat/completions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'model' => $model,
                'messages' => $messages,
                'stream' => false,
            ]));
            
            $body = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($httpCode !== 200) {
                error_log('UCloud Chat API HTTP Code: ' . $httpCode);
                error_log('UCloud Chat API Response: ' . substr($body, 0, 500));
                throw new \Exception('UCloud 聊天请求失败: HTTP ' . $httpCode);
            }
            
            $data = json_decode($body, true);
            
            if ($data === null) {
                error_log('UCloud API 返回内容: ' . $body);
                throw new \Exception('UCloud API 返回数据解析失败: ' . json_last_error_msg());
            }
            
            return $data;
            
        } catch (\Exception $e) {
            error_log('UCloud API 错误: ' . $e->getMessage());
            throw new \Exception('UCloud API 调用失败: ' . $e->getMessage());
        }
    }

    /**
     * Generate image using UCloud AI API
     * 
     * @param string $model Model name (e.g., 'dall-e-3')
     * @param string $prompt Image description
     * @param string|null $size Image size (e.g., '1024x1024')
     * @param string|null $quality Image quality (e.g., 'standard', 'hd')
     * @return array API response
     * @throws \Exception
     */
    public function generateImage(
        string $model,
        string $prompt,
        ?string $size = '1024x1024',
        ?string $quality = 'standard'
    ): array {
        try {
            if (empty($this->apiKey)) {
                throw new \Exception('UCloud API Key 未配置');
            }

            $ch = curl_init($this->apiUrl . '/images/generations');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ]);
            
            $requestBody = [
                'model' => $model,
                'prompt' => $prompt,
                'n' => 1,
                'size' => $size,
            ];
            
            if ($quality) {
                $requestBody['quality'] = $quality;
            }
            
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
            
            $body = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($httpCode !== 200) {
                error_log('UCloud Image Generation HTTP Code: ' . $httpCode);
                error_log('UCloud Image Generation Response: ' . substr($body, 0, 500));
                throw new \Exception('UCloud 图片生成请求失败: HTTP ' . $httpCode);
            }
            
            $data = json_decode($body, true);
            
            if ($data === null) {
                error_log('UCloud Image Generation 返回内容: ' . substr($body, 0, 500));
                throw new \Exception('UCloud 图片生成响应解析失败');
            }
            
            // 提取图片 URL
            if (isset($data['data'][0]['url'])) {
                return [
                    'image_url' => $data['data'][0]['url'],
                    'prompt' => $prompt,
                    'model' => $model,
                    'format' => 'url'
                ];
            } elseif (isset($data['data'][0]['b64_json'])) {
                return [
                    'image_url' => 'data:image/png;base64,' . $data['data'][0]['b64_json'],
                    'prompt' => $prompt,
                    'model' => $model,
                    'format' => 'base64'
                ];
            }
            
            throw new \Exception('UCloud 图片生成失败：未返回图片数据');
            
        } catch (\Exception $e) {
            error_log('UCloud 图片生成错误: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get available models from UCloud
     * 
     * @return array
     * @throws \Exception
     */
    public function getModels(): array
    {
        try {
            if (empty($this->apiKey)) {
                throw new \Exception('UCloud API Key 未配置');
            }

            $ch = curl_init($this->apiUrl . '/models');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->apiKey,
                'Accept: application/json',
            ]);
            
            $body = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($httpCode !== 200) {
                error_log('UCloud Models API HTTP Code: ' . $httpCode);
                // 如果 API 不支持获取模型列表，返回默认模型
                return $this->getDefaultModels();
            }
            
            $data = json_decode($body, true);
            
            if ($data === null || !isset($data['data'])) {
                // 返回默认模型列表
                return $this->getDefaultModels();
            }
            
            return $data['data'];
            
        } catch (\Exception $e) {
            error_log('UCloud Models API 错误: ' . $e->getMessage());
            // 返回默认模型列表
            return $this->getDefaultModels();
        }
    }

    /**
     * Get default UCloud UModelVerse models
     * UModelVerse 主要提供聊天模型，不支持图片生成
     * 
     * @return array
     */
    private function getDefaultModels(): array
    {
        return [
            // 聊天模型 - 从实际 API 返回的热门模型
            [
                'id' => 'gpt-5.3-chat-latest',
                'name' => 'GPT-5.3 Chat',
                'description' => 'UCloud UModelVerse - GPT-5.3 最新聊天模型',
                'context_length' => 128000,
                'type' => 'chat',
            ],
            [
                'id' => 'claude-sonnet-4-6',
                'name' => 'Claude Sonnet 4.6',
                'description' => 'UCloud UModelVerse - Claude Sonnet 4.6',
                'context_length' => 200000,
                'type' => 'chat',
            ],
            [
                'id' => 'gemini-3.1-pro-preview',
                'name' => 'Gemini 3.1 Pro',
                'description' => 'UCloud UModelVerse - Google Gemini 3.1 Pro',
                'context_length' => 1000000,
                'type' => 'chat',
            ],
            [
                'id' => 'qwen3.5-plus',
                'name' => 'Qwen 3.5 Plus',
                'description' => 'UCloud UModelVerse - 通义千问 3.5 Plus',
                'context_length' => 128000,
                'type' => 'chat',
            ],
            [
                'id' => 'doubao-1-5-pro-32k-character-250715',
                'name' => 'Doubao 1.5 Pro',
                'description' => 'UCloud UModelVerse - 豆包 1.5 Pro 32K',
                'context_length' => 32000,
                'type' => 'chat',
            ],
            [
                'id' => 'MiniMax-M2.5',
                'name' => 'MiniMax M2.5',
                'description' => 'UCloud UModelVerse - MiniMax M2.5',
                'context_length' => 128000,
                'type' => 'chat',
            ],
            // 视觉理解模型（可以理解图片，但不能生成图片）
            [
                'id' => 'gemini-3-pro-image-preview',
                'name' => 'Gemini 3 Pro Vision',
                'description' => 'UCloud UModelVerse - Gemini 3 Pro 视觉理解模型',
                'context_length' => 1000000,
                'type' => 'chat',
                'supports_vision' => true,
            ],
            [
                'id' => 'ByteDance/doubao-1.5-thinking-vision-pro',
                'name' => 'Doubao 1.5 Vision Pro',
                'description' => 'UCloud UModelVerse - 豆包 1.5 视觉理解模型',
                'context_length' => 32000,
                'type' => 'chat',
                'supports_vision' => true,
            ],
        ];
    }

    /**
     * Validate API key
     * 
     * @return bool
     */
    public function validateApiKey(): bool
    {
        try {
            if (empty($this->apiKey)) {
                return false;
            }
            
            $this->chat('gpt-3.5-turbo', [
                ['role' => 'user', 'content' => 'test']
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
