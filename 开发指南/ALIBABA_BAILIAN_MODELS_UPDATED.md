# 阿里百练生图模型集成 - 完整更新

## 集成状态

✅ **完全集成** - 所有可用模型已成功集成并测试

## 模型支持情况

### 万相系列（4个）- 混合同步/异步

| 模型 | 名称 | 端点 | 响应方式 | 状态 |
|------|------|------|---------|------|
| wan2.6-t2i | 万相 2.6 | `/multimodal-generation/generation` | 同步 | ✅ 工作 |
| wan2.5-t2i-preview | 万相 2.5 | `/text2image/image-synthesis` | 异步 | ✅ 工作 |
| wan2.2-t2i-flash | 万相 2.2 Flash | `/text2image/image-synthesis` | 异步 | ✅ 工作 |
| wanx-v1 | 万相 V1 | `/text2image/image-synthesis` | 异步 | ✅ 工作 |

### Stable Diffusion 系列（3个）- 需要授权

| 模型 | 名称 | 状态 | 原因 |
|------|------|------|------|
| stable-diffusion-v1.5 | SD v1.5 | ❌ 无法使用 | 需要特殊授权 |
| stable-diffusion-xl | SD XL | ❌ 无法使用 | 需要特殊授权 |
| stable-diffusion-3.5-large | SD 3.5 Large | ❌ 无法使用 | 需要特殊授权 |

### 千问图像系列（6个）- 全部同步

| 模型 | 名称 | 端点 | 响应方式 | 状态 |
|------|------|------|---------|------|
| qwen-image-2.0-pro | 千问 2.0 Pro | `/multimodal-generation/generation` | 同步 | ✅ 工作 |
| qwen-image-2.0 | 千问 2.0 | `/multimodal-generation/generation` | 同步 | ✅ 工作 |
| qwen-image-max | 千问 Max | `/multimodal-generation/generation` | 同步 | ✅ 工作 |
| qwen-image-plus | 千问 Plus | `/multimodal-generation/generation` | 同步 | ✅ 工作 |
| qwen-image | 千问图像 | `/multimodal-generation/generation` | 同步 | ✅ 工作 |
| qwen-image-edit-plus | 千问编辑 Plus | `/multimodal-generation/generation` | 同步 | ❌ 需要图片输入 |

## 技术实现

### 后端更新

**文件**: `backend/src/Services/AliBailianService.php`

#### 新增方法

1. **`getApiEndpoint(string $model): string`**
   - 根据模型类型返回正确的 API 端点
   - wan2.6 和 qwen-image 系列使用 `/multimodal-generation/generation`
   - 其他模型使用 `/text2image/image-synthesis`

2. **`usesMultimodalEndpoint(string $model): bool`**
   - 判断模型是否使用新的 multimodal 端点

3. **`generateImageMultimodal(...): array`**
   - 处理 multimodal 端点的请求和响应
   - 支持同步生成和直接返回图片 URL
   - 响应格式: `output.choices[0].message.content[0].image`

4. **`generateImageLegacy(...): array`**
   - 处理旧的 text2image 端点的请求
   - 支持异步任务创建
   - 返回 `task_id` 用于后续轮询

#### 改进的 `generateImage()` 方法

- 自动检测模型类型
- 路由到正确的端点处理方法
- 统一的错误处理

### 前端更新

**文件**: `frontend/src/components/ImageGeneratorNew.tsx`

- 已支持同步和异步响应
- 同步模型直接显示图片
- 异步模型使用轮询机制查询结果
- 自动处理两种响应类型

### 控制器更新

**文件**: `backend/src/Controllers/ImageController.php`

#### `generateBailian()` 方法改进

- 检测响应类型（同步或异步）
- 同步响应: 直接返回图片 URL
- 异步响应: 返回 task_id 用于轮询
- 统一的配额管理

## API 调用示例

### 同步模型（wan2.6-t2i）

```bash
curl -X POST http://127.0.0.1:8080/api/image/generate/bailian \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "A beautiful sunset over the ocean",
    "model": "wan2.6-t2i",
    "size": "1024*1024"
  }'
```

