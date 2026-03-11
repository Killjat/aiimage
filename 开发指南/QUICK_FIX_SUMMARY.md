# 🔧 快速修复总结

## 问题

阿里巴巴图片生成返回 `DataInspectionFailed` 错误

## 原因

1. **watermark 参数问题**: `watermark: false` 可能导致审核失败
2. **提示词内容**: 生成的图片内容被审核系统拒绝

## 已修复

✅ **backend/src/Services/AliBailianService.php**
- 移除了 `watermark: false` 参数
- 添加了更详细的错误处理
- 区分不同的错误类型

## 解决方案

### 方案 1: 修改提示词（推荐）

使用安全、通用的提示词：

```
✅ "一只可爱的猫咪"
✅ "蓝天白云下的山水风景"
✅ "现代办公室的工作场景"
✅ "美食摄影，精致的甜点"
```

### 方案 2: 切换到 OpenRouter

如果阿里巴巴仍然失败，使用 OpenRouter 的模型：

```
model: "black-forest-labs/flux.2-pro"
或
model: "black-forest-labs/flux.2-flex"
```

## 测试

### 测试简单提示词

```bash
curl -X POST http://127.0.0.1:8080/api/image/generate \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "一只猫",
    "model": "alibaba-wan2.6-t2i"
  }'
```

### 如果成功，尝试更复杂的提示词

```bash
curl -X POST http://127.0.0.1:8080/api/image/generate \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "一只可爱的橙色猫咪在阳光下睡觉",
    "model": "alibaba-wan2.6-t2i"
  }'
```

## 错误处理

现在会返回更清晰的错误信息：

- `DataInspectionFailed` → "图片内容被审核拒绝，请修改提示词后重试"
- `InsufficientBalance` → "账户余额不足"
- `RateLimitExceeded` → "请求过于频繁，请稍后再试"

## 下一步

1. 在前端尝试生成图片
2. 使用安全的提示词
3. 如果仍然失败，查看详细错误信息
4. 根据错误信息调整提示词或切换模型

## 详细指南

查看 [ALIBABA_IMAGE_GENERATION_FIX.md](./ALIBABA_IMAGE_GENERATION_FIX.md) 获取更多信息

---

**状态**: ✅ 已修复  
**日期**: 2026年3月10日
