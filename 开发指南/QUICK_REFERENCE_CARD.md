# 快速参考卡片

## 已集成的模型

### 阿里巴巴模型 (6 个已通过测试)

| 模型 ID | 名称 | 用途 |
|---------|------|------|
| `alibaba-wan2.6-t2i` | 万相 2.6 | 最高质量 |
| `alibaba-qwen-image-2.0-pro` | 千问图像 2.0 Pro | 文字渲染 |
| `alibaba-qwen-image-2.0` | 千问图像 2.0 | 快速生成 |
| `alibaba-qwen-image-max` | 千问图像 Max | 真实人物 |
| `alibaba-qwen-image-plus` | 千问图像 Plus | 文字渲染 |
| `alibaba-qwen-image` | 千问图像 | 成本最低 |

### OpenRouter 模型

| 模型 ID | 名称 | 用途 |
|---------|------|------|
| `black-forest-labs/flux.2-pro` | Flux 2 Pro | 专业设计 |
| `black-forest-labs/flux.2-flex` | Flux 2 Flex | 快速生成 |
| 其他 | 其他模型 | 多样选择 |

## API 端点

### 获取模型列表
```
GET /api/image/models
```

### 生成图片
```
POST /api/image/generate
Content-Type: application/json

{
  "prompt": "描述",
  "model": "alibaba-wan2.6-t2i"
}
```

## 模型选择指南

| 需求 | 推荐模型 |
|------|---------|
| 最高质量 | `alibaba-wan2.6-t2i` |
| 文字渲染 | `alibaba-qwen-image-2.0-pro` |
| 快速生成 | `alibaba-qwen-image-2.0` |
| 真实人物 | `alibaba-qwen-image-max` |
| 成本最低 | `alibaba-qwen-image` |
| 专业设计 | `black-forest-labs/flux.2-pro` |

## 代码示例

### 前端调用

```typescript
// 获取模型列表
const response = await fetch('/api/image/models');
const data = await response.json();
const models = data.models;

// 生成图片
const result = await fetch('/api/image/generate', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    prompt: '一只可爱的猫',
    model: 'alibaba-wan2.6-t2i'
  })
});
```

### 后端调用

```php
// 使用 AIServiceManager
$aiServiceManager->generateImage(
    'alibaba-wan2.6-t2i',
    '一只可爱的猫'
);
```

## 环境变量

```env
ALIBABA_API_KEY=your_key
ALIBABA_API_URL=https://dashscope.aliyuncs.com/api/v1
OPENROUTER_API_KEY=your_key
OPENROUTER_API_URL=https://openrouter.ai/api/v1
```

## 常见问题

**Q: 如何选择模型？**  
A: 根据需求选择：文字渲染→千问系列，最高质量→万相2.6，快速→千问2.0

**Q: 模型生成失败？**  
A: 检查提示词长度（<2100字符）、API Key、网络连接

**Q: 如何查看所有模型？**  
A: 调用 `/api/image/models` 端点或在前端图片生成器中查看

**Q: 阿里巴巴和 OpenRouter 有什么区别？**  
A: 阿里巴巴文字渲染强，OpenRouter 模型多样性强

## 测试命令

```bash
# 获取模型列表
curl http://localhost:8000/api/image/models

# 生成图片
curl -X POST http://localhost:8000/api/image/generate \
  -H "Content-Type: application/json" \
  -d '{"prompt":"test","model":"alibaba-wan2.6-t2i"}'

# 运行集成测试
php backend/test_alibaba_integration.php
```

## 文件位置

| 文件 | 位置 |
|------|------|
| 服务管理 | `backend/src/Services/AIServiceManager.php` |
| 控制器 | `backend/src/Controllers/ImageController.php` |
| 前端组件 | `frontend/src/components/ImageGenerator.tsx` |
| 测试脚本 | `backend/test_alibaba_integration.php` |

## 文档

| 文档 | 内容 |
|------|------|
| ALIBABA_OPENROUTER_INTEGRATION.md | 详细集成文档 |
| ALIBABA_MODELS_QUICK_START.md | 快速开始 |
| DEPLOYMENT_GUIDE.md | 部署指南 |
| FINAL_REPORT.md | 最终报告 |

## 状态检查

```bash
# 检查后端
curl http://localhost:8000/api/image/models

# 检查前端
curl http://localhost:5173

# 检查日志
tail -f backend/logs/error.log
```

## 性能指标

- 模型识别: < 1ms
- 模型列表加载: < 500ms
- 图片生成: 取决于模型

## 支持

遇到问题？查看：
- [集成文档](./ALIBABA_OPENROUTER_INTEGRATION.md)
- [快速开始](./ALIBABA_MODELS_QUICK_START.md)
- [部署指南](./DEPLOYMENT_GUIDE.md)

---

**版本**: 1.0  
**更新**: 2026年3月10日  
**状态**: ✅ 已完成
