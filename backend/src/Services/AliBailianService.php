<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AliBailianService
{
    private string $apiKey;
    private string $apiUrl;
    private Client $client;
    
    public function __construct()
    {
        $this->apiKey = $_ENV['ALIBABA_BAILIAN_API_KEY'] ?? '';
        $this->apiUrl = 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text2image/image-synthesis';
        
        $this->client = new Client([
            'timeout' => 120,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ]
        ]);
    }
    
    /**
     * 获取模型使用的 API 端点
     */
    private function getApiEndpoint(string $model): string
    {
        // 只有 qwen-image-2.0 和 qwen-image-2.0-pro 使用新的 multimodal-generation 端点
        if ($model === 'qwen-image-2.0' || $model === 'qwen-image-2.0-pro') {
            return 'https://dashscope.aliyuncs.com/api/v1/services/aigc/multimodal-generation/generation';
        }
        
        // 其他模型使用旧的 text2image 端点
        return 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text2image/image-synthesis';
    }
    
    /**
     * 判断模型是否使用新的 multimodal 端点
     */
    private function usesMultimodalEndpoint(string $model): bool
    {
        return $model === 'qwen-image-2.0' || $model === 'qwen-image-2.0-pro';
    }
    
    /**
     * 生成图片
     * 
     * @param string $prompt 正向提示词
     * @param string $model 模型ID
     * @param string|null $negativePrompt 反向提示词
     * @param string $style 图片风格（仅 wanx-v1 支持）
     * @param string $size 图片尺寸
     * @param int $n 生成数量
     * @param string|null $refImage 参考图片URL
     * @param float $refStrength 参考强度
     * @param string $refMode 参考模式
     * @return array
     * @throws \Exception
     */
    public function generateImage(
        string $prompt,
        string $model = 'wanx-v1',
        ?string $negativePrompt = null,
        string $style = '<auto>',
        string $size = '1024*1024',
        int $n = 1,
        ?string $refImage = null,
        float $refStrength = 1.0,
        string $refMode = 'repaint'
    ): array {
        try {
            if (empty($this->apiKey)) {
                throw new \Exception('阿里百练 API Key 未配置');
            }
            
            // 验证模型
            $supportedModels = $this->getSupportedModels();
            if (!isset($supportedModels[$model])) {
                throw new \Exception('不支持的模型: ' . $model);
            }
            
            // 根据模型选择合适的端点和请求格式
            if ($this->usesMultimodalEndpoint($model)) {
                return $this->generateImageMultimodal($prompt, $model, $negativePrompt, $size, $n, $refImage);
            } else {
                return $this->generateImageLegacy($prompt, $model, $negativePrompt, $style, $size, $n, $refImage, $refStrength, $refMode);
            }
            
        } catch (GuzzleException $e) {
            error_log('阿里百练 API 错误: ' . $e->getMessage());
            throw new \Exception('阿里百练图片生成失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 使用新的 multimodal 端点生成图片（wan2.6, qwen-image）
     */
    private function generateImageMultimodal(
        string $prompt,
        string $model,
        ?string $negativePrompt,
        string $size,
        int $n,
        ?string $refImage = null
    ): array {
        $endpoint = $this->getApiEndpoint($model);
        
        // multimodal 端点需要 width*height 格式，保持原样
        
        // 构建 multimodal 格式的请求
        $content = [
            [
                'text' => $prompt
            ]
        ];
        
        // 如果有参考图片，添加到 content 中
        if ($refImage) {
            // 确保参考图片格式正确：data:image/jpeg;base64,xxx 或 data:image/png;base64,xxx
            if (!preg_match('/^data:image\/(jpeg|jpg|png|bmp|webp);base64,/', $refImage)) {
                // 如果没有 MIME 类型前缀，添加一个
                if (strpos($refImage, 'data:') === 0) {
                    // 已经有 data: 前缀但格式不对，尝试修复
                    $refImage = preg_replace('/^data:[^;]*;base64,/', 'data:image/jpeg;base64,', $refImage);
                } else {
                    // 完全没有前缀，添加完整的前缀
                    $refImage = 'data:image/jpeg;base64,' . $refImage;
                }
            }
            
            $content[] = [
                'image' => $refImage
            ];
        }
        
        $requestData = [
            'model' => $model,
            'input' => [
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $content
                    ]
                ]
            ],
            'parameters' => [
                'n' => $n,
                'size' => $size,
                'prompt_extend' => true
                // 移除 watermark: false，使用默认值
            ]
        ];
        
        // 添加反向提示词
        if ($negativePrompt) {
            $requestData['parameters']['negative_prompt'] = $negativePrompt;
        }
        
        $response = $this->client->post($endpoint, [
            'json' => $requestData,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ]
        ]);
        
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        
        if ($data === null) {
            throw new \Exception('阿里百练 API 返回数据解析失败');
        }
        
        // 检查是否有错误
        if (isset($data['code']) && $data['code'] !== '200') {
            error_log('Multimodal API Error Response: ' . json_encode($data));
            
            // 处理特定的错误信息
            $message = $data['message'] ?? '未知错误';
            if (strpos($message, 'DataInspectionFailed') !== false) {
                throw new \Exception('图片内容被审核拒绝，请修改提示词后重试');
            } elseif (strpos($message, 'InsufficientBalance') !== false) {
                throw new \Exception('账户余额不足');
            } elseif (strpos($message, 'RateLimitExceeded') !== false) {
                throw new \Exception('请求过于频繁，请稍后再试');
            }
            
            throw new \Exception('API 错误: ' . $message);
        }
        
        // 检查 output 结构
        if (!isset($data['output'])) {
            error_log('Multimodal API Response: ' . json_encode($data));
            throw new \Exception('API 返回格式错误: 缺少 output 字段');
        }
        
        // multimodal 端点返回 choices 结构
        if (isset($data['output']['choices']) && is_array($data['output']['choices'])) {
            $imageUrls = [];
            
            foreach ($data['output']['choices'] as $choice) {
                if (isset($choice['message']['content']) && is_array($choice['message']['content'])) {
                    foreach ($choice['message']['content'] as $content) {
                        if (isset($content['image'])) {
                            $imageUrls[] = $content['image'];
                        }
                    }
                }
            }
            
            if (!empty($imageUrls)) {
                return [
                    'success' => true,
                    'status' => 'completed',
                    'images' => $imageUrls,
                    'message' => '图片生成成功',
                    'original_prompt' => $prompt,
                    'prompt_extended' => true
                ];
            }
        }
        
        error_log('Multimodal API Response: ' . json_encode($data));
        throw new \Exception('生成失败: 无法提取图片 URL');
    }
    
    /**
     * 使用旧的 text2image 端点生成图片（wanx-v1, wan2.2-t2i-flash 等）
     */
    private function generateImageLegacy(
        string $prompt,
        string $model,
        ?string $negativePrompt,
        string $style,
        string $size,
        int $n,
        ?string $refImage,
        float $refStrength,
        string $refMode
    ): array {
        $endpoint = $this->getApiEndpoint($model);
        
        // 构建请求数据
        $input = ['prompt' => $prompt];
        if ($negativePrompt) {
            $input['negative_prompt'] = $negativePrompt;
        }
        if ($refImage) {
            $input['ref_image'] = $refImage;
        }
        
        $parameters = [
            'n' => $n,
            'size' => $size
        ];
        
        // 根据模型类型设置参数
        if ($model === 'wanx-v1') {
            $parameters['style'] = $style;
        }
        // 注意：wan2.6-t2i 和其他新模型不需要 ref_mode 参数
        // API 会根据提示词自动进行图像编辑
        
        $requestData = [
            'model' => $model,
            'input' => $input,
            'parameters' => $parameters
        ];
        
        // 发起创建任务请求（异步）
        $response = $this->client->post($endpoint, [
            'json' => $requestData,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'X-DashScope-Async' => 'enable'
            ]
        ]);
        
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        
        if ($data === null) {
            throw new \Exception('阿里百练 API 返回数据解析失败');
        }
        
        if (isset($data['output']['task_id'])) {
            return [
                'success' => true,
                'task_id' => $data['output']['task_id'],
                'message' => '任务创建成功，正在生成图片...'
            ];
        } else {
            throw new \Exception('创建任务失败: ' . ($data['message'] ?? '未知错误'));
        }
    }
    
    /**
     * 查询任务状态和结果
     * 
     * @param string $taskId 任务ID
     * @return array
     * @throws \Exception
     */
    public function getTaskResult(string $taskId): array
    {
        try {
            if (empty($this->apiKey)) {
                throw new \Exception('阿里百练 API Key 未配置');
            }
            
            $queryUrl = "https://dashscope.aliyuncs.com/api/v1/tasks/{$taskId}";
            
            $response = $this->client->get($queryUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey
                ]
            ]);
            
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            
            if ($data === null) {
                throw new \Exception('阿里百练 API 返回数据解析失败');
            }
            
            $status = $data['output']['task_status'] ?? 'UNKNOWN';
            
            switch ($status) {
                case 'SUCCEEDED':
                    $results = $data['output']['results'] ?? [];
                    $imageUrls = [];
                    foreach ($results as $result) {
                        if (isset($result['url'])) {
                            $imageUrls[] = $result['url'];
                        }
                    }
                    
                    return [
                        'success' => true,
                        'status' => 'completed',
                        'images' => $imageUrls,
                        'message' => '图片生成成功'
                    ];
                    
                case 'FAILED':
                    return [
                        'success' => false,
                        'status' => 'failed',
                        'message' => $data['output']['message'] ?? '生成失败'
                    ];
                    
                case 'PENDING':
                case 'RUNNING':
                    return [
                        'success' => true,
                        'status' => 'processing',
                        'message' => '正在生成中...'
                    ];
                    
                default:
                    return [
                        'success' => false,
                        'status' => 'unknown',
                        'message' => '未知状态: ' . $status
                    ];
            }
            
        } catch (GuzzleException $e) {
            error_log('阿里百练查询任务错误: ' . $e->getMessage());
            throw new \Exception('查询任务状态失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取支持的图片尺寸
     */
    public function getSupportedSizes(): array
    {
        return [
            '1024*1024' => '1:1 正方形',
            '720*1280' => '9:16 竖屏',
            '1280*720' => '16:9 横屏'
        ];
    }
    
    /**
     * 获取支持的风格
     */
    public function getSupportedStyles(): array
    {
        return [
            '<auto>' => '自动',
            '<photography>' => '摄影',
            '<portrait>' => '人像写真',
            '<3d cartoon>' => '3D卡通',
            '<anime>' => '动画',
            '<oil painting>' => '油画',
            '<watercolor>' => '水彩',
            '<sketch>' => '素描',
            '<chinese painting>' => '中国画',
            '<flat illustration>' => '扁平插画'
        ];
    }
    
    /**
     * 获取所有支持的模型
     */
    public function getSupportedModels(): array
    {
        return [
            // 已通过测试的 6 个模型
            'wan2.6-t2i' => [
                'name' => '万相 2.6',
                'desc' => '最新版本，超高质量，支持同步调用',
                'category' => 'text-to-image',
                'version' => 'v2',
                'features' => ['超高质量', '同步调用', '提示词优化', '水印控制'],
                'sizes' => ['1280*1280', '1104*1472', '1472*1104', '960*1696', '1696*960', '768*2700'],
                'styles' => false,
                'max_prompt_length' => 2100
            ],
            'wan2.5-t2i-preview' => [
                'name' => '万相 2.5',
                'desc' => '高质量预览版，支持图片编辑',
                'category' => 'text-to-image',
                'version' => 'v2',
                'features' => ['高质量', '图片编辑', '异步调用'],
                'sizes' => ['1280*1280', '1104*1472', '1472*1104', '960*1696', '1696*960'],
                'styles' => false,
                'max_prompt_length' => 2100
            ],
            'wan2.2-t2i-flash' => [
                'name' => '万相 2.2',
                'desc' => '快速版本，支持图片编辑',
                'category' => 'text-to-image',
                'version' => 'v2',
                'features' => ['快速生成', '图片编辑', '异步调用'],
                'sizes' => ['1280*1280', '1104*1472', '1472*1104', '960*1696', '1696*960'],
                'styles' => false,
                'max_prompt_length' => 2100
            ],
            'wanx-v1' => [
                'name' => '万相 V1',
                'desc' => '经典版本，风格控制，支持图片编辑',
                'category' => 'text-to-image',
                'version' => 'v1',
                'features' => ['风格控制', '图片编辑', '异步调用'],
                'sizes' => ['1280*1280', '1104*1472', '1472*1104', '960*1696', '1696*960'],
                'styles' => ['<auto>', '<photography>', '<portrait>', '<3d cartoon>', '<anime>', '<oil painting>', '<watercolor>', '<sketch>', '<chinese painting>', '<flat illustration>'],
                'max_prompt_length' => 2100
            ],
            'stable-diffusion-v1.5' => [
                'name' => 'Stable Diffusion v1.5',
                'desc' => '开源经典模型',
                'category' => 'text-to-image',
                'version' => 'sd',
                'features' => ['开源', '经典'],
                'sizes' => ['1280*1280', '1024*1024'],
                'styles' => false,
                'max_prompt_length' => 1000
            ],
            'stable-diffusion-xl' => [
                'name' => 'Stable Diffusion XL',
                'desc' => '增强版本，更高质量',
                'category' => 'text-to-image',
                'version' => 'sd',
                'features' => ['增强版', '高质量'],
                'sizes' => ['1280*1280', '1024*1024'],
                'styles' => false,
                'max_prompt_length' => 1000
            ],
            'stable-diffusion-3.5-large' => [
                'name' => 'Stable Diffusion 3.5',
                'desc' => '最高质量版本',
                'category' => 'text-to-image',
                'version' => 'sd',
                'features' => ['最高质量', '精准渲染'],
                'sizes' => ['1280*1280', '1024*1024'],
                'styles' => false,
                'max_prompt_length' => 1000
            ],
            'qwen-image-2.0-pro' => [
                'name' => '千问图像 2.0 Pro',
                'desc' => '满血版，最强文字渲染和真实质感',
                'category' => 'text-to-image',
                'version' => 'qwen',
                'features' => ['文字渲染', '真实质感', '图片编辑'],
                'sizes' => ['1024*1024'],
                'styles' => false,
                'max_prompt_length' => 1000,
                'price' => '0.5元/张'
            ],
            'qwen-image-2.0' => [
                'name' => '千问图像 2.0',
                'desc' => '加速版，效果和性能最佳平衡',
                'category' => 'text-to-image',
                'version' => 'qwen',
                'features' => ['文字渲染', '真实质感', '图片编辑'],
                'sizes' => ['1024*1024'],
                'styles' => false,
                'max_prompt_length' => 1000,
                'price' => '0.2元/张'
            ],
            'qwen-image-max' => [
                'name' => '千问图像 Max',
                'desc' => '最高质量，真实性最强',
                'category' => 'text-to-image',
                'version' => 'qwen',
                'features' => ['超高质量', '真实人物', '细腻纹理'],
                'sizes' => ['1024*1024'],
                'styles' => false,
                'max_prompt_length' => 1000,
                'price' => '0.5元/张'
            ],
            'qwen-image-plus' => [
                'name' => '千问图像 Plus',
                'desc' => '高质量，文本渲染能力强',
                'category' => 'text-to-image',
                'version' => 'qwen',
                'features' => ['文字渲染', '高质量', '生成编辑'],
                'sizes' => ['1024*1024'],
                'styles' => false,
                'max_prompt_length' => 1000,
                'price' => '0.2元/张'
            ],
            'qwen-image' => [
                'name' => '千问图像',
                'desc' => '首代模型，文本渲染能力强',
                'category' => 'text-to-image',
                'version' => 'qwen',
                'features' => ['文字渲染', '高质量'],
                'sizes' => ['1024*1024'],
                'styles' => false,
                'max_prompt_length' => 1000,
                'price' => '0.25元/张'
            ],
        ];
    }

    /**
     * 获取模型信息
     */
    public function getModelInfo(string $model): ?array
    {
        $models = $this->getSupportedModels();
        return $models[$model] ?? null;
    }

    /**
     * 获取模型支持的尺寸
     */
    public function getModelSizes(string $model): array
    {
        $modelInfo = $this->getModelInfo($model);
        if (!$modelInfo) {
            return [];
        }
        
        $sizes = [];
        foreach ($modelInfo['sizes'] as $size) {
            $sizes[$size] = $size;
        }
        return $sizes;
    }

    /**
     * 验证 API Key
     */
    public function validateApiKey(): bool
    {
        try {
            if (empty($this->apiKey)) {
                return false;
            }
            
            // 尝试创建一个简单的任务来验证 API Key
            $result = $this->generateImage('test', 'wanx-v1');
            return $result['success'] ?? false;
            
        } catch (\Exception $e) {
            return false;
        }
    }
}