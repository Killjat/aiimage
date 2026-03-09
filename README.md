# OpenRouter AI Chat System

基于 OpenRouter API 的 AI 聊天和图片生成系统，支持多模型对话和图片生成。

## 功能特性 ✅

### 🎯 游客模式 NEW!
- ✅ 无需注册即可使用聊天功能
- ✅ 游客可免费生成 3 张图片
- ✅ 体验后可注册获取更多配额

### 聊天功能
- ✅ 支持 348 个 AI 模型（OpenRouter + DeepSeek）
- ✅ 实时对话
- ✅ 对话历史保存（localStorage）
- ✅ Google 风格简洁界面
- ✅ 自动选择最佳模型
- ✅ 无需登录即可使用

### 图片生成功能 🎨
- ✅ 6 个顶级图片生成模型
- ✅ 前端模型选择器
- ✅ 支持 4K 高清图片生成
- ✅ 图片预览和下载
- ✅ 支持中英文提示词
- ✅ 图片上传和编辑（点击/拖拽）
- ✅ 游客：3 张免费配额
- ✅ 已登录用户：10 张配额

**支持的图片生成模型**：
- 🍌 Nano Banana 2 (Gemini 3.1) - 最新最强，支持4K
- 🍌 Nano Banana (Gemini 2.5) - 速度极快
- ⚡ Flux 2 Pro - 顶级质量
- ⚡ Flux 2 Flex - 平衡选择
- 🎨 Riverflow V2 Pro - 完美文字渲染
- 🎨 Riverflow V2 Fast - 快速生成

### 网站分析功能 🔍
- ✅ 静态网站分析（HTML/CSS/JS）
- ✅ API 端点提取
- ✅ 表单结构分析
- ✅ AI 推理模型分析网站架构

### AI 智能网站分析 🤖 NEW!
- ✅ 基于分布全球 1000台住宅IP 的强大抓取能力
- ✅ 自动提取结构化数据（标题、价格、链接等）
- ✅ 内容分析和功能识别
- ✅ 支持 JavaScript 渲染的动态网站
- ✅ 4 个快速示例帮助上手
- 📖 详见 [智能分析使用指南](SMART_ANALYSIS_GUIDE.md)

### Notte 智能监控 📊 NEW!
- ✅ 预定义监控任务（Claude 新闻、定价）
- ✅ 自定义网页抓取
- ✅ 结构化数据提取
- ✅ AI 代理自动化任务
- ✅ 实时监控网站变化
- 📖 详见 [Notte 使用指南](NOTTE_GUIDE.md)

## 项目结构

```
.
├── backend/                     # PHP 后端服务
│   ├── src/
│   │   ├── Controllers/         # 控制器
│   │   ├── Services/            # 服务层
│   │   └── Database/            # 数据库
│   ├── database/
│   │   └── schema.sql           # 数据库结构
│   └── public/
│       └── index.php            # 入口文件
│
├── frontend/                    # React 前端
│   ├── src/
│   │   ├── components/          # UI组件
│   │   ├── services/            # API服务
│   │   └── App.tsx              # 主应用
│   └── dist/                    # 构建输出
│
├── scripts/                     # 🆕 脚本目录
│   ├── README.md                # 脚本说明文档
│   ├── deploy_remote.sh.example # 远程部署模板
│   ├── setup_https*.sh.example  # HTTPS配置模板
│   ├── start_system.sh          # 启动系统
│   ├── stop_system.sh           # 停止系统
│   ├── check_system_status.sh   # 状态检查
│   └── test_*.sh                # 测试脚本
│
├── DEPLOYMENT_GUIDE.md          # 部署指南
├── API_PROVIDERS.md             # API提供商说明
├── AUTH_GUIDE.md                # 认证指南
└── USER_GUIDE.md                # 用户指南
```

## 快速启动

### 📦 从 GitHub 克隆

```bash
git clone https://github.com/Killjat/aiimage.git
cd aiimage
```

### 方式1: 使用脚本（推荐）

