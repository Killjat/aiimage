#!/bin/bash

echo "=========================================="
echo "前端系统状态检查"
echo "=========================================="
echo ""

# 检查前端服务器
echo "1. 检查前端服务器 (http://localhost:5173/)"
if curl -s -o /dev/null -w "%{http_code}" http://localhost:5173/ | grep -q "200"; then
    echo "   ✅ 前端服务器运行正常"
else
    echo "   ❌ 前端服务器未响应"
fi
echo ""

# 检查后端 API
echo "2. 检查后端 API (http://127.0.0.1:8080/api/health)"
BACKEND_STATUS=$(curl -s http://127.0.0.1:8080/api/health | grep -o '"status":"ok"')
if [ ! -z "$BACKEND_STATUS" ]; then
    echo "   ✅ 后端 API 运行正常"
else
    echo "   ❌ 后端 API 未响应"
fi
echo ""

# 检查 MySQL
echo "3. 检查 MySQL 数据库"
if docker ps | grep -q "mysql-aiimage"; then
    echo "   ✅ MySQL 容器运行中"
else
    echo "   ❌ MySQL 容器未运行"
fi
echo ""

# 检查前端构建
echo "4. 检查前端 TypeScript 编译"
cd frontend
if npm run build > /dev/null 2>&1; then
    echo "   ✅ TypeScript 编译成功"
else
    echo "   ❌ TypeScript 编译失败"
fi
cd ..
echo ""

echo "=========================================="
echo "访问地址："
echo "  前端: http://localhost:5173/"
echo "  后端: http://127.0.0.1:8080/"
echo "=========================================="
echo ""
echo "如果所有检查都通过，请在浏览器中访问前端地址。"
echo "如果看到白屏，请按 F12 打开开发者工具查看控制台错误。"
echo ""
