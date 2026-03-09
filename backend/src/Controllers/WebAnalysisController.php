<?php

namespace App\Controllers;

use App\Services\WebScraperService;
use App\Services\AIServiceManager;
use App\Services\OpenRouterService;
use App\Services\DeepSeekService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class WebAnalysisController
{
    private WebScraperService $scraperService;
    private AIServiceManager $aiService;
    
    public function __construct()
    {
        $this->scraperService = new WebScraperService();
        
        // 初始化 AI 服务
        $openRouterService = new OpenRouterService();
        $deepSeekService = new DeepSeekService();
        $this->aiService = new AIServiceManager($openRouterService, $deepSeekService);
    }
    
    /**
     * 分析网站
     */
    public function analyzeWebsite(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            if (!isset($data['url'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => '缺少 URL 参数'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $url = $data['url'];
            $model = $data['model'] ?? 'openai/o3-mini'; // 默认使用 o3-mini 推理模型
            
            // 1. 抓取网站
            $scrapeResult = $this->scraperService->scrapeWebsite($url);
            
            if (!$scrapeResult['success']) {
                $response->getBody()->write(json_encode($scrapeResult));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $websiteData = $scrapeResult['data'];
            
            // 2. 生成分析提示词
            $prompt = $this->scraperService->generateAnalysisPrompt($websiteData);
            
            // 3. 调用 AI 模型分析
            $messages = [
                ['role' => 'user', 'content' => $prompt]
            ];
            $aiResult = $this->aiService->chat($model, $messages);
            
            // 提取 AI 返回的内容
            $analysisContent = '';
            if (isset($aiResult['choices'][0]['message']['content'])) {
                $analysisContent = $aiResult['choices'][0]['message']['content'];
            } elseif (isset($aiResult['content'])) {
                $analysisContent = $aiResult['content'];
            } else {
                error_log('AI 返回格式: ' . json_encode($aiResult));
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'AI 分析失败: 未返回有效内容'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }
            
            // 4. 返回结果
            $result = [
                'success' => true,
                'data' => [
                    'website_info' => [
                        'url' => $websiteData['url'],
                        'title' => $websiteData['title'],
                        'status_code' => $websiteData['status_code'],
                        'scripts_count' => count($websiteData['scripts']),
                        'stylesheets_count' => count($websiteData['stylesheets']),
                        'api_endpoints_count' => count($websiteData['api_endpoints']),
                        'forms_count' => count($websiteData['forms']),
                    ],
                    'analysis' => $analysisContent,
                    'model_used' => $model,
                    'raw_data' => $websiteData // 可选：返回原始数据供前端展示
                ]
            ];
            
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => '服务器错误: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    /**
     * 获取推荐的推理模型列表
     */
    public function getReasoningModels(Request $request, Response $response): Response
    {
        $reasoningModels = [
            [
                'id' => 'meta-llama/llama-3.3-70b-instruct',
                'name' => 'Llama 3.3 70B',
                'description' => '开源大模型，推理能力优秀（推荐）',
                'recommended' => true
            ],
            [
                'id' => 'meta-llama/llama-3.1-405b-instruct',
                'name' => 'Llama 3.1 405B',
                'description' => '超大规模模型，深度分析',
                'recommended' => true
            ],
            [
                'id' => 'qwen/qwen-2.5-72b-instruct',
                'name' => 'Qwen 2.5 72B',
                'description' => '阿里通义千问，中文理解强',
                'recommended' => true
            ],
            [
                'id' => 'mistralai/mistral-large',
                'name' => 'Mistral Large',
                'description' => '欧洲顶级模型，推理能力强',
                'recommended' => false
            ]
        ];
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'models' => $reasoningModels
        ], JSON_UNESCAPED_UNICODE));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
}
