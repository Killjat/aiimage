#!/bin/bash

# AI Chat System 认证 API 测试脚本

API_URL="http://127.0.0.1:8080/api"
TEST_EMAIL="test@example.com"
TEST_PASSWORD="password123"
TEST_USERNAME="测试用户"

echo "=== AI Chat System 认证 API 测试 ==="
echo ""

# 测试 1: 注册新用户
echo "--- 测试 1: 注册新用户 ---"
REGISTER_RESPONSE=$(curl -s -X POST "${API_URL}/auth/register" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"${TEST_EMAIL}\",
    \"password\": \"${TEST_PASSWORD}\",
    \"username\": \"${TEST_USERNAME}\"
  }")

echo "响应: ${REGISTER_RESPONSE}"
echo ""

# 提取 token
TOKEN=$(echo $REGISTER_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
  echo "❌ 注册失败或用户已存在"
  echo "尝试登录现有用户..."
  echo ""
  
  # 测试 2: 登录
  echo "--- 测试 2: 登录 ---"
  LOGIN_RESPONSE=$(curl -s -X POST "${API_URL}/auth/login" \
    -H "Content-Type: application/json" \
    -d "{
      \"email\": \"${TEST_EMAIL}\",
      \"password\": \"${TEST_PASSWORD}\"
    }")
  
  echo "响应: ${LOGIN_RESPONSE}"
  echo ""
  
  TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)
  
  if [ -z "$TOKEN" ]; then
    echo "❌ 登录失败"
    exit 1
  fi
  
  echo "✅ 登录成功"
else
  echo "✅ 注册成功"
fi

echo "Token: ${TOKEN:0:50}..."
echo ""

# 测试 3: 获取当前用户信息
echo "--- 测试 3: 获取当前用户信息 ---"
ME_RESPONSE=$(curl -s -X GET "${API_URL}/auth/me" \
  -H "Authorization: Bearer ${TOKEN}")

echo "响应: ${ME_RESPONSE}"
echo ""

if echo "$ME_RESPONSE" | grep -q "\"email\""; then
  echo "✅ 获取用户信息成功"
else
  echo "❌ 获取用户信息失败"
fi

echo ""

# 测试 4: 使用错误的密码登录
echo "--- 测试 4: 使用错误的密码登录 ---"
WRONG_LOGIN_RESPONSE=$(curl -s -X POST "${API_URL}/auth/login" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"${TEST_EMAIL}\",
    \"password\": \"wrong_password\"
  }")

echo "响应: ${WRONG_LOGIN_RESPONSE}"
echo ""

if echo "$WRONG_LOGIN_RESPONSE" | grep -q "error"; then
  echo "✅ 正确拒绝了错误密码"
else
  echo "❌ 安全问题：接受了错误密码"
fi

echo ""

# 测试 5: 使用无效 token 访问
echo "--- 测试 5: 使用无效 token 访问 ---"
INVALID_TOKEN_RESPONSE=$(curl -s -X GET "${API_URL}/auth/me" \
  -H "Authorization: Bearer invalid_token_here")

echo "响应: ${INVALID_TOKEN_RESPONSE}"
echo ""

if echo "$INVALID_TOKEN_RESPONSE" | grep -q "error"; then
  echo "✅ 正确拒绝了无效 token"
else
  echo "❌ 安全问题：接受了无效 token"
fi

echo ""
echo "=== 测试完成 ==="
echo ""
echo "💡 提示:"
echo "- 如果测试失败，请确保后端服务已启动: cd backend && php -S 0.0.0.0:8080 -t public"
echo "- 如果数据库错误，请运行: cd backend && php init_database.php"
