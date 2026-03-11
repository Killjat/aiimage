#!/bin/bash

# 快速部署脚本 - 一键部署到远程服务器
# 用法: bash scripts/quick_deploy.sh [remote_host] [remote_user] [remote_pass]

set -e

# 配置
REMOTE_HOST="${1:-165.154.235.9}"
REMOTE_USER="${2:-root}"
REMOTE_PASS="${3:-kqvfhpsiq@099211}"
REMOTE_PATH="/var/www/aiimage"
LOCAL_DB_NAME="ai_chat_system"

echo "=========================================="
echo "🚀 快速部署开始"
echo "=========================================="
echo "远程服务器: $REMOTE_HOST"
echo ""

# 1. 构建前端
echo "📦 [1/4] 构建前端..."
cp frontend/.env.remote frontend/.env
cd frontend
npm run build > /dev/null 2>&1
cd ..
echo "✅ 前端构建完成"

# 2. 导出本地数据库
echo "💾 [2/4] 导出本地数据库..."
DUMP_FILE="/tmp/db_$(date +%s).sql"
mysqldump -u root $LOCAL_DB_NAME > $DUMP_FILE 2>/dev/null
echo "✅ 数据库已导出"

# 3. 上传并部署
echo "📤 [3/4] 上传代码和数据库..."

# 上传前端
tar czf /tmp/frontend.tar.gz -C frontend dist > /dev/null 2>&1
sshpass -p "$REMOTE_PASS" scp -o StrictHostKeyChecking=no /tmp/frontend.tar.gz $REMOTE_USER@$REMOTE_HOST:/tmp/ > /dev/null 2>&1

# 上传后端代码
tar czf /tmp/backend.tar.gz -C backend src database public composer.json .env.remote > /dev/null 2>&1
sshpass -p "$REMOTE_PASS" scp -o StrictHostKeyChecking=no /tmp/backend.tar.gz $REMOTE_USER@$REMOTE_HOST:/tmp/ > /dev/null 2>&1

# 上传数据库
sshpass -p "$REMOTE_PASS" scp -o StrictHostKeyChecking=no $DUMP_FILE $REMOTE_USER@$REMOTE_HOST:/tmp/db.sql > /dev/null 2>&1

echo "✅ 上传完成"

# 4. 远程部署
echo "🔧 [4/4] 远程部署..."
sshpass -p "$REMOTE_PASS" ssh -o StrictHostKeyChecking=no $REMOTE_USER@$REMOTE_HOST << 'EOFREMOTE'
# 解压前端
cd /var/www/aiimage/frontend
rm -rf dist
tar xzf /tmp/frontend.tar.gz
rm /tmp/frontend.tar.gz

# 解压后端
cd /var/www/aiimage/backend
tar xzf /tmp/backend.tar.gz
# 使用远程配置
mv .env.remote .env
rm /tmp/backend.tar.gz

# 安装后端依赖
composer install --no-dev --quiet 2>/dev/null || true

# 导入数据库
docker exec mysql-aiimage mysql -u root -proot ai_chat_system < /tmp/db.sql 2>/dev/null
rm /tmp/db.sql

echo "✅ 远程部署完成"
EOFREMOTE

# 清理本地临时文件
rm -f /tmp/frontend.tar.gz /tmp/backend.tar.gz $DUMP_FILE

echo ""
echo "=========================================="
echo "✅ 部署完成！"
echo "=========================================="
echo "访问地址: https://$REMOTE_HOST"
echo "API 地址: https://$REMOTE_HOST/api"
echo ""
