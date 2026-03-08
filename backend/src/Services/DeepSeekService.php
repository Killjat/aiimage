<?php

namespace App\Services;

class DeepSeekService
{
    private string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiKey = $_ENV['DEEPSEEK_API_KEY'] ?? '';
        $this->apiUrl = $_ENV['DEEPSEEK_API_URL'] ?? 'https://api.deepseek.com/v1';
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
     * Send chat message to DeepSeek API
     * 
     * @param string $model Model name (e.g., 'deepseek-chat', 'deepseek-reasoner')
     * @param array $messages Array of message objects with 'role' and 'content'
     * @return array API response
     * @throws \Exception
     */
    public function chat(string $model, array $messages): array
    {
        try {
            if (empty($this->apiKey)) {
                throw new \Exception('DeepSeek API Key 未配置');
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
                error_log('DeepSeek Chat API HTTP Code: ' . $httpCode);
                error_log('DeepSeek Chat API Response: ' . substr($body, 0, 500));
                throw new \Exception('DeepSeek 聊天请求失败: HTTP ' . $httpCode);
            }
            
            $data = json_decode($body, true);
            
            if ($data === null) {
                error_log('DeepSeek API 返回内容: ' . $body);
                throw new \Exception('DeepSeek API 返回数据解析失败: ' . json_last_error_msg());
            }
            
            return $data;
            
        } catch (\Exception $e) {
            error_log('DeepSeek API 错误: ' . $e->getMessage());
            throw new \Exception('DeepSeek API 调用失败: ' . $e->getMessage());
        }
    }

    /**
     * Get available models from DeepSeek
     * 
     * @return array
     * @throws \Exception
     */
    public function getModels(): array
    {
        try {
            if (empty($this->apiKey)) {
                throw new \Exception('DeepSeek API Key 未配置');
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
                error_log('DeepSeek Models API HTTP Code: ' . $httpCode);
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
            error_log('DeepSeek Models API 错误: ' . $e->getMessage());
            // 返回默认模型列表
            return $this->getDefaultModels();
        }
    }

    /**
     * Get default DeepSeek models
     * 
     * @return array
     */
    private function getDefaultModels(): array
    {
        return [
            [
                'id' => 'deepseek-chat',
                'name' => 'DeepSeek Chat',
                'description' => 'DeepSeek 聊天模型，性价比极高',
                'pricing' => [
                    'prompt' => '0.00000014',  // $0.14 per 1M tokens
                    'completion' => '0.00000028',  // $0.28 per 1M tokens
                ],
                'context_length' => 64000,
            ],
            [
                'id' => 'deepseek-reasoner',
                'name' => 'DeepSeek Reasoner',
                'description' => 'DeepSeek 推理模型，适合复杂问题',
                'pricing' => [
                    'prompt' => '0.00000055',  // $0.55 per 1M tokens
                    'completion' => '0.0000022',  // $2.19 per 1M tokens
                ],
                'context_length' => 64000,
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
            
            $this->chat('deepseek-chat', [
                ['role' => 'user', 'content' => 'test']
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
