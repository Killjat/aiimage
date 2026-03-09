#!/bin/bash

echo "=========================================="
echo "游客模式功能测试"
echo "=========================================="
echo ""

API_URL="http://127.0.0.1:8080/api"

echo "1. 测试游客配额查询（无 Token）"
echo "-------------------------------------------"
QUOTA_RESPONSE=$(curl -s -X GET "${API_URL}/image/quota")
echo "响应: $QUOTA_RESPONSE"
echo ""

echo "2. 测试聊天功能（无需登录）"
echo "-------------------------------------------"
CHAT_RESPONSE=$(curl -s -X POST "${API_URL}/chat/send" \
  -H "Content-Type: application/json" \
  -d '{"model":"auto","messages":[{"role":"user","content":"你好"}]}')
echo "响应: ${CHAT_RESPONSE:0:100}..."
echo ""

echo "3. 测试游客图片生成（无 Token）"
echo "-------------------------------------------"
IMAGE_RESPONSE=$(curl -s -X POST "${API_URL}/image/generate" \
  -H "Content-Type: application/json" \
  -d '{"prompt":"a cute cat","model":"google/gemini-3.1-flash-image-preview"}')
echo "响应: ${IMAGE_RESPONSE:0:200}..."
echo ""

echo "4. 再次查询游客配额（应该减少1）"
echo "-------------------------------------------"
QUOTA_RESPONSE2=$(curl -s -X GET "${API_URL}/image/quota")
echo "响应: $QUOTA_RESPONSE2"
echo ""

echo "=========================================="
echo "测试完成！"
echo ""
echo "预期结果："
echo "1. 游客配额应该显示 3/3"
echo "2. 聊天应该成功返回消息"
echo "3. 图片生成应该成功（如果配额足够）"
echo "4. 配额应该变为 2/3"
echo "=========================================="
