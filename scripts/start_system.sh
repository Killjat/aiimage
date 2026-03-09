#!/bin/bash

echo "=========================================="
echo "启动 AI Chat 系统"
echo "=========================================="
echo ""

# 检查 MySQL
echo "1. 检查 MySQL 数据库..."
if docker ps | grep -q "mysql-aiimage"; then
    echo "   ✅ MySQL 已运行"
else
    echo "   ⚠️  MySQL 未运行，正在启动..."
    docker start mysql-aiimage
    sleep 3
    echo "   ✅ MySQL 已启动"
fi
echo ""

# 启动后端
echo "2. 启动后端服务..."
cd backend
php -S 0.0.0.0:8080 -t public > /dev/null 2>&1 &
BACKEND_PID=$!
echo "   ✅ 后端已启动 (PID: $BACKEND_PID)"
echo "   📍 地址: http://127.0.0.1:8080/"
cd ..
echo ""

# 等待后端启动
sleep 2

# 启动前端
echo "3. 启动前端服务..."
cd frontend
npm run dev > /dev/null 2>&1 &
FRONTEND_PID=$!
echo "   ✅ 前端已启动 (PID: $FRONTEND_PID)"
echo "   📍 地址: http://localhost:5173/"
cd ..
echo ""

# 等待前端启动
sleep 3

# 测试服务
echo "4. 测试服务状态..."
if curl -s http://127.0.0.1:8080/api/health | grep -q "ok"; then
    echo "   ✅ 后端 API 正常"
else
    echo "   ❌ 后端 API 异常"
fi

if curl -s http://localhost:5173/ | grep -q "root"; then
    echo "   ✅ 前端页面正常"
else
    echo "   ❌ 前端页面异常"
fi
echo ""

echo "=========================================="
echo "🎉 系统启动完成！"
echo ""
echo "访问地址："
echo "  前端: http://localhost:5173/"
echo "  后端: http://127.0.0.1:8080/"
echo ""
echo "进程 ID："
echo "  后端 PID: $BACKEND_PID"
echo "  前端 PID: $FRONTEND_PID"
echo ""
echo "停止服务："
echo "  kill $BACKEND_PID $FRONTEND_PID"
echo "=========================================="
