# 项目结构说明

## 📁 目录结构

```
aiimage/
│
├── 📂 backend/                  # 后端服务（PHP）
│   ├── 📂 src/
│   │   ├── 📂 Controllers/      # 控制器层
│   │   │   ├── AuthController.php
│   │   │   ├── ChatController.php
│   │   │   ├── ImageController.php
│   │   │   └── ModelsController.php
│   │   ├── 📂 Services/         # 服务层
│   │   │   ├── AIServiceManager.php
│   │   │   ├── AuthService.php
│   │   │   ├── DeepSeekService.php
│   │   │   ├── OpenRouterService.php
│   │   │   ├── QuotaService.php
│   │   │   └── DeepSeekService.php
│   │   └── 📂 Database/         # 数据库层
│   │       └── Database.php
│   ├── 📂 database/
│   │   └── schema.sql           # 数据库结构
│   ├── 📂 public/
│   │   └── index.php            # 入口文件
│   ├── .env.example             # 环境变量模板
│   └── composer.json            # PHP依赖
│
├── 📂 frontend/                 # 前端应用（React + TypeScript）
│   ├── 📂 src/
│   │   ├── 📂 components/       # UI组件
│   │   │   ├── ChatInput.tsx
│   │   │   ├── ChatMessage.tsx
│   │   │   ├── ImageGenerator.tsx
│   │   │   ├── Login.tsx
│   │   │   ├── ModelSelector.tsx
│   │   │   ├── QuotaDisplay.tsx
│   │   │   └── Register.tsx
│   │   ├── 📂 services/         # API服务
│   │   │   └── ApiClient.ts
│   │   ├── App.tsx              # 主应用
│   │   ├── AppWithAuth.tsx      # 带认证的应用
│   │   └── types.ts             # 类型定义
│   ├── 📂 dist/                 # 构建输出
│   ├── .env.example             # 环境变量模板
│   └── package.json             # NPM依赖
│
├── 📂 scripts/                  # 🆕 脚本目录
│   ├── 📄 README.md             # 脚本说明文档
│   │
│   ├── 🚀 部署脚本
│   │   ├── deploy_remote.sh.example      # 远程部署模板
│   │   ├── setup_https.sh.example        # HTTPS配置（域名）
│   │   └── setup_https_ip.sh.example     # HTTPS配置（IP）
│   │
│   ├── 🔧 系统管理
│   │   ├── start_system.sh               # 启动系统
│   │   ├── stop_system.sh                # 停止系统
│   │   ├── check_system_status.sh        # 状态检查
│   │   └── check_frontend_status.sh      # 前端状态
│   │
│   └── 🧪 测试脚本
│       ├── test_auth_api.sh              # 认证测试
│       ├── test_guest_mode.sh            # 游客模式测试
│       └── test_quota_system.sh          # 配额系统测试
│
├── 📚 文档
│   ├── README.md                # 项目说明
│   ├── DEPLOYMENT_GUIDE.md      # 部署指南
│   ├── API_PROVIDERS.md         # API提供商说明
│   ├── AUTH_GUIDE.md            # 认证指南
│   ├── USER_GUIDE.md            # 用户指南
│   ├── DOCKER_MYSQL_SETUP.md    # MySQL配置
│   ├── QUICK_START.md           # 快速开始
│   └── QUICK_REFERENCE.md       # 快速参考
│
└── 🔧 配置文件
    ├── .gitignore               # Git忽略配置
    ├── nginx.conf               # Nginx配置
    └── PROJECT_STRUCTURE.md     # 本文件
```

## 📝 文件说明

### 核心目录

| 目录 | 说明 | 技术栈 |
|------|------|--------|
| `backend/` | 后端API服务 | PHP 8.0+, Slim Framework |
| `frontend/` | 前端界面 | React 18, TypeScript, Vite |
| `scripts/` | 运维脚本 | Bash Shell |

### 重要文件

| 文件 | 说明 |
|------|------|
| `backend/.env` | 后端环境变量（包含API密钥，不提交） |
| `frontend/.env` | 前端环境变量（包含API地址，不提交） |
| `backend/database/schema.sql` | 数据库结构定义 |
| `scripts/README.md` | 脚本使用说明 |

## 🔒 安全文件

以下文件包含敏感信息，已在 `.gitignore` 中配置忽略：

```
backend/.env
frontend/.env
scripts/deploy_remote.sh
scripts/setup_https.sh
scripts/setup_https_ip.sh
```

使用前请复制对应的 `.example` 文件并填写实际配置。

## 🚀 快速导航

- **开始使用**: [README.md](README.md)
- **部署系统**: [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)
- **脚本说明**: [scripts/README.md](scripts/README.md)
- **用户指南**: [USER_GUIDE.md](USER_GUIDE.md)
- **API配置**: [API_PROVIDERS.md](API_PROVIDERS.md)

## 📊 代码统计

```
后端（PHP）:
- Controllers: 4 个
- Services: 6 个
- 总行数: ~2000 行

前端（TypeScript/React）:
- Components: 7 个
- Services: 1 个
- 总行数: ~1500 行

脚本（Bash）:
- 部署脚本: 3 个
- 管理脚本: 4 个
- 测试脚本: 4 个
```

## 🔄 工作流程

### 开发流程
```
1. 修改代码
2. 本地测试 (./scripts/start_system.sh)
3. 运行测试 (./scripts/test_*.sh)
4. 提交代码 (git commit)
```

### 部署流程
```
1. 配置脚本 (cp scripts/*.example scripts/*)
2. 部署代码 (./scripts/deploy_remote.sh)
3. 配置HTTPS (./scripts/setup_https_ip.sh)
4. 验证部署 (curl https://your-server/api/health)
```

## 📦 依赖管理

### 后端依赖
```bash
cd backend
composer install
```

### 前端依赖
```bash
cd frontend
npm install
```

## 🛠️ 开发工具

推荐的开发工具：
- **IDE**: VS Code, PhpStorm
- **API测试**: Postman, curl
- **数据库**: MySQL Workbench, phpMyAdmin
- **版本控制**: Git

## 📈 项目规模

- **总文件数**: ~100+
- **代码行数**: ~3500+
- **支持模型**: 495+
- **API提供商**: 3 个
