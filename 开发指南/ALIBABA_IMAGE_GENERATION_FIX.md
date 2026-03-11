# 阿里巴巴图片生成错误修复指南

## 问题描述

**错误信息**:
```
⚠️ 阿里百练图片生成失败: Client error: `POST https://dashscope.aliyuncs.com/api/v1/services/aigc/multimodal-generation/generation` resulted in a `400 Bad Request` response: {"request_id":"...","code":"DataInspectionFailed","message":"Output data may contain in..."}
```

**原因**: 
- `DataInspectionFailed` 表示生成的图片内容被阿里巴巴的审核系统拒绝了
- 这通常是因为提示词包含敏感内容或违反了内容政策

## 解决方案

### 1. 修改提示词

**避免以下内容**:
- ❌ 暴力、血腥内容
- ❌ 成人内容
- ❌ 政治敏感内容
- ❌ 仇恨言论
- ❌ 虚假信息

**推荐的提示词**:
- ✅ "一只可爱的猫咪在花园里"
- ✅ "蓝天白云下的山水风景"
- ✅ "现代办公室的工作场景"
- ✅ "美食摄影，精致的甜点"
- ✅ "科技产品的产品渲染图"

### 2. 代码修复

已修复的问题:
- ✅ 移除了 `watermark: false` 参数（可能导致审核失败）
- ✅ 添加了更详细的错误信息处理
- ✅ 区分不同的错误类型

### 3. 测试新的提示词

```bash
# 使用简单的提示词测试
curl -X POST http://127.0.0.1:8080/api/image/generate \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "一只可爱的猫咪",
    "model": "alibaba-wan2.6-t2i"
  }'
```

## 常见错误及解决方案

### 错误 1: DataInspectionFailed

**症状**: 图片内容被审核拒绝

**解决方案**:
1. 修改提示词，避免敏感内容
2. 使用更通用、安全的描述
3. 尝试其他模型

### 错误 2: InsufficientBalance

**症状**: 账户余额不足

**解决方案**:
1. 检查阿里巴巴账户余额
2. 充值账户
3. 查看 API Key 是否正确

### 错误 3: RateLimitExceeded

**症状**: 请求过于频繁

**解决方案**:
1. 等待一段时间后重试
2. 减少请求频率
3. 检查是否有并发请求

## 推荐的提示词示例

### 风景类
```
"一片宽阔的蓝天，白云飘动，下方是绿色的山脉和清澈的湖泊"
"日落时分，金色的阳光照在沙滩上，海浪轻轻拍打"
"冬天的森林，白雪覆盖的树木，阳光透过树枝洒下"
```

### 人物类
```
"一个年轻女性，穿着蓝色连衣裙，在花园里微笑"
"一个商务人士，穿着正装，在现代办公室里工作"
"一个小孩，穿着彩色衣服，在公园里玩耍"
```

### 物品类
```
"一杯热咖啡，旁边放着一本书和眼镜"
"一台现代笔记本电脑，屏幕上显示代码"
"一束鲜花，放在透明的玻璃花瓶里"
```

### 艺术类
```
"油画风格的向日葵，色彩鲜艳，笔触粗犷"
"水彩画风格的山水风景，淡雅清新"
"数字艺术风格的未来城市，霓虹灯闪烁"
```

## 模型选择建议

### 对于安全内容

**推荐模型**:
- `alibaba-wan2.6-t2i` - 最新版本，审核严格
- `alibaba-qwen-image-2.0-pro` - 文字渲染强
- `alibaba-qwen-image-2.0` - 快速生成

### 如果阿里巴巴失败

**备选方案**:
- 使用 OpenRouter 的 Flux 2 Pro
- 使用 OpenRouter 的 Flux 2 Flex
- 使用其他 OpenRouter 模型

## 测试步骤

### 1. 测试简单提示词

```bash
curl -X POST http://127.0.0.1:8080/api/image/generate \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "一只猫",
    "model": "alibaba-wan2.6-t2i"
  }'
```

### 2. 如果成功，逐步增加复杂度

```bash
curl -X POST http://127.0.0.1:8080/api/image/generate \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "一只可爱的橙色猫咪在阳光下睡觉",
    "model": "alibaba-wan2.6-t2i"
  }'
```

### 3. 如果仍然失败，切换到 OpenRouter

```bash
curl -X POST http://127.0.0.1:8080/api/image/generate \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "一只可爱的猫咪",
    "model": "black-forest-labs/flux.2-pro"
  }'
```

## 前端使用建议

### 在图片生成器中

1. **选择模型**
   - 如果使用阿里巴巴模型，使用安全的提示词
   - 如果提示词可能敏感，使用 OpenRouter 模型

2. **输入提示词**
   - 避免敏感内容
   - 使用具体、清晰的描述
   - 包含风格、颜色、场景等细节

3. **处理错误**
   - 如果生成失败，修改提示词后重试
   - 或切换到其他模型

## 日志查看

### 查看详细错误信息

```bash
tail -f backend.log | grep -i "alibaba\|image"
```

### 查看完整的 API 响应

```bash
tail -f backend.log | grep -i "multimodal api"
```

## 联系支持

如果问题仍未解决：

1. 检查阿里巴巴 API Key 是否正确
2. 查看阿里巴巴官方文档
3. 尝试使用 OpenRouter 作为备选方案

## 相关文档

- [阿里巴巴模型快速开始](./ALIBABA_MODELS_QUICK_START.md)
- [集成指南](./README_INTEGRATION.md)
- [快速参考](./QUICK_REFERENCE_CARD.md)

---

**版本**: 1.0  
**最后更新**: 2026年3月10日  
**状态**: ✅ 已修复
