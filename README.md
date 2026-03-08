# OpenRouter AI Chat System

基于 OpenRouter API 的 AI 聊天系统，支持多模型对话。

## 第一阶段：基础聊天功能 ✅

当前已实现：
- ✅ 后端 PHP API 服务
- ✅ OpenRouter API 集成
- ✅ 前端 React 聊天界面
- ✅ 三个模型选择（Grok、Gemini、GPT-4）
- ✅ 实时对话功能

## 项目结构

```
.
├── backend/          # PHP 后端服务
│   ├── src/
│   │   ├── Controllers/
│   │   ├── Services/
│   │   └── routes.php
│   ├── public/
│   │   └── index.php
│   └── composer.json
│
└── frontend/         # React 前端
    ├── src/
    │   ├── components/
    │   ├── services/
    │   └── App.tsx
    └── package.json
```

## 快速启动

### 1. 启动后端

```bash
cd backend
composer install
cp .env.example .env
# 编辑 .env 文件，填入你的 OPENROUTER_API_KEY
composer start
```

后端将在 `http://0.0.0.0:8080` 启动。

### 2. 启动前端

```bash
cd frontend
npm install
cp .env.example .env
# 默认配置已经设置好，直接启动即可
npm run dev
```

前端将在 `http://127.0.0.1:5173` 启动。

### 3. 获取 OpenRouter API Key

1. 访问 [OpenRouter](https://openrouter.ai/)
2. 注册账号并获取 API Key
3. 将 API Key 填入 `backend/.env` 文件

## 使用说明

1. 打开浏览器访问 `http://127.0.0.1:5173`
2. 选择一个 AI 模型（Grok、Gemini 或 GPT-4）
3. 在输入框中输入消息
4. 点击"发送"按钮开始对话

## 部署到服务器

查看 [DEPLOYMENT.md](./DEPLOYMENT.md) 了解详细的部署配置说明。

简单来说，只需要修改环境变量文件：
- `backend/.env` - 修改 APP_URL 和 CORS_ALLOWED_ORIGINS
- `frontend/.env` - 修改 VITE_API_BASE_URL

## 下一步计划

- [ ] 对话历史保存（数据库）
- [ ] 图片生成功能
- [ ] 智能搜索功能
- [ ] 用户配置管理
- [ ] 更多 UI 优化
# aiimage
