#!/bin/bash

# 快速停止脚本 - 一键停止前后端

# 颜色定义
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# 获取脚本所在目录
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}🛑 停止 AI Chat 系统${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# 读取 PID
BACKEND_PID=""
FRONTEND_PID=""

if [ -f "$SCRIPT_DIR/.backend.pid" ]; then
    BACKEND_PID=$(cat "$SCRIPT_DIR/.backend.pid")
fi

if [ -f "$SCRIPT_DIR/.frontend.pid" ]; then
    FRONTEND_PID=$(cat "$SCRIPT_DIR/.frontend.pid")
fi

# 停止后端
if [ -n "$BACKEND_PID" ] && ps -p $BACKEND_PID > /dev/null 2>&1; then
    echo -e "${BLUE}停止后端服务 (PID: $BACKEND_PID)...${NC}"
    kill $BACKEND_PID 2>/dev/null || true
    sleep 1
    if ps -p $BACKEND_PID > /dev/null 2>&1; then
        kill -9 $BACKEND_PID 2>/dev/null || true
    fi
    echo -e "${GREEN}✅ 后端已停止${NC}"
    rm -f "$SCRIPT_DIR/.backend.pid"
else
    echo -e "${YELLOW}⚠️  后端进程未找到${NC}"
fi

echo ""

# 停止前端
if [ -n "$FRONTEND_PID" ] && ps -p $FRONTEND_PID > /dev/null 2>&1; then
    echo -e "${BLUE}停止前端服务 (PID: $FRONTEND_PID)...${NC}"
    kill $FRONTEND_PID 2>/dev/null || true
    sleep 1
    if ps -p $FRONTEND_PID > /dev/null 2>&1; then
        kill -9 $FRONTEND_PID 2>/dev/null || true
    fi
    echo -e "${GREEN}✅ 前端已停止${NC}"
    rm -f "$SCRIPT_DIR/.frontend.pid"
else
    echo -e "${YELLOW}⚠️  前端进程未找到${NC}"
fi

echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}✅ 所有服务已停止${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
