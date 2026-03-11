# 阿里巴巴与 OpenRouter 图片生成集成

## 概述

系统现已支持两个级别的图片生成服务提供商：
- **OpenRouter** - 国际 AI 模型聚合平台
- **阿里巴巴** - 国内领先的 AI 图片生成服务

两个服务现在处于同一级别，用户可以在前端自由选择使用哪个提供商的模型。

## 架构设计

### 服务层级

```
AIServiceManager (统一管理)
├── OpenRouter 图片生成
│   ├── Flux 2 Pro
│   ├── Flux 2 Flex
│   ├── DALL-E 3
│   └── 其他模型...
└── 阿里巴巴图片生成
    ├── 万相系列 (wan2.6-t2i, wan2.5-t2i-preview, 等)
    ├── 千问图像系列 (qwen-image-2.0-pro, qwen-image-2.0, 等)
    └── Stable Diffusion 系列
```

### 模型识别机制

系统通过模型 ID 前缀自动识别使用哪个服务：

- **阿里巴巴模型**: `alibaba-*` 前缀
  - 例如: `alibaba-wan2.6-t2i`, `alibaba-qwen-image-2.0-pro`
  
- **OpenRouter 模型**: 其他所有模型
  - 例如: `black-forest-labs/flux.2-pro`, `openai/gpt-image-1`

## 已集成的阿里巴巴模型

### 已通过测试的模型 ✅

| 模型 ID | 名称 | 类型 | 特点 |
|---------|------|------|------|
| `alibaba-wan2.6-t2i` | 万相 2.6 | 文生图 | 最新版本，超高质量，支持同步调用 |
| `alibaba-qwen-image-2.0-pro` | 千问图像 2.0 Pro | 文生图 | 满血版，最强文字渲染和真实质感 |
| `alibaba-qwen-image-2.0` | 千问图像 2.0 | 文生图 | 加速版，效果和性能最佳平衡 |
| `alibaba-qwen-image-max` | 千问图像 Max | 文生图 | 最高质量，真实性最强 |
| `alibaba-qwen-image-plus` | 千问图像 Plus | 文生图 | 高质量，文本渲染能力强 |
| `alibaba-qwen-image` | 千问图像 | 文生图 | 首代模型，文本渲染能力强 |

### 其他可用模型

| 模型 ID | 名称 | 类型 |
|---------|------|------|
| `alibaba-wan2.5-t2i-preview` | 万相 2.5 | 文生图 |
| `alibaba-wan2.2-t2i-flash` | 万相 2.2 Flash | 文生图 |
| `alibaba-wanx-v1` | 万相 V1 | 文生图 |
| `alibaba-stable-diffusion-v1.5` | Stable Diffusion v1.5 | 文生图 |
| `alibaba-stable-diffusion-xl` | Stable Diffusion XL | 文生图 |
| `alibaba-stable-diffusion-3.5-large` | Stable Diffusion 3.5 Large | 文生图 |
| `alibaba-qwen-image-edit-plus` | 千问图像编辑 Plus | 图像编辑 |

## 代码实现

### 后端修改

#### 1. AIServiceManager 增强

```php
// 新增方法：获取阿里巴巴图片生成模型列表
public function getAliBailianImageModels(): array

// 增强方法：generateImage 现在支持两个提供商
public function generateImage(
    string $model,
    string $prompt,
    ?string $baseImage = null,
    ?string $aspectRatio = null,
    ?string $imageSize = null
): array

// 新增私有方法：判断图片生成使用哪个服务
private function getImageServiceProvider(string $model): string
```

#### 2. ImageController 增强

```php
// 更新构造函数：注入 AliBailianService
public function __construct()

// 增强方法：getImageModels 现在返回两个提供商的模型
public function getImageModels(Request $request, Response $response): Response
```

### 前端修改

#### ImageGenerator.tsx 增强

```typescript
// 新增状态：动态模型列表
const [imageModels, setImageModels] = useState(DEFAULT_IMAGE_MODELS);

// 新增方法：加载图片生成模型列表
const loadImageModels = async () => {
  // 从 API 获取所有可用模型（OpenRouter + 阿里巴巴）
}

// 模型选择器现在显示来自两个提供商的模型
// 阿里巴巴模型显示 "阿里" 标签
```

## API 端点

### 获取所有图片生成模型

```
GET /api/image/models
```

**响应示例**:
```json
{
  "success": true,
  "models": [
    {
      "id": "alibaba-wan2.6-t2i",
      "name": "万相 2.6",
      "provider": "alibaba",
      "type": "image"
    },
    {
      "id": "alibaba-qwen-image-2.0-pro",
      "name": "千问图像 2.0 Pro",
      "provider": "alibaba",
      "type": "image"
    },
    {
      "id": "black-forest-labs/flux.2-pro",
      "name": "Flux 2 Pro",
      "provider": "openrouter",
      "type": "image"
    }
  ],
  "count": 3
}
```

### 生成图片

```
POST /api/image/generate
```

**请求体**:
```json
{
  "prompt": "一只可爱的猫",
  "model": "alibaba-wan2.6-t2i"
}
```

系统会自动识别模型提供商并调用相应的服务。

## 使用流程

### 前端用户流程

1. 用户打开图片生成器
2. 系统加载所有可用模型（OpenRouter + 阿里巴巴）
3. 用户选择模型（可以看到提供商标签）
4. 用户输入提示词
5. 点击生成按钮
6. 系统自动识别模型提供商并调用相应服务
7. 返回生成的图片

### 后端处理流程

```
用户请求 (model: "alibaba-wan2.6-t2i")
    ↓
ImageController.generate()
    ↓
AIServiceManager.generateImage()
    ↓
getImageServiceProvider() 识别为 "alibaba"
    ↓
AliBailianService.generateImage()
    ↓
返回图片 URL
```

## 配置要求

### 环境变量

```env
# 阿里巴巴配置
ALIBABA_API_KEY=your_api_key
ALIBABA_API_URL=https://dashscope.aliyuncs.com/api/v1

# OpenRouter 配置
OPENROUTER_API_KEY=your_api_key
OPENROUTER_API_URL=https://openrouter.ai/api/v1
```

## 测试

运行集成测试：

```bash
php backend/test_alibaba_integration.php
```

**测试内容**:
- ✅ 获取阿里巴巴图片生成模型列表
- ✅ 测试模型识别机制
- ✅ 验证已通过测试的模型

## 性能对比

| 提供商 | 模型数量 | 特点 | 适用场景 |
|--------|---------|------|---------|
| **OpenRouter** | 4+ | 国际模型，高质量 | 专业设计，高质量需求 |
| **阿里巴巴** | 13+ | 国内模型，文字渲染强 | 中文内容，文字渲染 |

## 故障排查

### 问题：模型列表为空

**解决方案**:
1. 检查 API Key 配置
2. 检查网络连接
3. 查看错误日志

### 问题：图片生成失败

**解决方案**:
1. 确认模型 ID 正确
2. 检查提示词长度
3. 查看 API 响应错误信息

## 未来改进

- [ ] 添加模型性能对比
- [ ] 实现自动模型选择（基于提示词长度、质量需求等）
- [ ] 添加模型缓存机制
- [ ] 支持更多阿里巴巴模型
- [ ] 实现模型成本统计

## 相关文档

- [API 提供商对比](./API_PROVIDERS.md)
- [OpenRouter 模型指南](./OPENROUTER_MODELS_GUIDE.md)
- [阿里巴巴集成完成](./ALIBABA_INTEGRATION_COMPLETE.md)

---

**最后更新**: 2026年3月10日  
**版本**: 1.0  
**状态**: ✅ 已完成集成
