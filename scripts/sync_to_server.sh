#!/bin/bash

# 直接从本地同步代码到远程服务器
# 不依赖 GitHub，使用 rsync 直接传输
# 适用场景：快速开发、测试、bug修复

set -e

# 配置
REMOTE_HOST="165.154.235.9"
REMOTE_USER="root"
REMOTE_PATH="/var/www/aiimage"
LOCAL_PATH="$(cd "$(dirname "$0")/.." && pwd)"

# 颜色
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}🚀 快速部署 - 本地同步到服务器${NC}"
echo "================================================"
echo "本地: $LOCAL_PATH"
echo "远程: $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH"
echo "================================================"
echo ""

# 检查 rsync
if ! command -v rsync &> /dev/null; then
    echo -e "${RED}❌ 错误: 未安装 rsync${NC}"
    echo "安装: brew install rsync (macOS) 或 apt install rsync (Linux)"
    exit 1
fi

# 确认
read -p "确认同步? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ 取消同步"
    exit 0
fi

# 排除的文件和目录
EXCLUDE=(
    ".git"
    "node_modules"
    "vendor"
    "frontend/dist"
    "backend/cache"
    ".env"
    ".DS_Store"
    "*.log"
)

# 构建 rsync 排除参数
EXCLUDE_ARGS=""
for item in "${EXCLUDE[@]}"; do
    EXCLUDE_ARGS="$EXCLUDE_ARGS --exclude=$item"
done

# 同步代码
echo -e "${BLUE}📤 同步文件...${NC}"
rsync -avz --progress \
    $EXCLUDE_ARGS \
    --delete \
    "$LOCAL_PATH/" \
    "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ 文件同步完成${NC}"
else
    echo -e "${RED}❌ 文件同步失败${NC}"
    exit 1
fi

# 在远程服务器上执行部署
echo ""
echo -e "${BLUE}🔧 在服务器上部署...${NC}"
ssh $REMOTE_USER@$REMOTE_HOST << 'ENDSSH'
cd /var/www/aiimage

echo "📦 安装后端依赖..."
cd backend
composer install --no-dev --optimize-autoloader --quiet

echo "📦 安装前端依赖..."
cd ../frontend
npm install --silent

echo "🏗️  构建前端..."
npm run build

echo "🔄 重启服务..."
sudo systemctl restart aiimage-backend
sudo systemctl reload nginx

echo "✅ 部署完成！"

# 健康检查
sleep 2
if curl -f -s http://localhost:8080/api/health > /dev/null; then
    echo "✅ 后端 API 正常"
else
    echo "⚠️  后端 API 可能有问题，请检查日志"
fi
ENDSSH

echo ""
echo "================================================"
echo -e "${GREEN}✅ 快速部署完成！${NC}"
echo "================================================"
echo ""
echo "🌐 访问地址:"
echo "   https://165.154.235.9"
echo ""
echo "📝 查看日志:"
echo "   ssh root@165.154.235.9 'journalctl -u aiimage-backend -n 50'"
echo ""
echo "💡 提示: 稳定版本记得推送到 GitHub 保存"
echo ""
