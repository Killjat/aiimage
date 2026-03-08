# OpenRouter Chat System - Backend

PHP 后端服务，提供 OpenRouter API 代理功能。

## 快速开始

### 1. 安装依赖

```bash
cd backend
composer install
```

### 2. 配置环境变量

```bash
cp .env.example .env
```

编辑 `.env` 文件，填入你的 OpenRouter API Key：

```
OPENROUTER_API_KEY=your_actual_api_key_here
```

### 3. 启动服务器

```bash
composer start
```

服务器将在 `http://0.0.0.0:8080` 启动。

## API 接口

### 健康检查

```
GET /api/health
```

### 发送聊天消息

```
POST /api/chat/send
Content-Type: application/json

{
  "model": "grok-beta",
  "messages": [
    {"role": "user", "content": "你好"}
  ]
}
```

支持的模型：
- `grok-beta` - Grok
- `google/gemini-pro` - Gemini
- `openai/gpt-4` - GPT-4
- `openai/gpt-3.5-turbo` - GPT-3.5

响应：
```json
{
  "success": true,
  "message": "你好！有什么我可以帮助你的吗？",
  "model": "grok-beta",
  "usage": {
    "prompt_tokens": 10,
    "completion_tokens": 20,
    "total_tokens": 30
  }
}
```
