# 阿里巴巴模型集成部署指南

## 部署前检查

### 1. 环境准备

```bash
# 检查 PHP 版本
php -v  # 需要 PHP 7.4+

# 检查 Node.js 版本
node -v  # 需要 Node.js 14+

# 检查依赖
cd backend && composer install
cd ../frontend && npm install
```

### 2. 配置检查

```bash
# 检查后端 .env 文件
cat backend/.env

# 必需的环境变量：
# ALIBABA_API_KEY=your_key
# ALIBABA_API_URL=https://dashscope.aliyuncs.com/api/v1
# OPENROUTER_API_KEY=your_key
# OPENROUTER_API_URL=https://openrouter.ai/api/v1
```

### 3. 数据库检查

```bash
# 确保数据库已初始化
php backend/init_database.php
```

## 部署步骤

### 步骤 1: 后端部署

```bash
# 1. 进入后端目录
cd backend

# 2. 安装依赖
composer install

# 3. 验证集成
php test_alibaba_integration.php

# 4. 启动后端服务
php -S localhost:8000 -t public/
```

**预期输出**:
```
✅ 集成测试完成！
- 找到 13 个模型
- 模型识别正常
- 6 个模型已通过测试
```

### 步骤 2: 前端部署

```bash
# 1. 进入前端目录
cd frontend

# 2. 安装依赖
npm install

# 3. 构建项目
npm run build

# 4. 启动开发服务器（开发环境）
npm run dev

# 或启动生产服务器（生产环境）
npm run preview
```

### 步骤 3: 验证集成

#### 3.1 验证后端 API

```bash
# 获取所有图片生成模型
curl http://localhost:8000/api/image/models

# 预期响应包含：
# - OpenRouter 模型
# - 阿里巴巴模型（带 "alibaba-" 前缀）
```

#### 3.2 验证前端

1. 打开浏览器访问 `http://localhost:5173`
2. 打开图片生成器
3. 检查模型下拉菜单
4. 应该看到：
   - OpenRouter 模型（标签：推荐、快速、专业等）
   - 阿里巴巴模型（标签：阿里）

#### 3.3 测试图片生成

1. 选择阿里巴巴模型（如 "万相 2.6"）
2. 输入提示词（如 "一只可爱的猫"）
3. 点击生成
4. 等待图片生成完成

## 生产环境部署

### 使用 Docker

```dockerfile
# backend/Dockerfile
FROM php:8.1-fpm

WORKDIR /app

# 安装依赖
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libcurl4-openssl-dev

# 安装 Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 复制代码
COPY . .

# 安装 PHP 依赖
RUN composer install --no-dev

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public/"]
```

```dockerfile
# frontend/Dockerfile
FROM node:18-alpine

WORKDIR /app

COPY package*.json ./

RUN npm install

COPY . .

RUN npm run build

EXPOSE 5173

CMD ["npm", "run", "preview"]
```

### 使用 Docker Compose

```yaml
version: '3.8'

services:
  backend:
    build: ./backend
    ports:
      - "8000:8000"
    environment:
      - ALIBABA_API_KEY=${ALIBABA_API_KEY}
      - OPENROUTER_API_KEY=${OPENROUTER_API_KEY}
    volumes:
      - ./backend:/app

  frontend:
    build: ./frontend
    ports:
      - "5173:5173"
    environment:
      - VITE_API_BASE_URL=http://localhost:8000/api
    depends_on:
      - backend

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
    depends_on:
      - backend
      - frontend
```

### 部署命令

```bash
# 构建镜像
docker-compose build

# 启动服务
docker-compose up -d

# 查看日志
docker-compose logs -f

# 停止服务
docker-compose down
```

## 验证部署

### 1. 检查服务状态

```bash
# 检查后端
curl http://localhost:8000/api/image/models

# 检查前端
curl http://localhost:5173
```

### 2. 检查日志

```bash
# 后端日志
tail -f backend/logs/error.log

# 前端日志
docker-compose logs frontend
```

### 3. 性能测试

