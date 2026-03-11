# 快速部署指南

## 一键部署

最简单的方式，一条命令完成所有部署：

```bash
bash scripts/quick_deploy.sh
```

这会自动：
1. 构建前端
2. 导出本地数据库
3. 上传代码和数据库到远程
4. 在远程部署并导入数据库

**耗时**: 约 2-3 分钟

## 自定义部署

如果需要指定不同的服务器或凭证：

```bash
bash scripts/quick_deploy.sh 165.154.235.9 root kqvfhpsiq@099211
```

参数说明：
- 第1个参数: 远程服务器IP (默认: 165.154.235.9)
- 第2个参数: SSH用户名 (默认: root)
- 第3个参数: SSH密码 (默认: kqvfhpsiq@099211)

## 部署前检查清单

部署前确保以下条件满足：

- [ ] 本地数据库运行正常 (`mysql -u root ai_chat_system -e "SELECT 1"`)
- [ ] 前端代码已修改完成
- [ ] 后端代码已修改完成
- [ ] `.env` 文件已配置正确
- [ ] 远程服务器可访问
- [ ] 远程服务器已安装 Docker 和 MySQL

## 部署后验证

部署完成后，验证以下内容：

### 1. 检查前端
```bash
curl -s https://165.154.235.9 | grep -o '<title>.*</title>'
```
应该返回: `<title>Cyberstroll - 多模态AI聊天</title>`

### 2. 检查后端 API
```bash
curl -s https://165.154.235.9/api/health | jq .
```
应该返回: `{"status":"ok"}`

### 3. 检查数据库
```bash
sshpass -p "kqvfhpsiq@099211" ssh root@165.154.235.9 \
  "docker exec mysql-aiimage mysql -u root -proot ai_chat_system -e 'SELECT COUNT(*) FROM image_gallery;'"
```
应该显示图片库中的图片数量

### 4. 访问应用
在浏览器中访问: https://165.154.235.9

## 常见问题

### Q: 部署失败，提示 "sshpass: command not found"
A: 需要安装 sshpass
```bash
# macOS
brew install sshpass

# Ubuntu/Debian
sudo apt-get install sshpass

# CentOS/RHEL
sudo yum install sshpass
```

### Q: 前端显示"离线"状态
A: 这是正常的，因为前端通过 HTTPS 访问后端。检查浏览器控制台是否有 CORS 错误。

### Q: 数据库导入失败
A: 确保远程 MySQL 容器正在运行：
```bash
sshpass -p "kqvfhpsiq@099211" ssh root@165.154.235.9 "docker ps | grep mysql"
```

### Q: 如何只部署前端或后端？

**只部署前端**:
```bash
cd frontend && npm run build
tar czf /tmp/frontend.tar.gz dist
sshpass -p "kqvfhpsiq@099211" scp /tmp/frontend.tar.gz root@165.154.235.9:/tmp/
sshpass -p "kqvfhpsiq@099211" ssh root@165.154.235.9 \
  "cd /var/www/aiimage/frontend && rm -rf dist && tar xzf /tmp/frontend.tar.gz"
```

**只部署后端**:
```bash
tar czf /tmp/backend.tar.gz -C backend src database public composer.json
sshpass -p "kqvfhpsiq@099211" scp /tmp/backend.tar.gz root@165.154.235.9:/tmp/
sshpass -p "kqvfhpsiq@099211" ssh root@165.154.235.9 \
  "cd /var/www/aiimage/backend && tar xzf /tmp/backend.tar.gz && composer install --no-dev"
```

**只同步数据库**:
```bash
mysqldump -u root ai_chat_system > /tmp/db.sql
sshpass -p "kqvfhpsiq@099211" scp /tmp/db.sql root@165.154.235.9:/tmp/
sshpass -p "kqvfhpsiq@099211" ssh root@165.154.235.9 \
  "docker exec mysql-aiimage mysql -u root -proot ai_chat_system < /tmp/db.sql"
```

## 环境变量配置

### 前端 (.env)
```
VITE_API_BASE_URL=https://165.154.235.9/api
```

### 后端 (.env)
```
DB_HOST=mysql-aiimage
DB_USER=root
DB_PASS=root
DB_NAME=ai_chat_system
OPENROUTER_API_KEY=your_key
ALIBABA_BAILIAN_API_KEY=your_key
```

## 远程服务器信息

- **IP**: 165.154.235.9
- **SSH用户**: root
- **SSH密码**: kqvfhpsiq@099211
- **部署路径**: /var/www/aiimage
- **前端路径**: /var/www/aiimage/frontend/dist
- **后端路径**: /var/www/aiimage/backend
- **数据库**: Docker 容器 (mysql-aiimage)
- **数据库用户**: root
- **数据库密码**: root

## 架构说明

```
┌─────────────────────────────────────────┐
│         HTTPS (443)                     │
│  https://165.154.235.9                  │
│                                         │
│  ┌─────────────────────────────────┐   │
│  │  Nginx (反向代理)               │   │
│  │  - 前端: /var/www/.../dist      │   │
│  │  - API: 代理到 127.0.0.1:8080   │   │
│  └─────────────────────────────────┘   │
│                                         │
│  ┌─────────────────────────────────┐   │
│  │  PHP 8080 (后端 API)            │   │
│  │  - Slim Framework               │   │
│  │  - 连接 MySQL                   │   │
│  └─────────────────────────────────┘   │
│                                         │
│  ┌─────────────────────────────────┐   │
│  │  Docker MySQL (3306)            │   │
│  │  - 数据库: ai_chat_system       │   │
│  │  - 用户: root / root            │   │
│  └─────────────────────────────────┘   │
└─────────────────────────────────────────┘
```

## 性能优化建议

1. **前端缓存**: 静态资源设置 1 年过期时间
2. **API 超时**: 图片生成设置 120 秒超时
3. **数据库**: 使用 Docker 容器，便于扩展
4. **HTTPS**: 所有流量都通过 HTTPS 加密

## 下次部署

下次部署时，只需运行：

```bash
bash scripts/quick_deploy.sh
```

所有步骤都会自动完成，包括：
- ✅ 前端构建
- ✅ 代码上传
- ✅ 数据库同步
- ✅ 依赖安装
- ✅ 部署完成

**总耗时**: 2-3 分钟