```bash
# 启动系统
./scripts/start_system.sh

# 检查状态
./scripts/check_system_status.sh

# 停止系统
./scripts/stop_system.sh
```

### 方式2: 手动启动

#### 1. 启动后端

```bash
cd backend
composer install
cp .env.example .env
# 编辑 .env 文件，填入你的 OPENROUTER_API_KEY
php -S 0.0.0.0:8080 -t public
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

前端将在 `http://localhost:5173` 启动。

### 3. 获取 OpenRouter API Key

1. 访问 [OpenRouter](https://openrouter.ai/)
2. 注册账号并获取 API Key
3. 将 API Key 填入 `backend/.env` 文件

## 使用说明

### 聊天功能
1. 打开浏览器访问 `http://localhost:5173`
2. 从下拉框选择一个 AI 模型（或选择"Auto"自动选择）
3. 在输入框中输入消息
4. 点击"发送"按钮或按 Enter 键开始对话

### 图片生成功能 🎨
1. 点击页面顶部的"🎨 生成图片"按钮
2. 从下拉框选择图片生成模型（推荐 Nano Banana 2）
3. 输入图片描述（支持中英文）
4. 点击"🎨 生成图片"按钮
5. 等待 3-10 秒，图片生成完成
6. 可以下载图片或重新生成

**提示词示例**：
- 简单：`一只可爱的橘猫坐在窗台上`
- 详细：`一幅水彩画风格的插画，描绘一只橘色的猫咪坐在木质窗台上，温暖的阳光从窗户照进来`
- 英文：`A cute orange cat sitting on a windowsill, watercolor painting style`

## API 端点

### 聊天相关
- `GET /api/health` - 健康检查
- `GET /api/models` - 获取所有可用模型（346个）
- `POST /api/chat/send` - 发送聊天消息

### 图片生成相关
- `POST /api/image/generate` - 生成图片
- `GET /api/image/models` - 获取图片生成模型列表

## 部署到服务器

### 🚀 混合部署策略（推荐）

我们采用混合部署方案，兼顾开发效率和版本管理。详见 [部署策略文档](./DEPLOYMENT_STRATEGY.md)

#### 方式1: 快速部署（日常开发）
```bash
# 从本地直接同步到服务器，1-2分钟完成
./scripts/sync_to_server.sh
```

适用场景：快速修复、功能测试、频繁迭代

#### 方式2: GitHub 部署（稳定版本）
```bash
# 1. 提交到 GitHub
git add .
git commit -m "你的修改"
git push origin main

# 2. 服务器拉取部署
ssh root@165.154.235.9 "cd /var/www/aiimage && ./scripts/deploy_from_github.sh"
```

适用场景：重要功能上线、版本发布、需要回滚能力

### 📋 环境配置

部署前需要修改环境变量：
- `backend/.env` - 修改 APP_URL 和 CORS_ALLOWED_ORIGINS
- `frontend/.env` - 修改 VITE_API_BASE_URL

## 文档

- [IMAGE_MODELS.md](./IMAGE_MODELS.md) - 图片生成模型详细说明
- [TEST_IMAGE_GENERATION.md](./TEST_IMAGE_GENERATION.md) - 图片生成测试指南
- [DEPLOYMENT.md](./DEPLOYMENT.md) - 部署配置说明
- [TEST_REPORT.md](./TEST_REPORT.md) - 系统测试报告

## 技术栈

### 后端
- PHP 8.1+
- Slim Framework 4
- Guzzle HTTP Client
- OpenRouter API

### 前端
- React 18
- TypeScript
- Vite
- Tailwind CSS 3

## 下一步计划

- [ ] 对话历史保存（数据库）
- [ ] 智能搜索功能
- [ ] 用户配置管理
- [ ] 图片编辑功能（基于现有图片）
- [ ] 批量图片生成
- [ ] 更多 UI 优化

## 许可证

MIT


## 最近更新 🆕

### 2026-03-08: 游客模式上线 🎉
- ✅ 聊天功能无需登录，完全免费
- ✅ 游客可免费生成 3 张图片（24小时）
- ✅ 基于 IP 的配额管理
- ✅ 登录后获得 10 张图片配额
- ✅ 降低使用门槛，提升用户体验

详细信息请查看：`GUEST_MODE_IMPLEMENTATION.md`

### 2026-03-08: 白屏问题修复
- ✅ 修复了 TypeScript 类型导入错误
- ✅ 所有类型现在使用 `import type` 语法
- ✅ 前端构建和运行完全正常
- ✅ 用户认证功能集成完成

详细信息请查看：
- `WHITE_SCREEN_FIX_SUMMARY.md` - 白屏问题修复总结
- `FRONTEND_TROUBLESHOOTING.md` - 前端问题排查指南

### 用户认证系统 🔐
- ✅ 邮箱注册和登录
- ✅ JWT Token 认证
- ✅ BCrypt 密码加密
- ✅ 用户信息管理
- ✅ 安全的会话管理

### 图片生成配额系统 🎟️
- ✅ 每个用户 10 次免费配额
- ✅ 配额实时显示
- ✅ 生成历史记录
- ✅ 防滥用机制

## 快速状态检查

运行以下命令检查系统状态：

```bash
./check_frontend_status.sh
```

这将检查：
- ✅ 前端服务器 (http://localhost:5173/)
- ✅ 后端 API (http://127.0.0.1:8080/)
- ✅ MySQL 数据库
- ✅ TypeScript 编译

## 故障排除

### 前端白屏问题
如果遇到白屏，请按以下步骤操作：

1. **清除浏览器缓存**
   ```
   Mac: Cmd + Shift + R
   Windows/Linux: Ctrl + Shift + R
   ```

2. **检查浏览器控制台**
   - 按 F12 打开开发者工具
   - 查看 Console 标签页

3. **重启前端服务器**
   ```bash
   # 停止当前服务器 (Ctrl+C)
   cd frontend
   npm run dev
   ```

4. **验证构建**
   ```bash
   cd frontend
   npm run build
   ```

详细排查指南：`FRONTEND_TROUBLESHOOTING.md`

### 后端 API 问题
```bash
# 检查后端状态
curl http://127.0.0.1:8080/api/health

# 重启后端
cd backend
php -S 0.0.0.0:8080 -t public
```

### 数据库问题
```bash
# 检查 MySQL 容器
docker ps | grep mysql-aiimage

# 重启 MySQL
docker restart mysql-aiimage

# 查看日志
docker logs mysql-aiimage
```

## 相关文档

### 核心文档
- `USER_GUIDE.md` - 用户使用指南
- `FEATURES.md` - 功能详细说明
- `QUICK_REFERENCE.md` - 快速参考

### 技术文档
- `AUTH_GUIDE.md` - 认证系统指南
- `QUOTA_SYSTEM.md` - 配额系统说明
- `API_PROVIDERS.md` - API 提供商集成
- `DEPLOYMENT.md` - 部署指南

### 集成文档
- `DEEPSEEK_INTEGRATION.md` - DeepSeek API 集成
- `IMAGE_MODELS.md` - 图片生成模型说明

### 问题排查
- `WHITE_SCREEN_FIX_SUMMARY.md` - 白屏问题修复
- `FRONTEND_TROUBLESHOOTING.md` - 前端问题排查
- `TEST_REPORT.md` - 测试报告

## 技术栈

### 前端
- React 19.2.4
- TypeScript 5.9.3
- Vite 8.0.0-beta.16
- Tailwind CSS 3.4.19
- Axios 1.13.6

### 后端
- PHP 8.5.3
- Slim Framework 4.x
- Guzzle HTTP Client
- Firebase JWT
- MySQL 8.0

### 开发工具
- Docker (MySQL)
- Composer 2.9.5
- npm/Node.js

## 系统要求

- PHP 8.1+
- Node.js 18+
- MySQL 8.0+
- Docker (可选，用于 MySQL)
- Composer 2.x
- npm 9+

## 许可证

MIT License

## 支持

如有问题，请查看相关文档或提交 Issue。
