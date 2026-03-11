# 环境变量配置指南

## 文件结构

```
frontend/
  .env              # 当前使用的配置（开发时指向本地）
  .env.local        # 本地开发配置
  .env.remote       # 远程服务器配置

backend/
  .env              # 当前使用的配置（开发时指向本地）
  .env.local        # 本地开发配置
  .env.remote       # 远程服务器配置
```

## 本地开发

### 前端

**使用本地配置**:
```bash
cp frontend/.env.local frontend/.env
npm run dev
```

**配置内容**:
```
VITE_API_BASE_URL=http://127.0.0.1:8080/api
```

### 后端

**使用本地配置**:
```bash
cp backend/.env.local backend/.env
php -S 0.0.0.0:8080 -t public
```

**配置内容**:
- 数据库: 本地 MySQL (127.0.0.1)
- 调试模式: 开启
- CORS: 允许本地开发服务器

## 远程部署

部署脚本会自动处理配置切换：

```bash
bash scripts/quick_deploy.sh
```

**自动执行的步骤**:
1. 复制 `frontend/.env.remote` 到 `frontend/.env`
2. 构建前端
3. 复制 `backend/.env.remote` 到后端包中
4. 上传到远程服务器
5. 在远程服务器上使用 `.env.remote` 作为 `.env`

## 配置差异

### 前端

| 项目 | 本地 | 远程 |
|------|------|------|
| API URL | http://127.0.0.1:8080/api | https://165.154.235.9/api |

### 后端

| 项目 | 本地 | 远程 |
|------|------|------|
| 环境 | development | production |
| 调试 | true | false |
| 数据库主机 | 127.0.0.1 | mysql-aiimage (Docker) |
| 数据库密码 | 空 | root |
| CORS | 本地开发服务器 | 远程服务器 |

## 手动切换配置

### 切换到本地配置

```bash
# 前端
cp frontend/.env.local frontend/.env

# 后端
cp backend/.env.local backend/.env
```

### 切换到远程配置

```bash
# 前端
cp frontend/.env.remote frontend/.env

# 后端
cp backend/.env.remote backend/.env
```

## 添加新的环境变量

1. 在 `.env.local` 和 `.env.remote` 中都添加新变量
2. 确保两个文件中的值都正确
3. 在代码中使用环境变量

### 前端示例

```typescript
const API_URL = import.meta.env.VITE_API_BASE_URL;
```

### 后端示例

```php
$apiKey = $_ENV['OPENROUTER_API_KEY'];
```

## 注意事项

⚠️ **重要**: 
- `.env` 文件不应该提交到 Git（已在 .gitignore 中）
- `.env.local` 和 `.env.remote` 可以提交到 Git
- 敏感信息（API Key）应该在部署时更新
- 不要在版本控制中暴露生产环境的敏感信息

## 快速参考

### 本地开发

```bash
# 1. 设置前端配置
cp frontend/.env.local frontend/.env

# 2. 设置后端配置
cp backend/.env.local backend/.env

# 3. 启动后端
php -S 0.0.0.0:8080 -t backend/public

# 4. 启动前端
cd frontend && npm run dev
```

### 远程部署

```bash
# 一键部署（自动处理配置）
bash scripts/quick_deploy.sh
```

## 故障排除

### Q: 前端显示"离线"状态
A: 检查 `frontend/.env` 中的 `VITE_API_BASE_URL` 是否正确

### Q: 后端连接数据库失败
A: 检查 `backend/.env` 中的数据库配置是否正确

### Q: 部署后 API 返回 CORS 错误
A: 检查 `backend/.env` 中的 `CORS_ALLOWED_ORIGINS` 是否包含前端地址
