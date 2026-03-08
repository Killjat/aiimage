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
