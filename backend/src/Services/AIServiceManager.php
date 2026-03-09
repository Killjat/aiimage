<?php

namespace App\Services;

class AIServiceManager
{
    private OpenRouterService $openRouterService;
    private DeepSeekService $deepSeekService;

    public function __construct(
        OpenRouterService $openRouterService,
        DeepSeekService $deepSeekService
    ) {
        $this->openRouterService = $openRouterService;
        $this->deepSeekService = $deepSeekService;
    }

    /**
     * 根据模型 ID 判断使用哪个服务
     * 
     * @param string $model
     * @return string 'openrouter' 或 'deepseek'
     */
    private function getServiceProvider(string $model): string
    {
        // DeepSeek 模型以 'deepseek-' 或 'deepseek/' 开头
        if (strpos($model, 'deepseek-') === 0 || strpos($model, 'deepseek/') === 0) {
            return 'deepseek';
        }
        
        return 'openrouter';
    }

    /**
     * 发送聊天消息（自动选择服务）
     * 
     * @param string $model
     * @param array $messages
     * @return array
     * @throws \Exception
     */
    public function chat(string $model, array $messages): array
    {
        $provider = $this->getServiceProvider($model);
        
        if ($provider === 'deepseek') {
            // 移除 'deepseek/' 前缀（如果有）
            $cleanModel = str_replace('deepseek/', '', $model);
            return $this->deepSeekService->chat($cleanModel, $messages);
        }
        
        return $this->openRouterService->chat($model, $messages);
    }

    /**
     * 生成图片（仅 OpenRouter 支持）
     * 
     * @param string $model
     * @param string $prompt
     * @param string|null $baseImage
     * @param string|null $aspectRatio
     * @param string|null $imageSize
     * @return array
     * @throws \Exception
     */
    public function generateImage(
        string $model,
        string $prompt,
        ?string $baseImage = null,
        ?string $aspectRatio = null,
        ?string $imageSize = null
    ): array {
        // OpenRouter 图片生成
        return $this->openRouterService->generateImage(
            $model,
            $prompt,
            $baseImage,
            $aspectRatio,
            $imageSize
        );
    }

    /**
     * 获取所有可用模型
     * 
     * @param bool $chatOnly 是否只返回适合聊天的模型
     * @return array
     */
    public function getAllModels(bool $chatOnly = false): array
    {
        $models = [];
        
        try {
            // 获取 OpenRouter 模型
            $openRouterModels = $this->openRouterService->getModels();
            foreach ($openRouterModels as $model) {
                $models[] = array_merge($model, ['provider' => 'openrouter']);
            }
        } catch (\Exception $e) {
            error_log('获取 OpenRouter 模型失败: ' . $e->getMessage());
        }
        
        try {
            // 获取 DeepSeek 模型
            $deepSeekModels = $this->deepSeekService->getModels();
            foreach ($deepSeekModels as $model) {
                // 添加 'deepseek/' 前缀以区分
                $model['id'] = 'deepseek/' . $model['id'];
                $models[] = array_merge($model, ['provider' => 'deepseek']);
            }
        } catch (\Exception $e) {
            error_log('获取 DeepSeek 模型失败: ' . $e->getMessage());
        }
        
        // 如果只需要聊天模型，进行筛选
        if ($chatOnly) {
            $models = $this->filterChatModels($models);
        }
        
        return $models;
    }
    
