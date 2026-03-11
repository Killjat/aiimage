# Flux 模型集成完成

## 概述
成功集成 OpenRouter 的 Flux 图片生成模型到系统中。Flux 模型返回 base64 编码的图像数据，需要特殊处理。

## 已测试的模型
- ✅ `black-forest-labs/flux.2-pro` - 高质量图片生成
- ⚠️ `black-forest-labs/flux.2-flex` - 快速生成（偶尔服务不可用）

## 实现细节

### 后端 (PHP)

#### OpenRouterService.php
- 使用 `/chat/completions` 端点生成图片
- 设置 `modalities: ['image']` 参数
- 返回格式：base64 data URL (`data:image/png;base64,...`)
- 响应结构：
  ```php
  [
    'image_url' => 'data:image/png;base64,...',  // base64 data URL
    'prompt' => '...',
    'model' => 'black-forest-labs/flux.2-pro',
    'format' => 'base64'
  ]
  ```

#### ImageController.php
- 检查模型前缀判断是否为 Alibaba 模型
- Alibaba 模型：调用 `/api/image/generate/bailian`
- 其他模型（包括 Flux）：调用 `/api/image/generate`
- 正确处理 base64 data URL 的 JSON 编码

#### AIServiceManager.php
- 路由 Flux 模型到 OpenRouterService
- 统一返回格式

### 前端 (React/TypeScript)

#### ImageGenerator.tsx
- 模型列表包含 8 个模型：
  - 6 个 Alibaba 模型（带 `alibaba-` 前缀）
  - 2 个 Flux 模型
- 根据模型提供商路由请求：
  - Alibaba：POST `/api/image/generate/bailian`
  - Flux：POST `/api/image/generate`
- 改进的 `downloadImage()` 函数：
  - 检测 base64 data URL
  - 转换为 Blob 对象
  - 使用 `URL.createObjectURL()` 创建可下载链接
  - 正确释放资源

#### 图像显示
- `<img>` 标签直接支持 base64 data URL
- 无需额外处理

## 测试结果

### 后端测试
```
✅ OpenRouterService.generateImage() - 返回 base64 data URL
✅ AIServiceManager.generateImage() - 正确路由和处理
✅ JSON 编码/解码 - 支持大型 base64 数据（1.4-1.6 MB）
```

### 前端测试
- ✅ 图像显示：base64 data URL 正确显示
- ✅ 图像下载：base64 转 Blob 下载正常
- ✅ 配额管理：正确扣除和显示

## 数据流

```
前端请求
  ↓
ImageController.generate()
  ↓
AIServiceManager.generateImage()
  ↓
OpenRouterService.generateImage()
  ↓
OpenRouter API (chat/completions)
  ↓
返回 base64 data URL
  ↓
后端返回 JSON 响应
  ↓
前端显示和下载
```

## 关键技术点

### Base64 Data URL 处理
- 格式：`data:image/png;base64,<base64-encoded-data>`
- 大小：通常 1-2 MB
- 浏览器支持：所有现代浏览器

### 下载 Base64 图像
```typescript
// 1. 分割 data URL
const arr = imageUrl.split(',');
const mime = arr[0].match(/:(.*?);/)?.[1] || 'image/png';

// 2. 解码 base64
const bstr = atob(arr[1]);
const u8arr = new Uint8Array(bstr.length);
for (let i = 0; i < bstr.length; i++) {
  u8arr[i] = bstr.charCodeAt(i);
}

// 3. 创建 Blob
const blob = new Blob([u8arr], { type: mime });

// 4. 创建下载链接
const url = URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = 'image.png';
a.click();
URL.revokeObjectURL(url);
```

## 配置

### 环境变量
- `OPENROUTER_API_KEY` - OpenRouter API 密钥
- `OPENROUTER_API_URL` - OpenRouter API 地址（默认：https://openrouter.ai/api/v1）

### 模型配置
在 `ImageGenerator.tsx` 中定义：
```typescript
const DEFAULT_IMAGE_MODELS = [
  // Alibaba 模型
  { id: 'alibaba-wan2.6-t2i', name: '万相 2.6', provider: 'alibaba' },
  // ... 其他 Alibaba 模型
  // Flux 模型
  { id: 'black-forest-labs/flux.2-pro', name: 'Flux 2 Pro', provider: 'openrouter' },
  { id: 'black-forest-labs/flux.2-flex', name: 'Flux 2 Flex', provider: 'openrouter' },
];
```

## 已知问题

1. **Flux 2 Flex 偶尔不可用**
   - 返回 503 Service Unavailable
   - 建议使用 Flux 2 Pro 作为主要选择

2. **Base64 数据大小**
   - 单个图像可能达到 1-2 MB
   - 确保服务器和浏览器支持大型 JSON 响应

## 下一步

1. 添加更多 Flux 模型（如果 OpenRouter 支持）
2. 实现图像缓存以减少重复请求
3. 添加图像质量选项
4. 实现批量图像生成

## 参考

- [OpenRouter API 文档](https://openrouter.ai/docs)
- [Flux 模型文档](https://blackforestlabs.ai/)
- [Base64 Data URL 规范](https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/Data_URIs)
