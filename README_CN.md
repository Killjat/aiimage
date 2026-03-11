# Cyberstroll - 多模态AI聊天平台

一个功能完整的AI聊天和图片生成平台，支持多个AI模型和服务。

## 📚 文档

所有开发文档已整理到 **[开发指南](开发指南/)** 文件夹中。

### 快速链接

- 🚀 [快速开始](开发指南/QUICK_START.md)
- 📦 [部署指南](开发指南/DEPLOYMENT_README.md)
- 🔧 [环境配置](开发指南/ENV_CONFIGURATION.md)
- 📖 [完整文档索引](开发指南/README.md)

## 🎯 主要功能

- 💬 **AI 聊天** - 支持多个AI模型
- 🎨 **图片生成** - 支持Alibaba和OpenRouter模型
- 🖼️ **图片库** - 浏览和管理生成的图片
- 🔍 **智能分析** - 网站内容分析
- 🤖 **自动化** - Notte自动化工具

## 🛠️ 快速开始

### 本地开发

```bash
# 1. 配置环境
cp frontend/.env.local frontend/.env
cp backend/.env.local backend/.env

# 2. 启动后端
php -S 0.0.0.0:8080 -t backend/public

# 3. 启动前端
cd frontend && npm run dev
```

### 远程部署

```bash
# 一键部署
bash scripts/quick_deploy.sh
```

## 📁 项目结构

```
.
├── backend/              # PHP后端
│   ├── src/             # 源代码
│   ├── database/        # 数据库脚本
│   ├── public/          # 公开目录
│   └── .env.local       # 本地配置
├── frontend/            # React前端
│   ├── src/             # 源代码
│   ├── dist/            # 构建输出
│   └── .env.local       # 本地配置
├── scripts/             # 部署脚本
├── 开发指南/            # 所有文档
└── README.md            # 本文件
```

## 🚀 部署

### 快速部署（推荐）

```bash
bash scripts/quick_deploy.sh
```

耗时: 2-3 分钟

### 完整部署（包含测试）

```bash
bash scripts/deploy_to_remote.sh
```

耗时: 3-5 分钟

## 📊 技术栈

### 后端
- PHP 8.0+
- Slim Framework
- MySQL
- Docker

### 前端
- React 18
- TypeScript
- Vite
- TailwindCSS

### 服务
- OpenRouter API
- Alibaba Bailian
- DeepSeek API
- Notte API

## 🔑 环境变量

### 前端
```
VITE_API_BASE_URL=http://127.0.0.1:8080/api  # 本地
VITE_API_BASE_URL=https://165.154.235.9/api  # 远程
```

### 后端
```
DB_HOST=127.0.0.1           # 本地
DB_HOST=mysql-aiimage       # 远程 (Docker)
OPENROUTER_API_KEY=...
ALIBABA_BAILIAN_API_KEY=...
```

详见 [环境配置指南](开发指南/ENV_CONFIGURATION.md)

## 📖 文档

所有文档都在 [开发指南](开发指南/) 文件夹中：

- **部署**: DEPLOYMENT_README.md, QUICK_DEPLOY_GUIDE.md
- **功能**: IMAGE_GALLERY_GUIDE.md, NOTTE_GUIDE.md, SMART_ANALYSIS_GUIDE.md
- **模型**: ALIBABA_MODELS_QUICK_START.md, OPENROUTER_MODELS_GUIDE.md
- **配置**: ENV_CONFIGURATION.md, PROJECT_STRUCTURE.md
- **参考**: QUICK_REFERENCE.md, USER_GUIDE.md

完整索引见 [开发指南/README.md](开发指南/README.md)

## 🧪 测试

```bash
# 测试本地部署
bash scripts/test_deployment.sh

# 测试远程部署
bash scripts/test_deployment.sh 165.154.235.9
```

## 🐛 常见问题

### 前端显示"离线"状态
检查 `frontend/.env` 中的 `VITE_API_BASE_URL` 是否正确

### 后端连接数据库失败
检查 `backend/.env` 中的数据库配置

### 部署失败
查看 [部署指南](开发指南/DEPLOYMENT_README.md) 中的故障排除部分

## 📞 支持

- 查看 [开发指南](开发指南/) 中的相关文档
- 检查 [快速参考](开发指南/QUICK_REFERENCE.md) 中的常见问题

## 📝 许可证

MIT License

---

**最后更新**: 2026-03-11
