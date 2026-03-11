#!/bin/bash

# 远程部署测试脚本
# 测试所有新增的 API 端点

REMOTE_HOST="${1:-165.154.235.9}"
API_URL="http://$REMOTE_HOST:8080/api"

echo "=========================================="
echo "🧪 开始测试远程服务"
echo "=========================================="
echo "API 地址: $API_URL"
echo ""

# 颜色定义
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 测试计数
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# 测试函数
test_endpoint() {
    local name=$1
    local method=$2
    local endpoint=$3
    local expected_status=$4
    
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    echo -n "测试 [$TOTAL_TESTS] $name ... "
    
    if [ "$method" = "GET" ]; then
        http_code=$(curl -s -o /tmp/response.txt -w "%{http_code}" "$API_URL$endpoint")
    else
        http_code=$(curl -s -o /tmp/response.txt -w "%{http_code}" -X $method "$API_URL$endpoint")
    fi
    
    body=$(cat /tmp/response.txt 2>/dev/null)
    
    if [ "$http_code" = "$expected_status" ]; then
        echo -e "${GREEN}✅ PASS${NC} (HTTP $http_code)"
        PASSED_TESTS=$((PASSED_TESTS + 1))
        
        # 显示响应摘要
        if echo "$body" | grep -q "success\|data"; then
            echo "  └─ 响应: $(echo "$body" | cut -c1-100)..."
        fi
    else
        echo -e "${RED}❌ FAIL${NC} (期望 $expected_status, 得到 $http_code)"
        FAILED_TESTS=$((FAILED_TESTS + 1))
        echo "  └─ 响应: $(echo "$body" | cut -c1-100)"
    fi
    echo ""
}

# ============ 基础测试 ============
echo -e "${YELLOW}=== 基础服务测试 ===${NC}"
test_endpoint "健康检查" "GET" "/health" "200"
echo ""

# ============ 图片库 API 测试 ============
echo -e "${YELLOW}=== 图片库 API 测试 ===${NC}"

# 1. 获取公开图片库
test_endpoint "获取公开图片库" "GET" "/gallery/public?page=1&limit=20" "200"

# 2. 获取搜索建议
test_endpoint "获取搜索建议 (无关键词)" "GET" "/gallery/suggestions?limit=10" "200"

# 3. 获取搜索建议 (带关键词)
test_endpoint "获取搜索建议 (关键词: mountain)" "GET" "/gallery/suggestions?keyword=mountain&limit=10" "200"

# 4. 搜索图片
test_endpoint "搜索图片 (关键词: landscape)" "GET" "/gallery/search?keyword=landscape&page=1&limit=20" "200"

# 5. 获取模型统计
test_endpoint "获取模型统计" "GET" "/gallery/stats/models" "200"

# 6. 获取大模型统计
test_endpoint "获取大模型统计" "GET" "/gallery/stats/llm" "200"

# 7. 获取单个图片详情 (假设 ID=1)
test_endpoint "获取图片详情 (ID=1)" "GET" "/gallery/image/1" "200"

# 8. 点赞图片 (假设 ID=1)
test_endpoint "点赞图片 (ID=1)" "POST" "/gallery/image/1/like" "200"

echo ""

# ============ 其他 API 测试 ============
echo -e "${YELLOW}=== 其他 API 测试 ===${NC}"

# 1. 获取模型列表
test_endpoint "获取模型列表" "GET" "/models" "200"

# 2. 获取图片模型
test_endpoint "获取图片生成模型" "GET" "/image/models" "200"

# 3. 获取阿里配置
test_endpoint "获取阿里大模型配置" "GET" "/image/bailian/config" "200"

echo ""

# ============ 测试结果总结 ============
echo "=========================================="
echo "📊 测试结果总结"
echo "=========================================="
echo "总测试数: $TOTAL_TESTS"
echo -e "通过: ${GREEN}$PASSED_TESTS${NC}"
echo -e "失败: ${RED}$FAILED_TESTS${NC}"
echo ""

if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "${GREEN}✅ 所有测试通过！服务正常运行${NC}"
    exit 0
else
    echo -e "${RED}❌ 有 $FAILED_TESTS 个测试失败，请检查服务${NC}"
    exit 1
fi
