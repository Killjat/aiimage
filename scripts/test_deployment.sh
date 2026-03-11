#!/bin/bash

# 快速部署测试脚本
# 用法: bash scripts/test_deployment.sh [remote_host]

REMOTE_HOST="${1:-165.154.235.9}"
API_URL="https://$REMOTE_HOST/api"

echo "=========================================="
echo "🧪 部署验证"
echo "=========================================="
echo "API 地址: $API_URL"
echo ""

PASS=0
FAIL=0

test_endpoint() {
    local name=$1
    local url=$2
    
    echo -n "测试: $name ... "
    
    if response=$(curl -s -k "$url" 2>/dev/null); then
        if echo "$response" | grep -q "success\|status\|data"; then
            echo "✅ PASS"
            ((PASS++))
        else
            echo "❌ FAIL (无效响应)"
            ((FAIL++))
        fi
    else
        echo "❌ FAIL (连接失败)"
        ((FAIL++))
    fi
}

# 测试前端
echo "前端测试:"
echo -n "  访问首页 ... "
if curl -s -k https://$REMOTE_HOST | grep -q "Cyberstroll"; then
    echo "✅ PASS"
    ((PASS++))
else
    echo "❌ FAIL"
    ((FAIL++))
fi

echo ""
echo "API 测试:"
test_endpoint "健康检查" "$API_URL/health"
test_endpoint "模型列表" "$API_URL/models"
test_endpoint "图片库" "$API_URL/gallery/public"
test_endpoint "搜索建议" "$API_URL/gallery/suggestions"

echo ""
echo "=========================================="
echo "📊 测试结果"
echo "=========================================="
echo "通过: $PASS"
echo "失败: $FAIL"
echo ""

if [ $FAIL -eq 0 ]; then
    echo "✅ 所有测试通过！部署成功"
    exit 0
else
    echo "❌ 有 $FAIL 个测试失败"
    exit 1
fi
