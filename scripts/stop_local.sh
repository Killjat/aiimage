#!/bin/bash

# 本地停止脚本 - 停止前后端服务

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
echo -e "${BLUE}🛑 停止 AI Chat 系统${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# 读取 PID
BACKEND_PID=""
FRONTEND_PID=""

if [ -f "$PROJECT_ROOT/.backend.pid" ]; then
    BACKEND_PID=$(cat "$PROJECT_ROOT/.backend.pid")
fi

if [ -f "$PROJECT_ROOT/.frontend.pid" ]; then
    FRONTEND_PID=$(cat "$PROJECT_ROOT/.frontend.pid")
fi

# 停止后端
if [ -n "$BACKEND_PID" ] && ps -p $BACKEND_PID > /dev/null 2>&1; then
    echo -e "${BLUE}停止后端服务 (PID: $BACKEND_PID)...${NC}"
    kill $BACKEND_PID
    sleep 1
    if ps -p $BACKEND_PID > /dev/null 2>&1; then
        echo -e "${YELLOW}⚠️  进程未响应，强制杀死...${NC}"
        kill -9 $BACKEND_PID
    fi
    echo -e "${GREEN}✅ 后端已停止${NC}"
    rm -f "$PROJECT_ROOT/.backend.pid"
else
    echo -e "${YELLOW}⚠️  后端进程未找到${NC}"
fi

echo ""

# 停止前端
if [ -n "$FRONTEND_PID" ] && ps -p $FRONTEND_PID > /dev/null 2>&1; then
    echo -e "${BLUE}停止前端服务 (PID: $FRONTEND_PID)...${NC}"
    kill $FRONTEND_PID
    sleep 1
    if ps -p $FRONTEND_PID > /dev/null 2>&1; then
        echo -e "${YELLOW}⚠️  进程未响应，强制杀死...${NC}"
        kill -9 $FRONTEND_PID
    fi
    echo -e "${GREEN}✅ 前端已停止${NC}"
    rm -f "$PROJECT_ROOT/.frontend.pid"
else
    echo -e "${YELLOW}⚠️  前端进程未找到${NC}"
fi

echo ""

# 检查是否还有 PHP 或 Node 进程在运行
echo -e "${BLUE}检查残留进程...${NC}"

PHP_PIDS=$(pgrep -f "php -S 0.0.0.0" | grep -v grep)
if [ -n "$PHP_PIDS" ]; then
    echo -e "${YELLOW}⚠️  发现残留 PHP 进程:${NC}"
    echo "$PHP_PIDS" | while read pid; do
        echo "   PID: $pid"
        kill -9 $pid 2>/dev/null || true
    done
    echo -e "${GREEN}✅ 已清理${NC}"
fi

NODE_PIDS=$(pgrep -f "npm run dev" | grep -v grep)
if [ -n "$NODE_PIDS" ]; then
    echo -e "${YELLOW}⚠️  发现残留 Node 进程:${NC}"
    echo "$NODE_PIDS" | while read pid; do
        echo "   PID: $pid"
        kill -9 $pid 2>/dev/null || true
    done
    echo -e "${GREEN}✅ 已清理${NC}"
fi

echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}✅ 所有服务已停止${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
