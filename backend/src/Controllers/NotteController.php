<?php

namespace App\Controllers;

use App\Services\NotteService;
use App\Services\CacheService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class NotteController
{
    private NotteService $notteService;
    private CacheService $cacheService;
    
    public function __construct()
    {
        $this->notteService = new NotteService();
        $this->cacheService = new CacheService();
    }
    
    /**
     * 简单抓取网页内容
     */
    public function scrape(Request $request, Response $response): Response
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
            $onlyMainContent = $data['only_main_content'] ?? true;
            
            $result = $this->notteService->scrape($url, $onlyMainContent);
            
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    /**
     * 结构化抓取
     */
    public function scrapeStructured(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            if (!isset($data['url']) || !isset($data['instructions'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => '缺少必要参数'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $url = $data['url'];
            $instructions = $data['instructions'];
            $schema = $data['schema'] ?? [];
            
            $result = $this->notteService->scrapeStructured($url, $instructions, $schema);
            
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    /**
     * 运行 AI 代理（带缓存）
     */
    public function runAgent(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            if (!isset($data['task'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => '缺少任务描述'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $task = $data['task'];
            $startUrl = $data['start_url'] ?? null;
            $responseFormat = $data['response_format'] ?? null;
            $maxSteps = $data['max_steps'] ?? 20;
            $cookies = $data['cookies'] ?? null;
            $headers = $data['headers'] ?? null;
            
            // 生成缓存键参数
            $cacheParams = [
                'task' => $task,
                'url' => $startUrl
            ];
            
            // 尝试从缓存获取
            $cachedResult = $this->cacheService->get('notte_agent', $cacheParams);
            if ($cachedResult !== null) {
                // 添加缓存标记
                $cachedResult['from_cache'] = true;
                $cachedResult['cache_time'] = date('Y-m-d H:i:s');
                
                $response->getBody()->write(json_encode($cachedResult, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json');
            }
            
            // 执行实际请求
            $result = $this->notteService->runAgent($task, $startUrl, $responseFormat, $maxSteps, $cookies, $headers);
            
            // 只缓存成功的结果
            if ($result['success'] && isset($result['data']['structured']['success']) && $result['data']['structured']['success']) {
                $this->cacheService->set('notte_agent', $cacheParams, $result, 600); // 缓存10分钟
            }
            
            $result['from_cache'] = false;
            
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    /**
     * 监控 Anthropic 新闻（带缓存）
     */
    public function monitorAnthropicNews(Request $request, Response $response): Response
    {
        try {
            $cacheParams = ['endpoint' => 'anthropic_news'];
            $cachedResult = $this->cacheService->get('notte_monitor', $cacheParams);
            
            if ($cachedResult !== null) {
                $cachedResult['from_cache'] = true;
                $response->getBody()->write(json_encode($cachedResult, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json');
            }
            
            $result = $this->notteService->monitorAnthropicNews();
            
            if ($result['success']) {
                $this->cacheService->set('notte_monitor', $cacheParams, $result, 1800);
            }
            
            $result['from_cache'] = false;
            
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    /**
     * 监控 Anthropic 定价（带缓存）
     */
    public function monitorAnthropicPricing(Request $request, Response $response): Response
    {
        try {
            $cacheParams = ['endpoint' => 'anthropic_pricing'];
            $cachedResult = $this->cacheService->get('notte_monitor', $cacheParams);
            
            if ($cachedResult !== null) {
                $cachedResult['from_cache'] = true;
                $response->getBody()->write(json_encode($cachedResult, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json');
            }
            
            $result = $this->notteService->monitorAnthropicPricing();
            
            if ($result['success']) {
                $this->cacheService->set('notte_monitor', $cacheParams, $result, 1800);
            }
            
            $result['from_cache'] = false;
            
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    /**
     * 监控 ClawHub 技能动态（带缓存）
     */
    public function monitorClawHubSkills(Request $request, Response $response): Response
    {
        try {
            // 尝试从缓存获取
            $cacheParams = ['endpoint' => 'clawhub_skills'];
            $cachedResult = $this->cacheService->get('notte_monitor', $cacheParams);
            
            if ($cachedResult !== null) {
                $cachedResult['from_cache'] = true;
                $response->getBody()->write(json_encode($cachedResult, JSON_UNESCAPED_UNICODE));
                return $response->withHeader('Content-Type', 'application/json');
            }
            
            // 执行实际请求
            $result = $this->notteService->monitorClawHubSkills();
            
            // 缓存成功的结果（缓存30分钟，因为技能更新不频繁）
            if ($result['success']) {
                $this->cacheService->set('notte_monitor', $cacheParams, $result, 1800);
            }
            
            $result['from_cache'] = false;
            
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    /**
     * 获取预定义的监控任务列表
     */
    public function getMonitorTasks(Request $request, Response $response): Response
    {
        $tasks = [
            [
                'id' => 'anthropic-news',
                'name' => 'Claude 新闻监控',
                'description' => '监控 Anthropic 官网的最新新闻和公告',
                'url' => 'https://www.anthropic.com/news',
                'endpoint' => '/api/notte/monitor/anthropic/news'
            ],
            [
                'id' => 'anthropic-pricing',
                'name' => 'Claude 定价监控',
                'description' => '监控 Claude 模型的价格变化',
                'url' => 'https://www.anthropic.com/pricing',
                'endpoint' => '/api/notte/monitor/anthropic/pricing'
            ],
            [
                'id' => 'clawhub-skills',
                'name' => 'ClawHub 技能动态',
                'description' => '监控 ClawHub 的最新中文技能',
                'url' => 'https://clawhub.ai/skills',
                'endpoint' => '/api/notte/monitor/clawhub/skills'
            ],
        ];
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'tasks' => $tasks
        ], JSON_UNESCAPED_UNICODE));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
}
