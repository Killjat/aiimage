<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class WebScraperService
{
    private Client $client;
    
    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
            ]
        ]);
    }
    
    /**
     * 抓取网站内容
     */
    public function scrapeWebsite(string $url): array
    {
        try {
            // 验证 URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new \Exception('无效的 URL');
            }
            
            // 发送请求
            $response = $this->client->get($url);
            $html = $response->getBody()->getContents();
            $statusCode = $response->getStatusCode();
            
            // 提取信息
            $analysis = [
                'url' => $url,
                'status_code' => $statusCode,
                'html' => $html,
                'html_length' => strlen($html),
                'title' => $this->extractTitle($html),
                'meta_tags' => $this->extractMetaTags($html),
                'scripts' => $this->extractScripts($html),
                'stylesheets' => $this->extractStylesheets($html),
                'api_endpoints' => $this->extractApiEndpoints($html),
                'forms' => $this->extractForms($html),
                'headers' => $response->getHeaders(),
            ];
            
            return [
                'success' => true,
                'data' => $analysis
            ];
            
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => '网站抓取失败: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 提取网页标题
     */
    private function extractTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            return trim(strip_tags($matches[1]));
        }
        return null;
    }
    
    /**
     * 提取 Meta 标签
     */
    private function extractMetaTags(string $html): array
    {
        $metaTags = [];
        if (preg_match_all('/<meta\s+([^>]+)>/i', $html, $matches)) {
            foreach ($matches[1] as $meta) {
                $metaTags[] = $meta;
            }
        }
        return $metaTags;
    }
    
    /**
     * 提取 JavaScript 文件
     */
    private function extractScripts(string $html): array
    {
        $scripts = [];
        if (preg_match_all('/<script[^>]*src=["\']([^"\']+)["\'][^>]*>/i', $html, $matches)) {
            $scripts = array_unique($matches[1]);
        }
        return array_values($scripts);
    }
    
    /**
     * 提取 CSS 文件
     */
    private function extractStylesheets(string $html): array
    {
        $stylesheets = [];
        if (preg_match_all('/<link[^>]*rel=["\']stylesheet["\'][^>]*href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches)) {
            $stylesheets = array_merge($stylesheets, $matches[1]);
        }
        if (preg_match_all('/<link[^>]*href=["\']([^"\']+)["\'][^>]*rel=["\']stylesheet["\'][^>]*>/i', $html, $matches)) {
            $stylesheets = array_merge($stylesheets, $matches[1]);
        }
        return array_values(array_unique($stylesheets));
    }
    
    /**
     * 提取可能的 API 端点
     */
    private function extractApiEndpoints(string $html): array
    {
        $endpoints = [];
        
        // 查找常见的 API 模式
        $patterns = [
            '/["\']https?:\/\/[^"\']*\/api\/[^"\']+["\']/i',
            '/["\']\/api\/[^"\']+["\']/i',
            '/fetch\(["\']([^"\']+)["\']/i',
            '/axios\.[a-z]+\(["\']([^"\']+)["\']/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                foreach ($matches[0] as $match) {
                    $endpoint = trim($match, '"\' ');
                    if (!empty($endpoint)) {
                        $endpoints[] = $endpoint;
                    }
                }
            }
        }
        
        return array_values(array_unique($endpoints));
    }
    
    /**
     * 提取表单信息
     */
    private function extractForms(string $html): array
    {
        $forms = [];
        if (preg_match_all('/<form[^>]*>(.*?)<\/form>/is', $html, $matches)) {
            foreach ($matches[0] as $form) {
                $formData = [
                    'action' => null,
                    'method' => 'GET',
                    'inputs' => []
                ];
                
                // 提取 action
                if (preg_match('/action=["\']([^"\']+)["\']/i', $form, $actionMatch)) {
                    $formData['action'] = $actionMatch[1];
                }
                
                // 提取 method
                if (preg_match('/method=["\']([^"\']+)["\']/i', $form, $methodMatch)) {
                    $formData['method'] = strtoupper($methodMatch[1]);
                }
                
                // 提取 input 字段
                if (preg_match_all('/<input[^>]*>/i', $form, $inputMatches)) {
                    foreach ($inputMatches[0] as $input) {
                        $inputData = [];
                        if (preg_match('/name=["\']([^"\']+)["\']/i', $input, $nameMatch)) {
                            $inputData['name'] = $nameMatch[1];
                        }
                        if (preg_match('/type=["\']([^"\']+)["\']/i', $input, $typeMatch)) {
                            $inputData['type'] = $typeMatch[1];
                        }
                        if (!empty($inputData)) {
                            $formData['inputs'][] = $inputData;
                        }
                    }
                }
                
                $forms[] = $formData;
            }
        }
        return $forms;
    }
    
    /**
     * 生成分析提示词
     */
    public function generateAnalysisPrompt(array $websiteData): string
    {
        $prompt = "请分析以下网站的技术架构和实现原理：\n\n";
        $prompt .= "## 基本信息\n";
        $prompt .= "- URL: {$websiteData['url']}\n";
        $prompt .= "- 状态码: {$websiteData['status_code']}\n";
        $prompt .= "- 标题: " . ($websiteData['title'] ?? '未找到') . "\n";
        $prompt .= "- HTML 大小: " . number_format($websiteData['html_length']) . " 字节\n\n";
        
        if (!empty($websiteData['scripts'])) {
            $prompt .= "## JavaScript 文件 (" . count($websiteData['scripts']) . " 个)\n";
            foreach (array_slice($websiteData['scripts'], 0, 10) as $script) {
                $prompt .= "- $script\n";
            }
            if (count($websiteData['scripts']) > 10) {
                $prompt .= "... 还有 " . (count($websiteData['scripts']) - 10) . " 个文件\n";
            }
            $prompt .= "\n";
        }
        
        if (!empty($websiteData['stylesheets'])) {
            $prompt .= "## CSS 文件 (" . count($websiteData['stylesheets']) . " 个)\n";
            foreach (array_slice($websiteData['stylesheets'], 0, 5) as $css) {
                $prompt .= "- $css\n";
            }
            if (count($websiteData['stylesheets']) > 5) {
                $prompt .= "... 还有 " . (count($websiteData['stylesheets']) - 5) . " 个文件\n";
            }
            $prompt .= "\n";
        }
        
        if (!empty($websiteData['api_endpoints'])) {
            $prompt .= "## 发现的 API 端点 (" . count($websiteData['api_endpoints']) . " 个)\n";
            foreach (array_slice($websiteData['api_endpoints'], 0, 15) as $endpoint) {
                $prompt .= "- $endpoint\n";
            }
            if (count($websiteData['api_endpoints']) > 15) {
                $prompt .= "... 还有 " . (count($websiteData['api_endpoints']) - 15) . " 个端点\n";
            }
            $prompt .= "\n";
        }
        
        if (!empty($websiteData['forms'])) {
            $prompt .= "## 表单信息 (" . count($websiteData['forms']) . " 个)\n";
            foreach ($websiteData['forms'] as $i => $form) {
                $prompt .= "表单 " . ($i + 1) . ":\n";
                $prompt .= "  - Action: " . ($form['action'] ?? '未指定') . "\n";
                $prompt .= "  - Method: {$form['method']}\n";
                $prompt .= "  - 字段数: " . count($form['inputs']) . "\n";
            }
            $prompt .= "\n";
        }
        
        // 添加 HTML 片段（前 5000 字符）
        $htmlSnippet = substr($websiteData['html'], 0, 5000);
        $prompt .= "## HTML 代码片段（前 5000 字符）\n";
        $prompt .= "```html\n$htmlSnippet\n```\n\n";
        
        $prompt .= "## 分析要求\n";
        $prompt .= "请详细分析：\n";
        $prompt .= "1. 前端技术栈（框架、库、工具）\n";
        $prompt .= "2. 后端可能使用的技术\n";
        $prompt .= "3. API 接口设计和调用方式\n";
        $prompt .= "4. 数据流和业务逻辑\n";
        $prompt .= "5. 安全机制（认证、加密等）\n";
        $prompt .= "6. 性能优化手段\n";
        $prompt .= "7. 可能的实现难点和解决方案\n";
        
        return $prompt;
    }
}
