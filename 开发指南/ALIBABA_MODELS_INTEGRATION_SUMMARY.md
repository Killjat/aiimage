# 阿里百练完整生图模型集成总结

## 集成完成

系统已成功集成阿里百练所有 13 个生图模型，通过调用阿里百练 API 获取完整的模型列表。

## 支持的模型列表

### 万相系列（4个）
1. **wan2.6-t2i** - 万相 2.6（最新，超高质量，支持同步调用）
2. **wan2.5-t2i-preview** - 万相 2.5（高质量预览版）
3. **wan2.2-t2i-flash** - 万相 2.2 Flash（快速版本）
4. **wanx-v1** - 万相 V1（风格控制，参考图片）

### Stable Diffusion 系列（3个）
5. **stable-diffusion-v1.5** - 经典开源模型
6. **stable-diffusion-xl** - 增强版，更高质量
7. **stable-diffusion-3.5-large** - 最新版本，最高质量

### 千问图像系列（6个）
8. **qwen-image-2.0-pro** - 千问 2.0 Pro（满血版，最强文字渲染）
9. **qwen-image-2.0** - 千问 2.0（加速版，效果和性能平衡）
10. **qwen-image-max** - 千问 Max（最高质量，真实性最强）
11. **qwen-image-plus** - 千问 Plus（高质量，文本渲染能力强）
12. **qwen-image** - 千问图像（首代模型）
13. **qwen-image-edit-plus** - 千问图像编辑 Plus（图像编辑模型）

## 技术实现

### 后端
- **文件**: `backend/src/Services/AliBailianService.php`
- **方法**: `getSupportedModels()` - 返回所有支持的模型及其配置
- **API 端点**: `GET /api/image/bailian/config` - 获取所有模型配置

### 前端
- **文件**: `frontend/src/components/ImageGeneratorNew.tsx`
- **模型列表**: `IMAGE_MODELS` 常量包含所有 13 个模型
- **模型选择**: 用户可在 UI 中选择任何模型进行生图

### 数据获取方式
使用 `backend/fetch_alibaba_models.php` 脚本通过阿里百练 API 获取完整的模型列表：
```bash
php backend/fetch_alibaba_models.php
```

## 模型特性对比

| 模型 | 质量 | 速度 | 文字渲染 | 风格控制 | 价格 |
|------|------|------|---------|---------|------|
| wan2.6-t2i | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ✅ | ❌ | 高 |
| wan2.5-t2i-preview | ⭐⭐⭐⭐ | ⭐⭐⭐ | ✅ | ❌ | 中 |
| wan2.2-t2i-flash | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ✅ | ❌ | 低 |
| wanx-v1 | ⭐⭐⭐⭐ | ⭐⭐⭐ | ✅ | ✅ | 中 |
| SD v1.5 | ⭐⭐⭐ | ⭐⭐⭐⭐ | ❌ | ❌ | 低 |
| SD XL | ⭐⭐⭐⭐ | ⭐⭐⭐ | ❌ | ❌ | 中 |
| SD 3.5 Large | ⭐⭐⭐⭐⭐ | ⭐⭐ | ❌ | ❌ | 高 |
| 千问 2.0 Pro | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ❌ | 高 |
| 千问 2.0 | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ❌ | 中 |
| 千问 Max | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ❌ | 高 |
| 千问 Plus | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ❌ | 中 |
| 千问图像 | ⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ | ❌ | 低 |

## 使用建议

### 场景 1: 最高质量
推荐使用: **wan2.6-t2i** 或 **千问 2.0 Pro**
- 适合专业设计、商业用途
- 生成时间: 10-30 秒

### 场景 2: 文字渲染
推荐使用: **千问 2.0 Pro** 或 **千问 Max**
- 适合需要精确文字的设计
- 文字渲染能力最强

### 场景 3: 快速生成
推荐使用: **wan2.2-t2i-flash** 或 **千问 2.0**
- 适合快速迭代
- 生成时间: 3-10 秒

### 场景 4: 风格控制
推荐使用: **wanx-v1**
- 支持 10+ 种风格
- 支持参考图片

### 场景 5: 成本优先
推荐使用: **SD v1.5** 或 **千问图像**
- 最低成本
- 质量可接受

## API 调用示例

### 获取所有模型配置
```bash
curl http://127.0.0.1:8080/api/image/bailian/config
```

### 使用特定模型生成图片
```bash
curl -X POST http://127.0.0.1:8080/api/image/generate/bailian \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "一只可爱的橘猫",
    "model": "wan2.6-t2i",
    "size": "1280*1280"
  }'
```

## 文件清单

- `backend/src/Services/AliBailianService.php` - 阿里百练服务
- `backend/src/Controllers/ImageController.php` - 图片控制器
- `frontend/src/components/ImageGeneratorNew.tsx` - 前端图片生成器
- `backend/fetch_alibaba_models.php` - 模型列表获取脚本
- `ALIBABA_BAILIAN_MODELS.md` - 详细文档

## 下一步

1. 根据实际需求选择合适的模型
2. 监控各模型的生成质量和速度
3. 根据用户反馈优化模型选择
4. 定期更新模型列表（阿里百练会持续发布新模型）

---

**最后更新**: 2026年3月10日  
**版本**: V2.0（完整集成）