    /**
     * 筛选适合日常对话的模型
     * 
     * @param array $models
     * @return array
     */
    private function filterChatModels(array $models): array
    {
        // 推荐的聊天模型列表（按优先级排序）
        $recommendedChatModels = [
            // 高性价比模型
            'google/gemini-3.1-flash-lite',
            'google/gemini-2.5-flash',
            'google/gemini-3-flash',
            'anthropic/claude-sonnet-4.6',
            'anthropic/claude-haiku-4.5',
            
            // 旗舰模型
            'openai/gpt-5.4',
            'openai/gpt-4o',
            'openai/gpt-4o-mini',
            'anthropic/claude-opus-4.6',
            'google/gemini-3.1-pro',
            'google/gemini-2.5-pro',
            
            // DeepSeek 模型
            'deepseek/deepseek-chat',
            
            // 其他优质模型
            'x-ai/grok-4.1-fast',
            'x-ai/grok-beta',
            'meta-llama/llama-3.3-70b-instruct',
            'meta-llama/llama-3.1-405b-instruct',
            'qwen/qwen-3.5-plus',
            'qwen/qwen-2.5-72b-instruct',
            'mistral/mistral-large',
            'mistral/mistral-medium',
            'cohere/command-r-plus',
            'perplexity/llama-3.1-sonar-large-128k-online',
        ];
        
        // 需要排除的模型类型（关键词）
        $excludeKeywords = [
            'vision',      // 视觉模型
            'image',       // 图片生成模型
            'embed',       // 嵌入模型
            'moderation',  // 审核模型
            'whisper',     // 语音模型
            'tts',         // 文本转语音
            'dall-e',      // 图片生成
            'flux',        // 图片生成
            'stable-diffusion', // 图片生成
            'preview',     // 预览版本（可能不稳定）
            'beta',        // 测试版本
            'free',        // 免费模型（质量可能较低）
            'nano',        // Nano 系列（通常是小模型）
            'mini',        // Mini 系列（除了 gpt-4o-mini）
            'turbo',       // Turbo 系列（旧版本）
            'instruct',    // Instruct 系列（除了特定推荐的）
            'code',        // 代码专用模型
            'reasoning',   // 推理专用模型
        ];
        
        $filteredModels = [];
        
        foreach ($models as $model) {
            $modelId = $model['id'] ?? '';
            $modelName = strtolower($model['name'] ?? '');
            
            // 检查是否在推荐列表中
            $isRecommended = in_array($modelId, $recommendedChatModels);
            
            // 如果在推荐列表中，直接保留
            if ($isRecommended) {
                $model['recommended'] = true;
                $model['priority'] = array_search($modelId, $recommendedChatModels);
                $filteredModels[] = $model;
                continue;
            }
            
            // 对于非推荐模型，进行严格筛选
            // 检查是否包含排除关键词
            $shouldExclude = false;
            foreach ($excludeKeywords as $keyword) {
                if (stripos($modelId, $keyword) !== false || stripos($modelName, $keyword) !== false) {
                    $shouldExclude = true;
                    break;
                }
            }
            
            // 如果包含排除关键词，跳过
            if ($shouldExclude) {
                continue;
            }
            
            // 额外筛选：只保留主流提供商的模型
            $allowedProviders = [
                'openai/',
                'anthropic/',
                'google/',
                'meta-llama/',
                'mistral/',
                'x-ai/',
                'cohere/',
                'qwen/',
                'deepseek/',
                'perplexity/',
            ];
            
            $isAllowedProvider = false;
            foreach ($allowedProviders as $provider) {
                if (strpos($modelId, $provider) === 0) {
                    $isAllowedProvider = true;
                    break;
                }
            }
            
            // 只保留主流提供商的模型
            if ($isAllowedProvider) {
                $model['recommended'] = false;
                $model['priority'] = 999;
                $filteredModels[] = $model;
            }
        }
        
        // 按优先级排序
        usort($filteredModels, function($a, $b) {
            return ($a['priority'] ?? 999) - ($b['priority'] ?? 999);
        });
        
        return $filteredModels;
    }

    /**
     * 获取 DeepSeek 模型列表
     * 
     * @return array
     */
    public function getDeepSeekModels(): array
    {
        try {
            $models = $this->deepSeekService->getModels();
            // 添加 provider 标记
            return array_map(function($model) {
                $model['id'] = 'deepseek/' . $model['id'];
                $model['provider'] = 'deepseek';
                return $model;
            }, $models);
        } catch (\Exception $e) {
            error_log('获取 DeepSeek 模型失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 获取 OpenRouter 模型列表
     * 
     * @return array
     */
    public function getOpenRouterModels(): array
    {
        try {
            $models = $this->openRouterService->getModels();
            // 添加 provider 标记
            return array_map(function($model) {
                $model['provider'] = 'openrouter';
                return $model;
            }, $models);
        } catch (\Exception $e) {
            error_log('获取 OpenRouter 模型失败: ' . $e->getMessage());
            return [];
        }
    }
}
