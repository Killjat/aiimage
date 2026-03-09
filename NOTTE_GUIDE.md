# Notte 智能监控使用指南

## 简介

Notte 是一个强大的 AI 驱动的网页自动化和监控服务。我们的系统集成了 Notte API，提供以下功能：

- 📊 预定义监控任务（Claude/Anthropic 新闻、定价等）
- 🌐 自定义网页抓取和结构化数据提取
- 🤖 AI 代理执行复杂的自动化任务

## 功能特性

### 1. 预定义监控任务

系统预置了常用的监控任务，可以一键执行：

- **Claude 新闻监控**: 自动抓取 Anthropic 官网的最新新闻和公告
- **Claude 定价监控**: 监控 Claude 模型的价格变化

### 2. 自定义抓取

输入任意网址和抓取指令，AI 会智能提取你需要的信息：

```
网址: https://example.com
指令: 提取所有文章标题、日期和链接
```

### 3. AI 代理任务

让 AI 代理在浏览器中执行复杂任务：

```
起始网址: https://example.com
任务: 在这个网站上找到最新的产品价格并整理成表格
```

## API 端点

### 获取监控任务列表
```
GET /api/notte/monitor/tasks
```

### 执行 Claude 新闻监控
```
GET /api/notte/monitor/anthropic/news
```

### 执行 Claude 定价监控
```
GET /api/notte/monitor/anthropic/pricing
```

### 简单抓取
```
POST /api/notte/scrape
{
  "url": "https://example.com",
  "only_main_content": true
}
```

### 结构化抓取
```
POST /api/notte/scrape/structured
{
  "url": "https://example.com",
  "instructions": "提取所有文章标题和链接",
  "schema": {}
}
```

### 运行 AI 代理
```
POST /api/notte/agent/run
{
  "task": "找到最新的产品价格",
  "start_url": "https://example.com",
  "max_steps": 20
}
```

## 环境配置

在 `backend/.env` 中配置 Notte API：

```env
NOTTE_API_KEY=your_api_key_here
NOTTE_API_URL=https://api.notte.cc
```

## 使用场景

1. **监控竞品动态**: 定期抓取竞争对手的产品更新、价格变化
2. **技术文档追踪**: 监控 API 文档、SDK 更新
3. **新闻聚合**: 自动收集特定主题的新闻和文章
4. **价格监控**: 追踪产品价格变化，发现优惠
5. **数据采集**: 批量提取网站的结构化数据

## 注意事项

- Notte API 需要有效的 API Key
- 抓取频率应遵守目标网站的 robots.txt 和使用条款
- 复杂任务可能需要较长时间执行
- 建议先用简单任务测试，再执行复杂任务

## 前端使用

在主界面点击 "📊 Notte 监控" 按钮，即可打开监控面板：

1. **预定义监控**: 点击任务卡片即可执行
2. **自定义抓取**: 输入网址和指令，点击"开始抓取"
3. **AI 代理**: 输入任务描述，点击"执行任务"

结果会以 JSON 格式展示，方便查看和使用。