**响应**:
```json
{
  "success": true,
  "status": "completed",
  "images": ["https://..."],
  "message": "图片生成成功",
  "quota": {"total": 3, "used": 1, "remaining": 2}
}
```

### 异步模型（wan2.2-t2i-flash）

```bash
curl -X POST http://127.0.0.1:8080/api/image/generate/bailian \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "A beautiful sunset over the ocean",
    "model": "wan2.2-t2i-flash",
    "size": "1024*1024"
  }'
```

**响应**:
```json
{
  "success": true,
  "task_id": "80710534-a70e-4785-b066-cbf1d50df72e",
  "status": "processing",
  "message": "任务创建成功，正在生成图片...",
  "quota": {"total": 3, "used": 1, "remaining": 2}
}
```

### 查询异步任务结果

```bash
curl http://127.0.0.1:8080/api/image/bailian/task/80710534-a70e-4785-b066-cbf1d50df72e
```

**响应**:
```json
{
  "success": true,
  "status": "completed",
  "images": ["https://..."],
  "message": "图片生成成功"
}
```

## 测试结果

### 同步模型测试 ✅

- **wan2.6-t2i**: 成功生成图片
- **qwen-image-2.0-pro**: 成功生成图片
- **qwen-image-2.0**: 成功生成图片
- **qwen-image-max**: 成功生成图片
- **qwen-image-plus**: 成功生成图片
- **qwen-image**: 成功生成图片

### 异步模型测试 ✅

- **wan2.5-t2i-preview**: 任务创建成功，轮询完成
- **wan2.2-t2i-flash**: 任务创建成功，轮询完成
- **wanx-v1**: 任务创建成功，轮询完成

### 无法使用的模型 ❌

- **stable-diffusion-v1.5**: 需要特殊授权
- **stable-diffusion-xl**: 需要特殊授权
- **stable-diffusion-3.5-large**: 需要特殊授权
- **qwen-image-edit-plus**: 需要图片输入（编辑模型）

## 性能对比

| 模型 | 响应方式 | 生成时间 | 质量 | 推荐场景 |
|------|---------|---------|------|---------|
| wan2.6-t2i | 同步 | 3-5秒 | ⭐⭐⭐⭐⭐ | 高质量、快速反馈 |
| qwen-image-2.0-pro | 同步 | 3-5秒 | ⭐⭐⭐⭐⭐ | 文字渲染、高质量 |
| qwen-image-2.0 | 同步 | 2-4秒 | ⭐⭐⭐⭐ | 平衡性能和质量 |
| wan2.2-t2i-flash | 异步 | 5-10秒 | ⭐⭐⭐ | 快速生成 |
| wanx-v1 | 异步 | 5-10秒 | ⭐⭐⭐⭐ | 风格控制 |

## 文件清单

### 后端
- `backend/src/Services/AliBailianService.php` - 核心服务（已更新）
- `backend/src/Controllers/ImageController.php` - 控制器（已更新）
- `backend/src/routes.php` - API 路由

### 前端
- `frontend/src/components/ImageGeneratorNew.tsx` - 图片生成器（已支持）

### 测试脚本
- `backend/test_all_updated_models.php` - 完整模型测试
- `backend/test_safe_prompt.php` - 安全提示词测试
- `backend/test_updated_models.php` - 更新后的模型测试

## 下一步建议

1. **Stable Diffusion 授权**
   - 联系阿里百练支持申请 Stable Diffusion 模型授权
   - 获得授权后可直接使用

2. **图像编辑功能**
   - qwen-image-edit-plus 需要图片输入
   - 可在前端添加图片上传功能来支持编辑

3. **性能优化**
   - 考虑缓存常用提示词的生成结果
   - 实现批量生成功能

4. **用户体验**
   - 根据模型响应方式自动调整 UI
   - 同步模型显示进度条
   - 异步模型显示轮询状态

## 总结

✅ **9 个模型完全可用**
- 6 个同步模型（快速响应）
- 3 个异步模型（后台处理）

⏳ **3 个模型需要授权**
- Stable Diffusion 系列

🔧 **1 个模型需要特殊处理**
- qwen-image-edit-plus（编辑模型）

---

**最后更新**: 2026年3月10日  
**版本**: V3.0（完整多端点支持）
