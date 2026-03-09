<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class NotteService
{
    private string $apiKey;
    private string $apiUrl;
    private Client $client;
    
    public function __construct()
    {
        $this->apiKey = $_ENV['NOTTE_API_KEY'] ?? '';
        $this->apiUrl = $_ENV['NOTTE_API_URL'] ?? 'https://api.notte.cc/v1';
        
        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'timeout' => 120,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ]);
    }
    
    /**
     * 简单抓取 - 返回 Markdown 格式
     * 
     * @param string $url 目标网站 URL
     * @param bool $onlyMainContent 是否只抓取主要内容
     * @return array
     * @throws \Exception
     */
    public function scrape(string $url, bool $onlyMainContent = true): array
    {
        try {
            if (empty($this->apiKey)) {
                throw new \Exception('Notte API Key 未配置');
            }
            
            $response = $this->client->post('/scrape', [
                'json' => [
                    'url' => $url,
                    'only_main_content' => $onlyMainContent,
                ]
            ]);
            
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            
            if ($data === null) {
                throw new \Exception('Notte API 返回数据解析失败');
            }
            
            return [
                'success' => true,
                'content' => $data['content'] ?? $data,
                'raw_data' => $data
            ];
            
        } catch (GuzzleException $e) {
            error_log('Notte Scrape API 错误: ' . $e->getMessage());
            throw new \Exception('Notte 抓取失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 结构化抓取 - 根据 schema 提取数据
     * 
     * @param string $url 目标网站 URL
     * @param string $instructions 提取指令
     * @param array $schema 数据结构定义
     * @return array
     * @throws \Exception
     */
    public function scrapeStructured(string $url, string $instructions, array $schema): array
    {
        try {
            if (empty($this->apiKey)) {
                throw new \Exception('Notte API Key 未配置');
            }
            
            $response = $this->client->post('/scrape', [
                'json' => [
                    'url' => $url,
                    'instructions' => $instructions,
                    'response_format' => $schema,
                ]
            ]);
            
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            
            if ($data === null) {
                throw new \Exception('Notte API 返回数据解析失败');
            }
            
            return [
                'success' => true,
                'data' => $data,
            ];
            
        } catch (GuzzleException $e) {
            error_log('Notte Structured Scrape API 错误: ' . $e->getMessage());
            throw new \Exception('Notte 结构化抓取失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 运行 AI 代理 - 执行复杂任务
     * 
     * @param string $task 任务描述
     * @param string|null $startUrl 起始 URL（可选）
     * @param array|null $responseFormat 返回格式（可选）
     * @param int $maxSteps 最大步骤数
     * @return array
     * @throws \Exception
     */
    public function runAgent(
        string $task,
        ?string $startUrl = null,
        ?array $responseFormat = null,
        int $maxSteps = 20,
        ?string $cookies = null,
        ?string $headers = null
    ): array {
        try {
            if (empty($this->apiKey)) {
                throw new \Exception('Notte API Key 未配置');
            }
            
            // 使用 scrape 端点代替 agent/run
            if (!$startUrl) {
                throw new \Exception('需要提供起始 URL');
            }
            
            // 如果任务包含 "List" 或 "列出"，使用列表格式的 schema
            $isListTask = stripos($task, 'list') !== false || stripos($task, '列出') !== false;
            
            if ($isListTask) {
                $schema = [
                    'type' => 'object',
                    'properties' => [
                        'summary' => [
                            'type' => 'string',
                            'description' => 'Brief summary of the extracted data'
                        ],
                        'items' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'title' => ['type' => 'string'],
                                    'details' => ['type' => 'string']
                                ]
                            ],
                            'description' => 'List of extracted items with title and details'
                        ]
                    ]
                ];
            } else {
                $schema = $responseFormat ?: [
                    'type' => 'object',
                    'properties' => [
                        'analysis' => [
                            'type' => 'string',
                            'description' => 'Detailed analysis result'
                        ],
                        'key_findings' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Key findings'
                        ]
                    ]
                ];
            }
            
            $payload = [
                'url' => $startUrl,
                'instructions' => $task,
                'response_format' => $schema,
            ];
            
            // 添加 cookies 和 headers（如果提供）
            if ($cookies) {
                $payload['cookies'] = $cookies;
            }
            if ($headers) {
                // 尝试解析 JSON 格式的 headers
                $parsedHeaders = json_decode($headers, true);
                if ($parsedHeaders !== null) {
                    $payload['headers'] = $parsedHeaders;
                }
            }
            
            $response = $this->client->post('/scrape', [
                'json' => $payload
            ]);
            
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            
            if ($data === null) {
                throw new \Exception('Notte API 返回数据解析失败');
            }
            
            return [
                'success' => true,
                'data' => $data,
            ];
            
        } catch (GuzzleException $e) {
            error_log('Notte Agent API 错误: ' . $e->getMessage());
            throw new \Exception('Notte 代理执行失败: ' . $e->getMessage());
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function createSession(array $options = []): array
    {
        try {
            if (empty($this->apiKey)) {
                throw new \Exception('Notte API Key 未配置');
            }
            
            $response = $this->client->post('/sessions', [
                'json' => $options
            ]);
            
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            
            if ($data === null) {
                throw new \Exception('Notte API 返回数据解析失败');
            }
            
            return [
                'success' => true,
                'session_id' => $data['session_id'] ?? $data['id'] ?? null,
                'cdp_url' => $data['cdp_url'] ?? null,
                'data' => $data
            ];
            
        } catch (GuzzleException $e) {
            error_log('Notte Session API 错误: ' . $e->getMessage());
            throw new \Exception('Notte 会话创建失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 验证 API Key
     * 
     * @return bool
     */
    public function validateApiKey(): bool
    {
        try {
            if (empty($this->apiKey)) {
                return false;
            }
            
            // 尝试一个简单的 API 调用来验证
            $this->client->get('/sessions');
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 预定义的监控任务
     */
    
    /**
     * 监控 Anthropic 新闻
     */
    public function monitorAnthropicNews(): array
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'articles' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'date' => ['type' => 'string'],
                            'summary' => ['type' => 'string'],
                            'link' => ['type' => 'string'],
                        ]
                    ]
                ]
            ]
        ];
        
        return $this->scrapeStructured(
            'https://www.anthropic.com/news',
            'Extract the latest 5 news articles with their titles, dates, summaries, and links',
            $schema
        );
    }
    
    /**
     * 监控 Anthropic 定价
     */
    public function monitorAnthropicPricing(): array
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'models' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'input_price' => ['type' => 'string'],
                            'output_price' => ['type' => 'string'],
                            'context_window' => ['type' => 'string'],
                        ]
                    ]
                ]
            ]
        ];
        
        return $this->scrapeStructured(
            'https://www.anthropic.com/pricing',
            'Extract all Claude model pricing information including input price, output price, and context window',
            $schema
        );
    }
    
    /**
     * 监控 ClawHub 技能动态（只提取中文内容）
     */
    public function monitorClawHubSkills(): array
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'skills' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'author' => ['type' => 'string'],
                            'link' => ['type' => 'string'],
                            'downloads' => ['type' => 'string'],
                            'stars' => ['type' => 'string'],
                        ]
                    ]
                ]
            ]
        ];
        
        return $this->scrapeStructured(
            'https://clawhub.ai/skills',
            '提取前20个技能的信息。对每个技能提取：标题(title)、描述(description)、作者(author)、链接(link)、下载量(downloads)、星标数(stars)',
            $schema
        );
    }
}
