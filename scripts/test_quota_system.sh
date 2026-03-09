#!/bin/bash

# 配额系统测试脚本

API_URL="http://127.0.0.1:8080/api"
TEST_EMAIL="quota_test_$(date +%s)@example.com"
TEST_PASSWORD="password123"

echo "=== 配额系统测试 ==="
echo ""
echo "测试邮箱: ${TEST_EMAIL}"
echo ""

# 1. 注册新用户
echo "--- 步骤 1: 注册新用户 ---"
REGISTER_RESPONSE=$(curl -s -X POST "${API_URL}/auth/register" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"${TEST_EMAIL}\",
    \"password\": \"${TEST_PASSWORD}\"
  }")

echo "注册响应: ${REGISTER_RESPONSE}"
echo ""

# 提取 token
TOKEN=$(echo $REGISTER_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
  echo "❌ 注册失败，无法获取 token"
  exit 1
fi

echo "✅ 注册成功"
echo "Token: ${TOKEN:0:50}..."
echo ""

# 2. 查看初始配额
echo "--- 步骤 2: 查看初始配额 ---"
QUOTA_RESPONSE=$(curl -s -X GET "${API_URL}/image/quota" \
  -H "Authorization: Bearer ${TOKEN}")

echo "配额信息: ${QUOTA_RESPONSE}"
echo ""

# 3. 生成第一张图片
echo "--- 步骤 3: 生成第一张图片 ---"
IMAGE1_RESPONSE=$(curl -s -X POST "${API_URL}/image/generate" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "a cute cat",
    "model": "google/gemini-3.1-flash-image-preview"
  }')

echo "生成响应: ${IMAGE1_RESPONSE}"
echo ""

if echo "$IMAGE1_RESPONSE" | grep -q "\"success\":true"; then
  echo "✅ 第一张图片生成成功"
  
  # 提取配额信息
  REMAINING=$(echo $IMAGE1_RESPONSE | grep -o '"remaining":[0-9]*' | cut -d':' -f2)
  echo "剩余配额: ${REMAINING}"
else
  echo "❌ 图片生成失败"
fi
echo ""

# 4. 连续生成多张图片
echo "--- 步骤 4: 连续生成 5 张图片 ---"
for i in {2..6}; do
  echo "生成第 ${i} 张图片..."
  RESPONSE=$(curl -s -X POST "${API_URL}/image/generate" \
    -H "Authorization: Bearer ${TOKEN}" \
    -H "Content-Type: application/json" \
    -d "{
      \"prompt\": \"test image ${i}\",
      \"model\": \"google/gemini-3.1-flash-image-preview\"
    }")
  
  if echo "$RESPONSE" | grep -q "\"success\":true"; then
    REMAINING=$(echo $RESPONSE | grep -o '"remaining":[0-9]*' | cut -d':' -f2)
    echo "  ✅ 成功，剩余配额: ${REMAINING}"
  else
    ERROR=$(echo $RESPONSE | grep -o '"error":"[^"]*' | cut -d'"' -f4)
    echo "  ❌ 失败: ${ERROR}"
  fi
done
echo ""

# 5. 查看当前配额
echo "--- 步骤 5: 查看当前配额 ---"
QUOTA_RESPONSE=$(curl -s -X GET "${API_URL}/image/quota" \
  -H "Authorization: Bearer ${TOKEN}")

echo "配额信息: ${QUOTA_RESPONSE}"
echo ""

# 6. 查看生成历史
echo "--- 步骤 6: 查看生成历史 ---"
HISTORY_RESPONSE=$(curl -s -X GET "${API_URL}/image/history?limit=5" \
  -H "Authorization: Bearer ${TOKEN}")

echo "历史记录: ${HISTORY_RESPONSE}"
echo ""

# 7. 继续生成直到配额用完
echo "--- 步骤 7: 继续生成直到配额用完 ---"
for i in {7..15}; do
  echo "尝试生成第 ${i} 张图片..."
  RESPONSE=$(curl -s -X POST "${API_URL}/image/generate" \
    -H "Authorization: Bearer ${TOKEN}" \
    -H "Content-Type: application/json" \
    -d "{
      \"prompt\": \"test image ${i}\",
      \"model\": \"google/gemini-3.1-flash-image-preview\"
    }")
  
  if echo "$RESPONSE" | grep -q "\"success\":true"; then
    REMAINING=$(echo $RESPONSE | grep -o '"remaining":[0-9]*' | cut -d':' -f2)
    echo "  ✅ 成功，剩余配额: ${REMAINING}"
    
    if [ "$REMAINING" = "0" ]; then
      echo "  配额已用完，停止测试"
      break
    fi
  else
    ERROR=$(echo $RESPONSE | grep -o '"error":"[^"]*' | cut -d'"' -f4)
    echo "  ❌ 失败: ${ERROR}"
    
    if echo "$ERROR" | grep -q "配额已用完"; then
      echo "  ✅ 配额限制正常工作"
      break
    fi
  fi
done
echo ""

# 8. 尝试在配额用完后生成
echo "--- 步骤 8: 配额用完后尝试生成 ---"
FINAL_RESPONSE=$(curl -s -X POST "${API_URL}/image/generate" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "should fail",
    "model": "google/gemini-3.1-flash-image-preview"
  }')

echo "响应: ${FINAL_RESPONSE}"
echo ""

if echo "$FINAL_RESPONSE" | grep -q "配额已用完"; then
  echo "✅ 配额限制正常工作"
else
  echo "❌ 配额限制可能有问题"
fi

echo ""
echo "=== 测试完成 ==="
echo ""
echo "📊 测试总结:"
echo "- 用户注册: ✅"
echo "- 初始配额: 10 次"
echo "- 配额扣除: 测试中"
echo "- 配额限制: 测试中"
echo "- 历史记录: 测试中"
echo ""
echo "💡 提示:"
echo "- 查看完整配额信息: curl -H \"Authorization: Bearer ${TOKEN}\" ${API_URL}/image/quota"
echo "- 查看生成历史: curl -H \"Authorization: Bearer ${TOKEN}\" ${API_URL}/image/history"
