<?php

namespace App\Services;

class AIServiceManager
{
    private OpenRouterService $openRouterService;
    private DeepSeekService $deepSeekService;
    private UCloudService $ucloudService;

    public function __construct(
        OpenRouterService $openRouterService,
        DeepSeekService $deepSeekService,
        UCloudService $ucloudService
    ) {
        $this->openRouterService = $openRouterService;
        $this->deepSeekService = $deepSeekService;
        $this->ucloudService = $ucloudService;
    }

    /**
     * 根据模型 ID 判断使用哪个服务
     * 
     * @param string $model
     * @return string 'openrouter', 'deepseek', 或 'ucloud'
     */
    private function getServiceProvider(string $model): string
    {
        // DeepSeek 模型以 'deepseek-' 或 'deepseek/' 开头
        if (strpos($model, 'deepseek-') === 0 || strpos($model, 'deepseek/') === 0) {
            return 'deepseek';
        }
        
        // UCloud 模型以 'ucloud/' 开头
        if (strpos($model, 'ucloud/') === 0) {
            return 'ucloud';
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
        
        if ($provider === 'ucloud') {
            // 移除 'ucloud/' 前缀
            $cleanModel = str_replace('ucloud/', '', $model);
            return $this->ucloudService->chat($cleanModel, $messages);
        }
        
        return $this->openRouterService->chat($model, $messages);
    }

    /**
     * 生成图片（OpenRouter 和 UCloud 支持）
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
        $provider = $this->getServiceProvider($model);
        
        if ($provider === 'ucloud') {
            // UCloud 使用不同的参数格式
            $cleanModel = str_replace('ucloud/', '', $model);
            $size = $this->convertImageSize($imageSize, $aspectRatio);
            return $this->ucloudService->generateImage($cleanModel, $prompt, $size);
        }
        
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
     * 转换图片尺寸格式
     * 
     * @param string|null $imageSize
     * @param string|null $aspectRatio
     * @return string
     */
    private function convertImageSize(?string $imageSize, ?string $aspectRatio): string
    {
        // 默认尺寸
        $size = '1024x1024';
        
        if ($aspectRatio === '16:9') {
            $size = $imageSize === '4K' ? '1792x1024' : '1024x576';
        } elseif ($aspectRatio === '9:16') {
            $size = $imageSize === '4K' ? '1024x1792' : '576x1024';
        } elseif ($imageSize === '4K') {
            $size = '1024x1024';  // DALL-E 3 最大支持 1024x1024
        }
        
        return $size;
    }

    /**
     * 获取所有可用模型
     * 
     * @return array
     */
    public function getAllModels(): array
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
        
        try {
            // 获取 UCloud 模型
            $ucloudModels = $this->ucloudService->getModels();
            foreach ($ucloudModels as $model) {
                // 添加 'ucloud/' 前缀以区分
                $model['id'] = 'ucloud/' . $model['id'];
                $models[] = array_merge($model, ['provider' => 'ucloud']);
            }
        } catch (\Exception $e) {
            error_log('获取 UCloud 模型失败: ' . $e->getMessage());
        }
        
        return $models;
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
    
    /**
     * 获取 UCloud 模型列表
     * 
     * @return array
     */
    public function getUCloudModels(): array
    {
        try {
            $models = $this->ucloudService->getModels();
            // 添加 provider 标记
            return array_map(function($model) {
                $model['id'] = 'ucloud/' . $model['id'];
                $model['provider'] = 'ucloud';
                return $model;
            }, $models);
        } catch (\Exception $e) {
            error_log('获取 UCloud 模型失败: ' . $e->getMessage());
            return [];
        }
    }
}
