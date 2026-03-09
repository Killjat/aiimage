#!/bin/bash

echo "=== AI Chat System 状态检查 ==="
echo ""

# 检查 MySQL
echo "1. MySQL (Docker)"
if docker ps | grep -q mysql-aiimage; then
    echo "   ✅ MySQL 容器运行中"
    docker exec mysql-aiimage mysql -uroot -proot -e "SELECT 1;" > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo "   ✅ MySQL 可以连接"
    else
        echo "   ❌ MySQL 无法连接"
    fi
else
    echo "   ❌ MySQL 容器未运行"
    echo "   启动命令: docker start mysql-aiimage"
fi
echo ""

# 检查后端
echo "2. 后端服务 (PHP)"
BACKEND_RESPONSE=$(curl -s http://127.0.0.1:8080/api/health 2>/dev/null)
if echo "$BACKEND_RESPONSE" | grep -q "ok"; then
    echo "   ✅ 后端服务运行中"
    echo "   地址: http://127.0.0.1:8080"
else
    echo "   ❌ 后端服务未响应"
    echo "   启动命令: cd backend && php -S 0.0.0.0:8080 -t public"
fi
echo ""

# 检查前端
echo "3. 前端服务 (Vite)"
FRONTEND_RESPONSE=$(curl -s http://localhost:5173 2>/dev/null | head -c 50)
if [ ! -z "$FRONTEND_RESPONSE" ]; then
    echo "   ✅ 前端服务运行中"
    echo "   地址: http://localhost:5173"
else
    echo "   ❌ 前端服务未响应"
    echo "   启动命令: cd frontend && npm run dev"
fi
echo ""

# 检查数据库表
echo "4. 数据库表"
TABLES=$(docker exec mysql-aiimage mysql -uroot -proot ai_chat_system -e "SHOW TABLES;" 2>/dev/null | grep -v "Tables_in")
if [ ! -z "$TABLES" ]; then
    echo "   ✅ 数据库表已创建"
    echo "$TABLES" | while read table; do
        echo "      - $table"
    done
else
    echo "   ❌ 数据库表未创建"
    echo "   初始化命令: cd backend && php init_database.php"
fi
echo ""

# 检查 API 功能
echo "5. API 功能测试"

# 测试模型列表
MODELS_RESPONSE=$(curl -s http://127.0.0.1:8080/api/models 2>/dev/null)
if echo "$MODELS_RESPONSE" | grep -q "success"; then
    MODEL_COUNT=$(echo "$MODELS_RESPONSE" | grep -o '"id"' | wc -l)
    echo "   ✅ 模型列表 API: $MODEL_COUNT 个模型"
else
    echo "   ❌ 模型列表 API 失败"
fi

# 测试认证 API
AUTH_RESPONSE=$(curl -s -X POST http://127.0.0.1:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test","password":"test"}' 2>/dev/null)
if echo "$AUTH_RESPONSE" | grep -q "error"; then
    echo "   ✅ 认证 API 响应正常"
else
    echo "   ⚠️  认证 API 可能有问题"
fi

echo ""
echo "=== 检查完成 ==="
echo ""

# 总结
ALL_OK=true

if ! docker ps | grep -q mysql-aiimage; then
    ALL_OK=false
fi

if ! echo "$BACKEND_RESPONSE" | grep -q "ok"; then
    ALL_OK=false
fi

if [ -z "$FRONTEND_RESPONSE" ]; then
    ALL_OK=false
fi

if [ "$ALL_OK" = true ]; then
    echo "✅ 所有服务运行正常！"
    echo ""
    echo "访问地址:"
    echo "  前端: http://localhost:5173"
    echo "  后端: http://127.0.0.1:8080"
    echo ""
    echo "可用功能:"
    echo "  - 用户注册/登录"
    echo "  - AI 聊天 (346+ 模型)"
    echo "  - 图片生成 (6 个模型)"
    echo "  - 配额管理 (每用户 10 次)"
else
    echo "⚠️  部分服务未运行，请检查上面的详细信息"
    echo ""
    echo "快速启动所有服务:"
    echo "  docker start mysql-aiimage"
    echo "  cd backend && php -S 0.0.0.0:8080 -t public &"
    echo "  cd frontend && npm run dev"
fi
