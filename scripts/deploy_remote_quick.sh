#!/bin/bash

# 远程部署脚本 - 快速更新
# 用于部署新增功能到远程服务器

set -e

REMOTE_HOST="165.154.235.9"
REMOTE_USER="root"
REMOTE_PATH="/var/www/aiimage"
SSH_KEY="$HOME/.ssh/id_ed25519"

echo "=========================================="
echo "开始远程部署..."
echo "=========================================="

# 1. 同步前端代码
echo ""
echo "1️⃣  同步前端代码..."
rsync -avz -e "ssh -i $SSH_KEY -o StrictHostKeyChecking=no" --delete \
  --exclude='node_modules' \
  --exclude='.env' \
  --exclude='dist' \
  frontend/ "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/frontend/"

# 2. 同步后端代码
echo ""
echo "2️⃣  同步后端代码..."
rsync -avz -e "ssh -i $SSH_KEY -o StrictHostKeyChecking=no" --delete \
  --exclude='vendor' \
  --exclude='.env' \
  --exclude='database/aiimage.db' \
  --exclude='cache/*' \
  backend/ "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/backend/"

# 3. 在远程服务器上构建前端
echo ""
echo "3️⃣  构建前端..."
ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
cd /var/www/aiimage/frontend
npm install --legacy-peer-deps
npm run build
echo "✅ 前端构建完成"
EOF

# 4. 验证部署
echo ""
echo "4️⃣  验证部署..."
ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
echo "检查后端API..."
curl -s http://127.0.0.1:8080/api/health | grep -q "ok" && echo "✅ 后端API正常" || echo "❌ 后端API异常"

echo "检查前端构建..."
[ -d /var/www/aiimage/frontend/dist ] && echo "✅ 前端构建成功" || echo "❌ 前端构建失败"

echo "检查图片库API..."
curl -s http://127.0.0.1:8080/api/gallery/public?page=1&limit=1 | grep -q "success" && echo "✅ 图片库API正常" || echo "❌ 图片库API异常"
EOF

echo ""
echo "=========================================="
echo "✅ 远程部署完成！"
echo "=========================================="
echo ""
echo "访问地址:"
echo "  前端: https://165.154.235.9"
echo "  后端: https://165.154.235.9/api"
echo ""
