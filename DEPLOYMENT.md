# 部署指南

## 重要说明

本系统支持前后端分离部署，前端和后端可以部署在不同的服务器上。所有的 URL 配置都通过环境变量管理，没有硬编码。

## 本地开发环境

### 后端配置
```bash
cd backend
cp .env.example .env
```

编辑 `backend/.env`：
```env
OPENROUTER_API_KEY=your_api_key_here
APP_URL=http://127.0.0.1:8080
CORS_ALLOWED_ORIGINS=http://127.0.0.1:5173,http://127.0.0.1:5174
```

### 前端配置
```bash
cd frontend
cp .env.example .env
```

编辑 `frontend/.env`：
```env
VITE_API_BASE_URL=http://127.0.0.1:8080/api
```

## 服务器部署

### 场景 1：前后端在同一台服务器

**后端配置** (`backend/.env`)：
```env
OPENROUTER_API_KEY=your_api_key_here
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-server-ip:8080
CORS_ALLOWED_ORIGINS=http://your-server-ip:5173
```

**前端配置** (`frontend/.env`)：
```env
VITE_API_BASE_URL=http://your-server-ip:8080/api
```

### 场景 2：前后端在不同服务器（推荐）

**后端服务器** (例如: 192.168.1.100)

`backend/.env`：
```env
OPENROUTER_API_KEY=your_api_key_here
APP_ENV=production
APP_DEBUG=false
APP_URL=http://192.168.1.100:8080
# 允许前端服务器访问
CORS_ALLOWED_ORIGINS=http://192.168.1.200:5173,https://your-domain.com
```

**前端服务器** (例如: 192.168.1.200)

`frontend/.env`：
```env
# 指向后端服务器地址
VITE_API_BASE_URL=http://192.168.1.100:8080/api
```

### 场景 3：使用域名部署

**后端服务器**

`backend/.env`：
```env
OPENROUTER_API_KEY=your_api_key_here
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.your-domain.com
CORS_ALLOWED_ORIGINS=https://your-domain.com,https://www.your-domain.com
```

**前端服务器**

`frontend/.env`：
```env
VITE_API_BASE_URL=https://api.your-domain.com/api
```

### 场景 4：使用 Nginx 反向代理（同域名不同路径）

Nginx 配置：
```nginx
server {
    listen 80;
    server_name your-domain.com;
    
    # 前端
    location / {
        root /path/to/frontend/dist;
        try_files $uri $uri/ /index.html;
    }
    
    # 后端 API 反向代理
    location /api {
        proxy_pass http://127.0.0.1:8080/api;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

**后端配置** (`backend/.env`)：
```env
OPENROUTER_API_KEY=your_api_key_here
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-domain.com
CORS_ALLOWED_ORIGINS=http://your-domain.com
```

**前端配置** (`frontend/.env`)：
```env
# 使用相对路径，自动使用当前域名
VITE_API_BASE_URL=/api
```

## 部署步骤

### 1. 后端部署

```bash
cd backend

# 安装依赖（生产环境）
composer install --no-dev --optimize-autoloader

# 配置环境变量
cp .env.example .env
nano .env  # 编辑配置

# 启动服务
php -S 0.0.0.0:8080 -t public
```

生产环境建议使用 PHP-FPM + Nginx 或 Apache。

### 2. 前端部署

```bash
cd frontend

# 安装依赖
npm install

# 配置环境变量
cp .env.example .env
nano .env  # 编辑配置

# 构建生产版本
npm run build
```

构建后的文件在 `frontend/dist/` 目录，可以用任何 Web 服务器托管。

### 3. 使用 Nginx 托管前端（推荐）

```nginx
server {
    listen 80;
    server_name your-domain.com;
    
    root /path/to/frontend/dist;
    index index.html;
    
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    # 启用 gzip 压缩
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
}
```

### 4. 使用 HTTPS（强烈推荐）

安装 SSL 证书（例如使用 Let's Encrypt）：

```bash
sudo certbot --nginx -d your-domain.com -d api.your-domain.com
```

然后更新配置：

**后端** (`backend/.env`)：
```env
APP_URL=https://api.your-domain.com
CORS_ALLOWED_ORIGINS=https://your-domain.com
```

**前端** (`frontend/.env`)：
```env
VITE_API_BASE_URL=https://api.your-domain.com/api
```

## 配置说明

### 后端环境变量

| 变量 | 必填 | 说明 | 示例 |
|------|------|------|------|
| OPENROUTER_API_KEY | ✅ | OpenRouter API 密钥 | sk-or-v1-xxx |
| OPENROUTER_API_URL | ✅ | OpenRouter API 地址 | https://openrouter.ai/api/v1 |
| APP_URL | ✅ | 后端服务地址（用于 OpenRouter Referer） | http://127.0.0.1:8080 |
| CORS_ALLOWED_ORIGINS | ✅ | 允许的前端来源（逗号分隔） | http://127.0.0.1:5173 |
| APP_ENV | ❌ | 运行环境 | development / production |
| APP_DEBUG | ❌ | 调试模式 | true / false |

### 前端环境变量

| 变量 | 必填 | 说明 | 示例 |
|------|------|------|------|
| VITE_API_BASE_URL | ✅ | 后端 API 地址 | http://127.0.0.1:8080/api |

## 环境变量验证

系统会在启动时验证必填的环境变量：

- **后端**：如果 `APP_URL` 未配置，会抛出异常并记录错误日志
- **前端**：如果 `VITE_API_BASE_URL` 未配置，会在浏览器控制台显示错误

## 跨域配置说明

后端的 `CORS_ALLOWED_ORIGINS` 必须包含前端的完整地址：

```env
# ✅ 正确
CORS_ALLOWED_ORIGINS=http://192.168.1.200:5173,https://your-domain.com

# ❌ 错误（缺少端口）
CORS_ALLOWED_ORIGINS=http://192.168.1.200

# ❌ 错误（协议不匹配）
CORS_ALLOWED_ORIGINS=https://your-domain.com  # 前端使用 http
```

## 常见问题

### 1. 前端无法连接后端

检查：
- 后端服务是否启动
- `VITE_API_BASE_URL` 是否正确
- 防火墙是否开放端口
- CORS 配置是否包含前端地址

### 2. OpenRouter API 调用失败

检查：
- `OPENROUTER_API_KEY` 是否正确
- `APP_URL` 是否配置（OpenRouter 需要 Referer）
- 网络是否可以访问 OpenRouter

### 3. 跨域错误

检查：
- `CORS_ALLOWED_ORIGINS` 是否包含前端地址
- 协议（http/https）是否匹配
- 端口号是否正确

## 安全建议

1. ✅ 生产环境设置 `APP_DEBUG=false`
2. ✅ 使用 HTTPS 保护 API 密钥传输
3. ✅ 定期更新依赖包
4. ✅ 配置防火墙规则，只开放必要端口
5. ✅ 设置合理的 CORS 策略，不要使用 `*`
6. ✅ 不要将 `.env` 文件提交到版本控制
7. ✅ 使用环境变量管理敏感信息
8. ✅ 定期轮换 API 密钥

## 监控和日志

### 后端日志

PHP 错误日志位置：
- 开发环境：终端输出
- 生产环境：`/var/log/php-fpm/error.log` 或 Nginx 错误日志

### 前端日志

浏览器控制台会显示：
- API 请求错误
- 环境变量配置错误
- 网络连接问题

## 性能优化

1. 启用 Nginx gzip 压缩
2. 配置浏览器缓存
3. 使用 CDN 加速静态资源
4. 启用 HTTP/2
5. 使用 PHP OPcache


