# 本地部署指南

## 快速开始

### 一键部署

```bash
# 使脚本可执行
chmod +x scripts/deploy_local.sh scripts/stop_local.sh

# 启动所有服务
bash scripts/deploy_local.sh

# 停止所有服务
bash scripts/stop_local.sh
```

## 详细步骤

### 1. 前置要求

确保已安装以下软件：

```bash
# 检查 PHP
php -v

# 检查 Node.js
node -v
npm -v

# 检查 MySQL (可选)
mysql --version
```

### 2. 安装依赖

#### 后端依赖

```bash
cd backend
composer install
cd ..
```

#### 前端依赖

```bash
cd frontend
npm install
cd ..
```

### 3. 数据库初始化

```bash
# 如果 MySQL 未初始化
cd backend
php init_database.php
cd ..
```

### 4. 启动服务

#### 方式 1: 使用部署脚本（推荐）

```bash
bash scripts/deploy_local.sh
```

这个脚本会自动：
- ✅ 检查依赖
- ✅ 初始化数据库
- ✅ 安装依赖
- ✅ 启动后端服务
- ✅ 启动前端服务
- ✅ 测试服务状态

#### 方式 2: 手动启动

**启动后端**:
```bash
cd backend
php -S 0.0.0.0:8080 -t public
```

**启动前端** (新终端):
```bash
cd frontend
npm run dev
```

### 5. 访问应用

- **前端**: http://localhost:5173/
- **后端 API**: http://127.0.0.1:8080/api/

## 常见问题

### 问题 1: 端口已被占用

**症状**: 启动时提示端口已被占用

**解决方案**:
```bash
# 查找占用端口的进程
lsof -i :8080
lsof -i :5173

# 杀死进程
kill -9 <PID>

# 或使用脚本自动处理
bash scripts/deploy_local.sh
```

### 问题 2: MySQL 连接失败

**症状**: 数据库初始化失败

**解决方案**:
```bash
# 启动 MySQL
# macOS
brew services start mysql

# Linux
sudo systemctl start mysql

# Docker
docker run -d -p 3306:3306 -e MYSQL_ROOT_PASSWORD=password mysql:8.0
```

### 问题 3: npm 依赖安装失败

**症状**: npm install 出错

**解决方案**:
```bash
# 清除缓存
npm cache clean --force

# 重新安装
npm install

# 或使用 yarn
yarn install
```

### 问题 4: PHP 扩展缺失

**症状**: PHP 错误提示缺少扩展

**解决方案**:
```bash
# 检查已安装的扩展
php -m

# 安装缺失的扩展
# macOS
brew install php@8.1

# Linux
sudo apt-get install php8.1-curl php8.1-json php8.1-mysql
```

## 日志查看

### 后端日志

```bash
# 实时查看
tail -f backend.log

# 查看最后 50 行
tail -n 50 backend.log

# 搜索错误
grep ERROR backend.log
```

### 前端日志

```bash
# 实时查看
tail -f frontend.log

# 查看最后 50 行
tail -n 50 frontend.log
```

## 测试服务

### 测试后端 API

```bash
# 健康检查
curl http://127.0.0.1:8080/api/health

# 获取模型列表
curl http://127.0.0.1:8080/api/image/models

# 获取聊天模型
curl http://127.0.0.1:8080/api/models?chat_only=true
```

### 测试前端

```bash
# 检查前端是否运行
curl http://localhost:5173/

# 检查前端资源
curl http://localhost:5173/index.html
```

## 开发工作流

### 1. 启动服务

```bash
bash scripts/deploy_local.sh
```

### 2. 修改代码

- 后端代码修改后自动重新加载
- 前端代码修改后自动热更新

### 3. 测试修改

- 打开浏览器访问 http://localhost:5173/
- 测试功能

### 4. 查看日志

```bash
# 后端日志
tail -f backend.log

# 前端日志
tail -f frontend.log
```

### 5. 停止服务

```bash
bash scripts/stop_local.sh
```

## 性能优化

### 后端优化

```bash
# 启用 OPcache
php -d opcache.enable=1 -S 0.0.0.0:8080 -t public
```

### 前端优化

```bash
# 生产构建
cd frontend
npm run build

# 预览生产构建
npm run preview
```

## 调试技巧

### 启用调试模式

编辑 `backend/.env`:
```env
APP_DEBUG=true
```

### 查看详细错误

```bash
# 后端错误
tail -f backend.log | grep -i error

# 前端错误
# 打开浏览器开发者工具 (F12)
# 查看 Console 标签
```

### 使用 PHP 调试器

```bash
# 安装 Xdebug
pecl install xdebug

# 配置 php.ini
# 添加: zend_extension=xdebug.so
```

## 环境变量配置

### 后端配置 (backend/.env)

```env
# 开发环境
APP_ENV=development
APP_DEBUG=true

# API 配置
OPENROUTER_API_KEY=your_key
DEEPSEEK_API_KEY=your_key
ALIBABA_BAILIAN_API_KEY=your_key

# 数据库配置
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ai_chat_system
DB_USER=root
DB_PASS=

# CORS 配置
CORS_ALLOWED_ORIGINS=http://127.0.0.1:5173,http://localhost:5173
```

### 前端配置 (frontend/.env)

```env
# 开发环境
VITE_API_BASE_URL=http://127.0.0.1:8080/api
```

## 生产构建

### 构建前端

```bash
cd frontend
npm run build
```

输出目录: `frontend/dist/`

### 部署前端

```bash
# 使用 nginx 或其他 web 服务器
# 将 dist 目录作为根目录
```

### 部署后端

```bash
# 使用 PHP 内置服务器（仅用于测试）
php -S 0.0.0.0:8080 -t public

# 或使用 Apache/Nginx + PHP-FPM
```

## 监控和维护

### 检查服务状态

```bash
# 检查 PHP 进程
ps aux | grep "php -S"

# 检查 Node 进程
ps aux | grep "npm run dev"

# 检查端口占用
lsof -i :8080
lsof -i :5173
```

### 清理缓存

```bash
# 清理后端缓存
rm -rf backend/cache/*

# 清理前端缓存
rm -rf frontend/node_modules/.vite
```

### 重启服务

```bash
# 停止所有服务
bash scripts/stop_local.sh

# 启动所有服务
bash scripts/deploy_local.sh
```

## 故障排查清单

- [ ] 检查 PHP 版本 (7.4+)
- [ ] 检查 Node.js 版本 (14+)
- [ ] 检查 MySQL 是否运行
- [ ] 检查端口是否被占用
- [ ] 检查 API Key 是否配置
- [ ] 检查数据库是否初始化
- [ ] 查看后端日志
- [ ] 查看前端日志
- [ ] 检查网络连接
- [ ] 清理缓存重试

## 快速命令参考

```bash
# 启动
bash scripts/deploy_local.sh

# 停止
bash scripts/stop_local.sh

# 查看后端日志
tail -f backend.log

# 查看前端日志
tail -f frontend.log

# 测试后端
curl http://127.0.0.1:8080/api/health

# 测试前端
curl http://localhost:5173/

# 清理进程
bash scripts/stop_local.sh

# 重新启动
bash scripts/deploy_local.sh
```

## 下一步

- 修改代码进行开发
- 测试新功能
- 查看日志调试问题
- 准备部署到服务器

---

**版本**: 1.0  
**最后更新**: 2026年3月10日  
**状态**: ✅ 已完成
