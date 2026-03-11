# 快速参考卡片

## 🎨 图片生成模型速查表

| 模型 | 速度 | 质量 | 特点 | 推荐场景 |
|------|------|------|------|----------|
| 🍌 Nano Banana 2<br>`google/gemini-3.1-flash-image-preview` | ⚡⚡⚡⚡ | ⭐⭐⭐⭐⭐ | 支持4K，最新最强 | **默认推荐** |
| 🍌 Nano Banana<br>`google/gemini-2.5-flash-image` | ⚡⚡⚡⚡⚡ | ⭐⭐⭐⭐ | 速度极快（3.2秒） | 快速生成 |
| ⚡ Flux 2 Pro<br>`black-forest-labs/flux.2-pro` | ⚡⚡⚡ | ⭐⭐⭐⭐⭐ | 顶级质量 | 专业级作品 |
| ⚡ Flux 2 Flex<br>`black-forest-labs/flux.2-flex` | ⚡⚡⚡⚡ | ⭐⭐⭐⭐ | 平衡质量和速度 | 日常使用 |
| 🎨 Riverflow V2 Pro<br>`riverflow-ai/riverflow-v2-pro` | ⚡⚡⚡ | ⭐⭐⭐⭐⭐ | 完美文字渲染 | 海报、广告 |
| 🎨 Riverflow V2 Fast<br>`riverflow-ai/riverflow-v2-fast` | ⚡⚡⚡⚡⚡ | ⭐⭐⭐ | 快速生成 | 批量生成 |

## ⚙️ 高级选项

### 宽高比选项
- **1:1** - 正方形（Instagram、头像）
- **16:9** - 横屏（桌面壁纸、YouTube封面）
- **9:16** - 竖屏（手机壁纸、Stories）
- **4:3** - 标准（传统照片）
- **3:4** - 竖版（海报、杂志）

### 分辨率选项
- **1K** (1024px) - 快速预览、社交媒体
- **2K** (2048px) - 标准输出、网页使用（推荐）
- **4K** (4096px) - 高质量打印、专业作品

### 图片编辑功能
- 上传现有图片（PNG/JPG/WEBP/GIF，最大5MB）
- 描述想要的修改
- AI 会基于原图进行编辑

## 💬 聊天模型

- **总数**: 346 个模型
- **推荐**: 选择 "Auto" 让系统自动选择最佳模型
- **免费模型**: 
  - `liquid/lfm-2.5-1.2b-instruct:free`
  - `stepfun/step-3.5-flash:free`
  - 更多免费模型可在下拉框中查看

## 🚀 快速启动命令

### 后端
```bash
cd backend
php -S 0.0.0.0:8080 -t public
```

### 前端
```bash
cd frontend
npm run dev
```

## 📝 提示词模板

### 简单风格
```
一只可爱的猫咪
```

### 详细风格
```
一幅水彩画风格的插画，描绘一只橘色的猫咪坐在木质窗台上，温暖的阳光从窗户照进来，在猫咪身上形成柔和的光影。背景是模糊的室内场景，整体色调温暖明亮。
```

### 专业风格
```
一个未来主义风格的城市景观，高耸的摩天大楼，飞行汽车在空中穿梭，霓虹灯闪烁，赛博朋克风格，夜景，4K高清，电影级光影效果，超现实主义
```

### 英文提示词
```
A photorealistic portrait of a cute orange cat sitting on a wooden windowsill, warm sunlight streaming through the window, soft shadows, cozy interior background, shallow depth of field, 4K, professional photography
```

## 🔧 常用配置

### 环境变量（后端）
```env
OPENROUTER_API_KEY=your_api_key_here
OPENROUTER_API_URL=https://openrouter.ai/api/v1
APP_URL=http://127.0.0.1:8080
CORS_ALLOWED_ORIGINS=http://localhost:5173
```

### 环境变量（前端）
```env
VITE_API_BASE_URL=http://127.0.0.1:8080/api
```

## 📊 API 端点速查

### 聊天
- `GET /api/health` - 健康检查
- `GET /api/models` - 获取所有模型
- `POST /api/chat/send` - 发送消息

### 图片生成
- `POST /api/image/generate` - 生成图片
- `GET /api/image/models` - 获取图片模型

## 🐛 常见问题

### 403 错误
- 检查 API Key 是否正确
- 尝试切换其他模型
- 确认 VPN 是否正常

### 图片不显示
- 检查浏览器控制台错误
- 确认后端服务正在运行
- 查看后端日志

### 生成速度慢
- 尝试使用 Nano Banana 或 Riverflow V2 Fast
- 检查网络连接
- 降低图片分辨率

## 📚 相关文档

- [README.md](./README.md) - 项目概述
- [IMAGE_MODELS.md](./IMAGE_MODELS.md) - 模型详细说明
- [TEST_IMAGE_GENERATION.md](./TEST_IMAGE_GENERATION.md) - 测试指南
- [DEPLOYMENT.md](./DEPLOYMENT.md) - 部署说明
- [TEST_REPORT.md](./TEST_REPORT.md) - 测试报告

## 💡 提示

1. **首次使用**: 推荐使用 Nano Banana 2，质量和速度都很好
2. **追求质量**: 使用 Flux 2 Pro 或 Riverflow V2 Pro
3. **追求速度**: 使用 Nano Banana 或 Riverflow V2 Fast
4. **文字渲染**: 必须使用 Riverflow V2 Pro
5. **中文提示词**: Gemini 系列（Nano Banana）支持最好
6. **批量生成**: 使用 Riverflow V2 Fast 或 Nano Banana

## 🎯 最佳实践

### 提示词编写
- ✅ 详细描述场景、风格、光影
- ✅ 指定艺术风格（水彩、油画、摄影等）
- ✅ 说明分辨率要求（4K、高清等）
- ❌ 避免过于简短的描述
- ❌ 避免矛盾的要求

### 模型选择
- 日常使用: Nano Banana 2
- 专业作品: Flux 2 Pro
- 快速预览: Nano Banana
- 文字海报: Riverflow V2 Pro

### 性能优化
- 使用 localStorage 缓存模型选择
- 避免频繁切换模型
- 合理设置图片分辨率
- 批量生成时使用快速模型