```bash
# 测试模型列表加载时间
time curl http://localhost:8000/api/image/models

# 测试图片生成
curl -X POST http://localhost:8000/api/image/generate \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "一只可爱的猫",
    "model": "alibaba-wan2.6-t2i"
  }'
```

## 故障排查

### 问题 1: 模型列表为空

**症状**: 前端看不到任何模型

**解决方案**:
```bash
# 1. 检查 API 响应
curl http://localhost:8000/api/image/models

# 2. 检查后端日志
tail -f backend/logs/error.log

# 3. 检查 API Key 配置
cat backend/.env | grep API_KEY

# 4. 重启后端服务
```

### 问题 2: 图片生成失败

**症状**: 点击生成后出现错误

**解决方案**:
```bash
# 1. 检查提示词长度
# 阿里巴巴模型限制：最多 2100 字符

# 2. 检查 API Key 有效性
# 确保 ALIBABA_API_KEY 正确

# 3. 查看错误日志
tail -f backend/logs/error.log

# 4. 测试 API 直接调用
curl -X POST http://localhost:8000/api/image/generate \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "prompt": "test",
    "model": "alibaba-wan2.6-t2i"
  }'
```

### 问题 3: 前端无法连接后端

**症状**: 前端显示网络错误

**解决方案**:
```bash
# 1. 检查后端是否运行
curl http://localhost:8000

# 2. 检查 CORS 配置
# 确保 nginx.conf 中配置了正确的 CORS 头

# 3. 检查防火墙
# 确保端口 8000 未被阻止

# 4. 检查前端环境变量
cat frontend/.env | grep API_BASE_URL
```

## 监控和维护

### 1. 日志监控

```bash
# 实时监控后端日志
tail -f backend/logs/error.log

# 实时监控前端日志
docker-compose logs -f frontend
```

### 2. 性能监控

```bash
# 监控 API 响应时间
watch -n 1 'curl -w "Time: %{time_total}s\n" http://localhost:8000/api/image/models'

# 监控系统资源
docker stats
```

### 3. 定期检查

```bash
# 每天检查一次
0 0 * * * /path/to/check_health.sh

# 每周备份一次
0 0 * * 0 /path/to/backup.sh
```

## 回滚计划

如果部署出现问题，可以按以下步骤回滚：

```bash
# 1. 停止当前服务
docker-compose down

# 2. 恢复之前的版本
git checkout HEAD~1

# 3. 重新构建和启动
docker-compose build
docker-compose up -d

# 4. 验证服务
curl http://localhost:8000/api/image/models
```

## 性能优化

### 1. 缓存优化

```php
// 在 AIServiceManager 中添加缓存
$cacheKey = 'alibaba_models_' . md5(json_encode($options));
if ($cache->has($cacheKey)) {
    return $cache->get($cacheKey);
}
```

### 2. 数据库优化

```sql
-- 添加索引
CREATE INDEX idx_model_id ON image_generations(model);
CREATE INDEX idx_user_model ON image_generations(user_id, model);
```

### 3. 前端优化

```typescript
// 使用 React.memo 避免不必要的重新渲染
const ModelSelector = React.memo(({ models, onSelect }) => {
  // ...
});
```

## 安全检查

- [x] API Key 已加密存储
- [x] 用户输入已验证
- [x] SQL 注入已防护
- [x] CORS 已配置
- [x] HTTPS 已启用（生产环境）

## 部署完成检查表

- [ ] 后端服务正常运行
- [ ] 前端服务正常运行
- [ ] API 端点可访问
- [ ] 模型列表加载正常
- [ ] 图片生成功能正常
- [ ] 日志记录正常
- [ ] 性能指标达标
- [ ] 安全检查通过

## 联系支持

如有问题，请查看：
- [集成文档](./ALIBABA_OPENROUTER_INTEGRATION.md)
- [快速开始](./ALIBABA_MODELS_QUICK_START.md)
- [故障排查](./INTEGRATION_SUMMARY.md)

---

**部署指南版本**: 1.0  
**最后更新**: 2026年3月10日  
**状态**: ✅ 已准备好部署
