#!/bin/bash

# 快速修复远程服务器的环境变量
# 用途：添加缺失的 ALIBABA_BAILIAN_API_KEY 到远程服务器

set -e

echo "🔧 修复远程服务器环境变量..."
echo "================================================"

# 远程服务器信息
REMOTE_USER="root"
REMOTE_HOST="165.154.235.9"
REMOTE_PROJECT_DIR="/var/www/aiimage"

# 本地 .env 文件路径
LOCAL_BACKEND_ENV="backend/.env"
LOCAL_FRONTEND_ENV="frontend/.env"

# 检查本地文件是否存在
if [ ! -f "$LOCAL_BACKEND_ENV" ]; then
    echo "❌ 错误: 本地 $LOCAL_BACKEND_ENV 不存在"
    exit 1
fi

echo "📤 同步后端环境变量到远程服务器..."
scp "$LOCAL_BACKEND_ENV" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PROJECT_DIR/backend/.env"
echo "✅ 后端环境变量已同步"

if [ -f "$LOCAL_FRONTEND_ENV" ]; then
    echo "📤 同步前端环境变量到远程服务器..."
    scp "$LOCAL_FRONTEND_ENV" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PROJECT_DIR/frontend/.env"
    echo "✅ 前端环境变量已同步"
fi

# 远程重启后端服务
echo "🔄 重启远程后端服务..."
ssh "$REMOTE_USER@$REMOTE_HOST" "systemctl restart aiimage-backend"
echo "✅ 后端服务已重启"

# 验证
echo "🏥 验证服务状态..."
ssh "$REMOTE_USER@$REMOTE_HOST" "systemctl status aiimage-backend --no-pager -l | head -3"

echo ""
echo "================================================"
echo "✅ 环境变量修复完成！"
echo "================================================"
echo ""
echo "🌐 访问地址:"
echo "   前端: https://165.154.235.9"
echo "   后端: http://165.154.235.9/api"
echo ""
