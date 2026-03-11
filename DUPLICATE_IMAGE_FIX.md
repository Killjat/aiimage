# 重复图片问题修复总结

## 问题分析

用户生成图片时，出现了两张重复的图片被保存到数据库，且这两张图片的 model 和 prompt 都是空的。

### 根本原因

在异步任务完成时，后端的 `getBailianTaskResult` 方法尝试自动保存图片到图片库，但是：
1. 任务结果中没有包含原始的模型和提示词信息
2. 导致保存的记录中 model 和 prompt 都是空的

## 解决方案

### 修改策略

**从后端自动保存改为前端主动保存**

这样做的好处：
1. 前端有完整的模型和提示词信息
2. 避免了信息丢失
3. 更清晰的数据流

### 具体修改

#### 1. 后端 (ImageController.php)

**移除**：`getBailianTaskResult` 方法中的自动保存逻辑

**添加**：新的 `saveImage` 方法
```php
public function saveImage(Request $request, Response $response): Response
{
    // 接收前端发送的图片信息
    // 保存到图片库
}
```

**注册路由**：`POST /api/image/save`

#### 2. 前端 (ImageGeneratorNew.tsx)

**修改**：`pollTaskResult` 函数
```typescript
// 异步任务完成后，调用后端 API 保存到图片库
await fetch(`${API_BASE_URL}/gallery/save`, {
    method: 'POST',
    headers,
    body: JSON.stringify({
        model: selectedModel.replace('alibaba/', ''),
        prompt: prompt,
        imageUrl: data.images[0],
        size: selectedBailianSize,
        negativePrompt: negativePrompt || null
    })
});
```

## 数据流

### 同步响应（立即返回图片）
```
用户生成图片
    ↓
后端调用 AI 服务
    ↓
图片立即返回
    ↓
后端自动保存到图片库（已有完整信息）
    ↓
完成
```

### 异步响应（需要轮询）
```
用户生成图片
    ↓
后端创建异步任务
    ↓
前端轮询任务状态
    ↓
任务完成，返回图片
    ↓
前端调用 /api/image/save 保存到图片库
    ↓
完成
```

## 修复步骤

1. ✅ 移除后端 `getBailianTaskResult` 中的自动保存逻辑
2. ✅ 添加后端 `saveImage` 方法
3. ✅ 注册 `POST /api/image/save` 路由
4. ✅ 修改前端 `pollTaskResult` 函数
5. ✅ 前端构建成功
6. ✅ 删除数据库中的 2 条错误记录

## 验证

### 数据库清理
```sql
DELETE FROM image_gallery WHERE id IN (38, 39);
```

### 结果
- ✅ 删除了 2 条无效记录
- ✅ 剩余记录都有完整的 model 和 prompt 信息

## 测试建议

1. 生成一张同步返回的图片（qwen-image-2.0）
   - 验证是否正确保存到图片库

2. 生成一张异步返回的图片（wan2.5-t2i-preview）
   - 等待任务完成
   - 验证是否正确保存到图片库

3. 在图片库中查看"我的图片"
   - 验证所有图片都有正确的 model 和 prompt

## 代码变更总结

### 后端
- `ImageController.php`: 添加 `saveImage` 方法，移除 `getBailianTaskResult` 中的保存逻辑
- `routes.php`: 添加 `POST /api/image/save` 路由

### 前端
- `ImageGeneratorNew.tsx`: 修改 `pollTaskResult` 函数，在异步任务完成后调用保存 API

### 数据库
- 删除 2 条无效记录

## 部署检查清单

- [x] 后端代码修改完成
- [x] 前端代码修改完成
- [x] 路由注册完成
- [x] 前端构建成功
- [x] 数据库清理完成
- [ ] 部署到生产环境
- [ ] 用户测试验证
