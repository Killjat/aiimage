#!/bin/bash

# 快速启动脚本 - 一键启动前后端

set -e

# 颜色定义
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}🚀 启动 AI Chat 系统${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# 获取脚本所在目录
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# 启动后端
echo -e "${BLUE}1️⃣  启动后端服务...${NC}"
cd "$SCRIPT_DIR/backend"
php -S 0.0.0.0:8080 -t public > "$SCRIPT_DIR/backend.log" 2>&1 &
BACKEND_PID=$!
sleep 2

if ps -p $BACKEND_PID > /dev/null; then
    echo -e "${GREEN}✅ 后端已启动 (PID: $BACKEND_PID)${NC}"
    echo "   📍 地址: http://127.0.0.1:8080/"
else
    echo -e "${RED}❌ 后端启动失败${NC}"
    cat "$SCRIPT_DIR/backend.log"
    exit 1
fi

echo ""

# 启动前端
echo -e "${BLUE}2️⃣  启动前端服务...${NC}"
cd "$SCRIPT_DIR/frontend"
npm run dev > "$SCRIPT_DIR/frontend.log" 2>&1 &
FRONTEND_PID=$!
sleep 3

if ps -p $FRONTEND_PID > /dev/null; then
    echo -e "${GREEN}✅ 前端已启动 (PID: $FRONTEND_PID)${NC}"
    echo "   📍 地址: http://localhost:5173/"
else
    echo -e "${RED}❌ 前端启动失败${NC}"
    cat "$SCRIPT_DIR/frontend.log"
    kill $BACKEND_PID
    exit 1
fi

echo ""

# 显示总结
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}🎉 系统启动完成！${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo "📍 访问地址:"
echo -e "   ${GREEN}前端: http://localhost:5173/${NC}"
echo -e "   ${GREEN}后端: http://127.0.0.1:8080/${NC}"
echo ""
echo "📊 进程信息:"
echo "   后端 PID: $BACKEND_PID"
echo "   前端 PID: $FRONTEND_PID"
echo ""
echo "📝 日志文件:"
echo "   后端: $SCRIPT_DIR/backend.log"
echo "   前端: $SCRIPT_DIR/frontend.log"
echo ""
echo "🛑 停止服务:"
echo "   kill $BACKEND_PID $FRONTEND_PID"
echo ""
echo "📚 查看日志:"
echo "   tail -f $SCRIPT_DIR/backend.log"
echo "   tail -f $SCRIPT_DIR/frontend.log"
echo ""
echo -e "${BLUE}========================================${NC}"

# 保存 PID
echo "$BACKEND_PID" > "$SCRIPT_DIR/.backend.pid"
echo "$FRONTEND_PID" > "$SCRIPT_DIR/.frontend.pid"

# 等待进程
wait
