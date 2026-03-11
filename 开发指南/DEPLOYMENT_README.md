# 部署指南

## 快速开始

### 一键部署（推荐）

```bash
bash scripts/quick_deploy.sh
```

这会自动完成所有部署步骤，耗时约 2-3 分钟。

### 完整部署（包含测试）

```bash
bash scripts/deploy_to_remote.sh
```

这会部署代码、同步数据库，并运行完整的测试套件。

## 部署脚本说明

### 1. `scripts/quick_deploy.sh` - 快速部署
- **用途**: 最快的部署方式
- **耗时**: 2-3 分钟
- **功能**:
  - 构建前端
  - 导出本地数据库
  - 上传代码和数据库
  - 远程部署和导入

**用法**:
```bash
bash scripts/quick_deploy.sh [host] [user] [pass]
```

**示例**:
```bash
# 使用默认配置
bash scripts/quick_deploy.sh

# 自定义服务器
bash scripts/quick_deploy.sh 165.154.235.9 root kqvfhpsiq@099211
```

### 2. `scripts/deploy_to_remote.sh` - 完整部署
- **用途**: 完整部署 + 测试
- **耗时**: 3-5 分钟
- **功能**:
  - 构建前端
  - 导出本地数据库
  - 上传代码和数据库
  - 远程部署和导入
  - 运行测试套件

**用法**:
```bash
bash scripts/deploy_to_remote.sh [host] [user] [pass]
```

### 3. `scripts/test_deployment.sh` - 快速测试
- **用途**: 验证部署是否成功
- **耗时**: 10-20 秒
- **功能**:
  - 测试前端访问
  - 测试 API 端点
  - 显示测试结果

**用法**:
```bash
bash scripts/test_deployment.sh [host]
```

**示例**:
```bash
bash scripts/test_deployment.sh 165.154.235.9
```

## 部署前准备

### 1. 安装依赖

```bash
# macOS
brew install sshpass

# Ubuntu/Debian
sudo apt-get install sshpass

# CentOS/RHEL
sudo yum install sshpass
```

### 2. 检查本地环境

```bash
# 检查 MySQL
mysql -u root ai_chat_system -e "SELECT 1"

# 检查 Node.js
node --version

# 检查 npm
npm --version
```

### 3. 检查远程服务器

```bash
# 测试 SSH 连接
sshpass -p "kqvfhpsiq@099211" ssh root@165.154.235.9 "echo OK"

# 检查 Docker
sshpass -p "kqvfhpsiq@099211" ssh root@165.154.235.9 "docker ps"
```

## 部署步骤

### 方式 1: 快速部署（推荐）

```bash
# 1. 确保代码已提交
git add .
git commit -m "Update code"

# 2. 运行快速部署
bash scripts/quick_deploy.sh

# 3. 验证部署
bash scripts/test_deployment.sh
```

### 方式 2: 完整部署

```bash
# 1. 运行完整部署（包含测试）
bash scripts/deploy_to_remote.sh

# 2. 查看测试结果
# 脚本会自动显示测试结果
```

### 方式 3: 手动部署

如果需要更细粒度的控制，可以分步部署：

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

## 部署后验证

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

### 4. 访问应用
在浏览器中访问: https://165.154.235.9

## 常见问题

### Q: 部署失败，提示 "sshpass: command not found"
A: 需要安装 sshpass（见上面的"安装依赖"部分）

### Q: 前端显示"离线"状态
A: 这是正常的。检查浏览器控制台是否有错误。通常是因为 HTTPS 访问 HTTP 被浏览器阻止。

### Q: 数据库导入失败
A: 确保远程 MySQL 容器正在运行：
```bash
sshpass -p "kqvfhpsiq@099211" ssh root@165.154.235.9 "docker ps | grep mysql"
```

### Q: 如何回滚到上一个版本？
A: 保存数据库备份：
```bash
mysqldump -u root ai_chat_system > backup_$(date +%Y%m%d_%H%M%S).sql
```

然后从备份恢复：
```bash
mysql -u root ai_chat_system < backup_20260311_120000.sql
```

## 环境配置

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

| 项目 | 值 |
|------|-----|
| IP | 165.154.235.9 |
| SSH 用户 | root |
| SSH 密码 | kqvfhpsiq@099211 |
| 部署路径 | /var/www/aiimage |
| 前端路径 | /var/www/aiimage/frontend/dist |
| 后端路径 | /var/www/aiimage/backend |
| 数据库容器 | mysql-aiimage |
| 数据库用户 | root |
| 数据库密码 | root |

## 性能指标

| 操作 | 耗时 |
|------|------|
| 前端构建 | 2-3 秒 |
| 数据库导出 | 1-2 秒 |
| 代码上传 | 5-10 秒 |
| 远程部署 | 10-15 秒 |
| 数据库导入 | 5-10 秒 |
| **总耗时** | **2-3 分钟** |

## 下次部署

下次部署时，只需运行：

```bash
bash scripts/quick_deploy.sh
```

所有步骤都会自动完成！
