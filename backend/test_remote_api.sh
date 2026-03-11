#!/bin/bash

# 测试远程服务器上的 Alibaba 图片生成 API

echo "🧪 测试远程 Alibaba 图片生成 API"
echo "================================================"
echo ""

REMOTE_URL="http://165.154.235.9/api"

# 测试 1: 获取图片模型列表
echo "✓ 测试 1: 获取图片模型列表"
echo "  请求: GET $REMOTE_URL/image/models"
echo ""

MODELS_RESPONSE=$(curl -s "$REMOTE_URL/image/models")
echo "  响应:"
echo "$MODELS_RESPONSE" | jq '.' 2>/dev/null || echo "$MODELS_RESPONSE"
echo ""

# 检查是否包含 Alibaba 模型
if echo "$MODELS_RESPONSE" | grep -q "alibaba"; then
    echo "  ✅ 包含 Alibaba 模型"
else
    echo "  ⚠️  未找到 Alibaba 模型"
fi

echo ""
echo "================================================"
echo "✅ API 测试完成"
echo "================================================"
echo ""
echo "如果看到 Alibaba 模型，说明配置正确。"
echo "现在可以在前端尝试使用 Alibaba 模型生成图片。"
echo ""
