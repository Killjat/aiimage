#!/bin/bash

# 从 GitHub 部署到生产服务器
# 适用场景：稳定版本发布、需要版本控制
# 使用方法: ./scripts/deploy_from_github.sh

set -e  # 遇到错误立即退出

echo "🚀 从 GitHub 部署稳定版本..."
echo "================================================"

# 颜色定义
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# 项目目录
PROJECT_DIR="/var/www/aiimage"

# 检查是否在正确的目录
if [ ! -d "$PROJECT_DIR" ]; then
    echo -e "${RED}❌ 错误: 项目目录不存在: $PROJECT_DIR${NC}"
    exit 1
fi

cd $PROJECT_DIR

# 1. 备份当前版本
echo -e "${BLUE}📦 备份当前版本...${NC}"
BACKUP_DIR="/var/backups/aiimage/$(date +%Y%m%d_%H%M%S)"
mkdir -p $BACKUP_DIR
cp -r backend/.env $BACKUP_DIR/ 2>/dev/null || true
cp -r frontend/.env $BACKUP_DIR/ 2>/dev/null || true
echo -e "${GREEN}✅ 备份完成: $BACKUP_DIR${NC}"

# 2. 拉取最新代码
echo -e "${BLUE}📥 拉取最新代码...${NC}"
git fetch origin
CURRENT_COMMIT=$(git rev-parse HEAD)
git pull origin main

NEW_COMMIT=$(git rev-parse HEAD)
if [ "$CURRENT_COMMIT" = "$NEW_COMMIT" ]; then
    echo -e "${GREEN}✅ 代码已是最新版本${NC}"
else
    echo -e "${GREEN}✅ 代码更新成功${NC}"
    echo "   从 $CURRENT_COMMIT"
    echo "   到 $NEW_COMMIT"
fi

# 3. 同步环境变量文件
echo -e "${BLUE}🔐 同步环境变量...${NC}"
# 检查本地是否有 .env 文件，如果有则复制到远程
if [ -f "backend/.env" ]; then
    cp backend/.env backend/.env.remote.bak
    echo -e "${GREEN}✅ 后端环境变量已同步${NC}"
else
    echo -e "${RED}⚠️  本地 backend/.env 不存在，跳过同步${NC}"
fi

if [ -f "frontend/.env" ]; then
    cp frontend/.env frontend/.env.remote.bak
    echo -e "${GREEN}✅ 前端环境变量已同步${NC}"
else
    echo -e "${RED}⚠️  本地 frontend/.env 不存在，跳过同步${NC}"
fi

# 4. 后端部署
echo -e "${BLUE}🔧 部署后端...${NC}"
cd backend

# 安装/更新依赖
if [ -f "composer.json" ]; then
    composer install --no-dev --optimize-autoloader --no-interaction
    echo -e "${GREEN}✅ Composer 依赖已更新${NC}"
fi

# 设置权限
chown -R www-data:www-data .
chmod -R 755 .
echo -e "${GREEN}✅ 后端权限已设置${NC}"

# 5. 前端部署
echo -e "${BLUE}🎨 部署前端...${NC}"
cd ../frontend

# 安装/更新依赖
if [ -f "package.json" ]; then
    npm install --production
    echo -e "${GREEN}✅ NPM 依赖已更新${NC}"
fi

# 构建前端
npm run build
echo -e "${GREEN}✅ 前端构建完成${NC}"

# 设置权限
chown -R www-data:www-data dist
chmod -R 755 dist

# 6. 重启服务
echo -e "${BLUE}🔄 重启服务...${NC}"

# 重启后端服务
if systemctl is-active --quiet aiimage-backend; then
    systemctl restart aiimage-backend
    echo -e "${GREEN}✅ 后端服务已重启${NC}"
else
    echo -e "${RED}⚠️  后端服务未运行，尝试启动...${NC}"
    systemctl start aiimage-backend
fi

# 重新加载 Nginx
if systemctl is-active --quiet nginx; then
    systemctl reload nginx
    echo -e "${GREEN}✅ Nginx 已重新加载${NC}"
else
    echo -e "${RED}❌ Nginx 未运行${NC}"
    exit 1
fi

# 7. 健康检查
echo -e "${BLUE}🏥 健康检查...${NC}"
sleep 2

# 检查后端 API
if curl -f -s http://localhost:8080/api/health > /dev/null; then
    echo -e "${GREEN}✅ 后端 API 正常${NC}"
else
    echo -e "${RED}❌ 后端 API 异常${NC}"
    echo "查看日志: journalctl -u aiimage-backend -n 50"
fi

# 检查前端
if curl -f -s -I https://165.154.235.9 > /dev/null; then
    echo -e "${GREEN}✅ 前端访问正常${NC}"
else
    echo -e "${RED}⚠️  前端访问异常${NC}"
fi

# 8. 显示服务状态
echo ""
echo "================================================"
echo -e "${GREEN}✅ 部署完成！${NC}"
echo "================================================"
echo ""
echo "📊 服务状态:"
systemctl status aiimage-backend --no-pager -l | head -5
echo ""
echo "🌐 访问地址:"
echo "   前端: https://165.154.235.9"
echo "   后端: http://165.154.235.9/api"
echo ""
echo "📝 查看日志:"
echo "   后端: journalctl -u aiimage-backend -f"
echo "   Nginx: tail -f /var/log/nginx/error.log"
echo ""
echo "🔄 回滚命令:"
echo "   git reset --hard $CURRENT_COMMIT && ./scripts/deploy_from_github.sh"
echo ""
