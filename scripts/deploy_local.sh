#!/bin/bash

# 本地部署脚本 - 启动前后端服务
# 用于开发和测试环境

set -e

# 颜色定义
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# 获取脚本所在目录
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}🚀 本地部署 - AI Chat 系统${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# 检查依赖
echo -e "${BLUE}1️⃣  检查依赖...${NC}"

if ! command -v php &> /dev/null; then
    echo -e "${RED}❌ 错误: 未安装 PHP${NC}"
    exit 1
fi
echo -e "${GREEN}✅ PHP 已安装 ($(php -v | head -n 1))${NC}"

if ! command -v node &> /dev/null; then
    echo -e "${RED}❌ 错误: 未安装 Node.js${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Node.js 已安装 ($(node -v))${NC}"

if ! command -v npm &> /dev/null; then
    echo -e "${RED}❌ 错误: 未安装 npm${NC}"
    exit 1
fi
echo -e "${GREEN}✅ npm 已安装 ($(npm -v))${NC}"

echo ""

# 检查数据库
echo -e "${BLUE}2️⃣  检查数据库...${NC}"

# 检查 MySQL 是否运行
if ! command -v mysql &> /dev/null; then
    echo -e "${YELLOW}⚠️  MySQL 客户端未安装，跳过数据库检查${NC}"
else
    if mysql -h 127.0.0.1 -u root -e "SELECT 1" &> /dev/null; then
        echo -e "${GREEN}✅ MySQL 已连接${NC}"
        
        # 初始化数据库
        if mysql -h 127.0.0.1 -u root -e "USE ai_chat_system" &> /dev/null; then
            echo -e "${GREEN}✅ 数据库已存在${NC}"
        else
            echo -e "${YELLOW}⚠️  数据库不存在，正在初始化...${NC}"
            cd "$PROJECT_ROOT/backend"
            php init_database.php
            echo -e "${GREEN}✅ 数据库初始化完成${NC}"
            cd "$PROJECT_ROOT"
        fi
    else
        echo -e "${YELLOW}⚠️  MySQL 未运行，请先启动 MySQL${NC}"
        echo "   macOS: brew services start mysql"
        echo "   Linux: sudo systemctl start mysql"
        echo "   Docker: docker run -d -p 3306:3306 -e MYSQL_ROOT_PASSWORD=password mysql:8.0"
    fi
fi

echo ""

# 安装后端依赖
echo -e "${BLUE}3️⃣  安装后端依赖...${NC}"
cd "$PROJECT_ROOT/backend"

if [ ! -d "vendor" ]; then
    echo "📦 安装 Composer 依赖..."
    composer install
    echo -e "${GREEN}✅ 后端依赖安装完成${NC}"
else
    echo -e "${GREEN}✅ 后端依赖已存在${NC}"
fi

echo ""

# 安装前端依赖
echo -e "${BLUE}4️⃣  安装前端依赖...${NC}"
cd "$PROJECT_ROOT/frontend"

if [ ! -d "node_modules" ]; then
    echo "📦 安装 npm 依赖..."
    npm install
    echo -e "${GREEN}✅ 前端依赖安装完成${NC}"
else
    echo -e "${GREEN}✅ 前端依赖已存在${NC}"
fi

echo ""

# 启动后端
echo -e "${BLUE}5️⃣  启动后端服务...${NC}"
cd "$PROJECT_ROOT/backend"

# 检查端口是否被占用
if lsof -Pi :8080 -sTCP:LISTEN -t >/dev/null 2>&1; then
    echo -e "${YELLOW}⚠️  端口 8080 已被占用${NC}"
    read -p "是否使用其他端口? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        read -p "请输入端口号 (默认 8081): " PORT
        PORT=${PORT:-8081}
    else
        echo -e "${RED}❌ 无法启动后端${NC}"
        exit 1
    fi
else
    PORT=8080
fi

# 启动后端服务
php -S 0.0.0.0:$PORT -t public > "$PROJECT_ROOT/backend.log" 2>&1 &
BACKEND_PID=$!
sleep 2

if ps -p $BACKEND_PID > /dev/null; then
    echo -e "${GREEN}✅ 后端已启动 (PID: $BACKEND_PID)${NC}"
    echo "   📍 地址: http://127.0.0.1:$PORT/"
    echo "   📝 日志: $PROJECT_ROOT/backend.log"
else
    echo -e "${RED}❌ 后端启动失败${NC}"
    cat "$PROJECT_ROOT/backend.log"
    exit 1
fi

echo ""

# 启动前端
echo -e "${BLUE}6️⃣  启动前端服务...${NC}"
cd "$PROJECT_ROOT/frontend"

# 检查端口是否被占用
if lsof -Pi :5173 -sTCP:LISTEN -t >/dev/null 2>&1; then
    echo -e "${YELLOW}⚠️  端口 5173 已被占用${NC}"
    read -p "是否使用其他端口? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        read -p "请输入端口号 (默认 5174): " FRONTEND_PORT
        FRONTEND_PORT=${FRONTEND_PORT:-5174}
    else
        echo -e "${RED}❌ 无法启动前端${NC}"
        kill $BACKEND_PID
        exit 1
    fi
else
    FRONTEND_PORT=5173
fi

# 启动前端服务
npm run dev -- --port $FRONTEND_PORT > "$PROJECT_ROOT/frontend.log" 2>&1 &
FRONTEND_PID=$!
sleep 3

if ps -p $FRONTEND_PID > /dev/null; then
    echo -e "${GREEN}✅ 前端已启动 (PID: $FRONTEND_PID)${NC}"
    echo "   📍 地址: http://localhost:$FRONTEND_PORT/"
    echo "   📝 日志: $PROJECT_ROOT/frontend.log"
else
    echo -e "${RED}❌ 前端启动失败${NC}"
    cat "$PROJECT_ROOT/frontend.log"
    kill $BACKEND_PID
    exit 1
fi

echo ""

# 测试服务
echo -e "${BLUE}7️⃣  测试服务...${NC}"
sleep 2

# 测试后端
if curl -s http://127.0.0.1:$PORT/api/health 2>/dev/null | grep -q "ok"; then
    echo -e "${GREEN}✅ 后端 API 正常${NC}"
else
    echo -e "${YELLOW}⚠️  后端 API 可能有问题，请检查日志${NC}"
fi

echo ""

# 显示总结
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}🎉 部署完成！${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo "📍 访问地址:"
echo -e "   ${GREEN}前端: http://localhost:$FRONTEND_PORT/${NC}"
echo -e "   ${GREEN}后端: http://127.0.0.1:$PORT/${NC}"
echo ""
echo "📊 进程信息:"
echo "   后端 PID: $BACKEND_PID"
echo "   前端 PID: $FRONTEND_PID"
echo ""
echo "📝 日志文件:"
echo "   后端: $PROJECT_ROOT/backend.log"
echo "   前端: $PROJECT_ROOT/frontend.log"
echo ""
echo "🛑 停止服务:"
echo "   kill $BACKEND_PID $FRONTEND_PID"
echo ""
echo "📚 查看日志:"
echo "   tail -f $PROJECT_ROOT/backend.log"
echo "   tail -f $PROJECT_ROOT/frontend.log"
echo ""
echo "🔄 重新启动:"
echo "   bash $SCRIPT_DIR/deploy_local.sh"
echo ""
echo -e "${BLUE}========================================${NC}"

# 保存 PID 到文件
echo "$BACKEND_PID" > "$PROJECT_ROOT/.backend.pid"
echo "$FRONTEND_PID" > "$PROJECT_ROOT/.frontend.pid"

# 等待进程
wait
