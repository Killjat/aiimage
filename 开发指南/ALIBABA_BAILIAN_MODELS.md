# 阿里百练图片生成模型集成指南

## 概述

本系统已集成阿里百练的所有主要图片生成模型，提供多种选择以满足不同的生成需求。

**注意**: 完整的模型列表请访问阿里百练模型广场：
https://bailian.console.aliyun.com/cn-beijing/?tab=model#/efm/model_experience_center/vision?currentTab=imageGenerate

## 支持的模型

### 1. 万相 V1 (wanx-v1)
- **名称**: 万相 V1
- **描述**: 阿里自研高质量图片生成模型
- **类别**: 文本到图像 (Text-to-Image)
- **特性**:
  - 风格控制（10+ 种风格）
  - 参考图片支持
  - 反向提示词支持
- **支持尺寸**: 
  - 1024×1024 (1:1 正方形)
  - 720×1280 (9:16 竖屏)
  - 1280×720 (16:9 横屏)
- **支持风格**:
  - 自动 (Auto)
  - 摄影 (Photography)
  - 人像写真 (Portrait)
  - 3D卡通 (3D Cartoon)
  - 动画 (Anime)
  - 油画 (Oil Painting)
  - 水彩 (Watercolor)
  - 素描 (Sketch)
  - 中国画 (Chinese Painting)
  - 扁平插画 (Flat Illustration)
- **推荐场景**: 需要风格控制和高质量输出的场景

### 2. Stable Diffusion v1.5
- **名称**: Stable Diffusion v1.5
- **描述**: 经典开源图片生成模型
- **类别**: 文本到图像 (Text-to-Image)
- **特性**:
  - 高质量生成
  - 快速生成速度
  - 参考图片支持
- **支持尺寸**:
  - 512×512
  - 768×768
  - 1024×1024
- **推荐场景**: 需要快速生成和经典风格的场景

### 3. Stable Diffusion XL
- **名称**: Stable Diffusion XL
- **描述**: 增强版 Stable Diffusion，更高质量
- **类别**: 文本到图像 (Text-to-Image)
- **特性**:
  - 超高质量生成
  - 细节丰富
  - 参考图片支持
- **支持尺寸**:
  - 768×768
  - 1024×1024
  - 1280×1280
- **推荐场景**: 需要超高质量和细节丰富的场景

### 4. Stable Diffusion 3.5 Large
- **名称**: Stable Diffusion 3.5 Large
- **描述**: 最新版本，最高质量和准确度
- **类别**: 文本到图像 (Text-to-Image)
- **特性**:
  - 最高质量生成
  - 精准理解提示词
  - 参考图片支持
- **支持尺寸**:
  - 1024×1024
  - 1280×1280
  - 1536×1536
- **推荐场景**: 需要最高质量和最精准理解的场景

## 使用方法

### 前端调用

在前端选择模型后，系统会自动调整可用的参数：

```typescript
// 选择模型
const selectedModel = 'alibaba/wanx-v1';

// 构建请求
const requestBody = {
  prompt: '一只可爱的橘猫在阳光下打盹',
  model: 'wanx-v1',  // 去掉 'alibaba/' 前缀
  style: '<photography>',  // 仅 wanx-v1 支持
  size: '1024*1024'
};

// 发送请求
const response = await fetch('/api/image/generate/bailian', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(requestBody)
});
```

### 后端 API

#### 生成图片

**端点**: `POST /api/image/generate/bailian`

**请求参数**:
```json
{
  "prompt": "图片描述",
  "model": "wanx-v1|stable-diffusion-v1.5|stable-diffusion-xl|stable-diffusion-3.5-large",
  "size": "1024*1024|720*1280|1280*720|512*512|768*768|1280*1280|1536*1536",
  "style": "<auto>|<photography>|<portrait>|<3d cartoon>|<anime>|<oil painting>|<watercolor>|<sketch>|<chinese painting>|<flat illustration>",
  "negative_prompt": "不想要的内容（可选）"
}
```

**响应**:
```json
{
  "success": true,
  "task_id": "任务ID",
  "message": "任务创建成功，正在生成图片...",
  "quota": {
    "total": 总配额,
    "used": 已使用,
    "remaining": 剩余
  }
}
```

#### 查询任务结果

**端点**: `GET /api/image/bailian/task/{taskId}`

**响应**:
```json
{
  "success": true,
  "status": "completed|processing|failed",
  "images": ["图片URL"],
  "message": "状态信息"
}
```

#### 获取配置

**端点**: `GET /api/image/bailian/config`

**响应**:
```json
{
  "success": true,
  "config": {
    "models": { /* 所有模型信息 */ },
    "sizes": { /* 支持的尺寸 */ },
    "styles": { /* 支持的风格 */ },
    "provider": "alibaba_bailian"
  }
}
```

## 模型对比

| 特性 | wanx-v1 | SD v1.5 | SD XL | SD 3.5 Large |
|------|---------|---------|-------|--------------|
| 质量 | ⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| 速度 | ⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐ |
| 风格控制 | ✅ | ❌ | ❌ | ❌ |
| 参考图片 | ✅ | ✅ | ✅ | ✅ |
| 反向提示词 | ✅ | ❌ | ❌ | ❌ |
| 最大尺寸 | 1280×720 | 1024×1024 | 1280×1280 | 1536×1536 |

## 提示词最佳实践

### wanx-v1
```
好的提示词: "一只可爱的橘猫在阳光下打盹，摄影风格，高质量"
不好的提示词: "猫"
```

### Stable Diffusion 系列
```
好的提示词: "A cute orange cat sleeping in the sun, professional photography, high quality, detailed"
不好的提示词: "cat"
```

## 配额管理

- 每个用户有每日图片生成配额
- 游客用户有限制的配额
- 登录用户可获得更多配额
- 配额在每次生成时扣除，失败不退还

## 故障排除

### 403 Forbidden 错误
- 检查 API Key 是否正确配置
- 确认 API Key 有权限访问该模型
- 检查账户是否有足够的额度

### 任务超时
- 某些模型生成时间较长
- 系统最多轮询 60 次（约 5 分钟）
- 如果超时，请稍后重试

### 模型不支持
- 确认选择的模型在支持列表中
- 检查参数是否符合模型要求
- 某些参数仅特定模型支持

## 技术实现

### 后端架构

```
AliBailianService
├── generateImage()          # 生成图片
├── getTaskResult()          # 查询任务结果
├── getSupportedModels()     # 获取所有模型
├── getModelInfo()           # 获取模型信息
├── getModelSizes()          # 获取模型支持的尺寸
└── validateApiKey()         # 验证 API Key
```

### 前端架构

```
ImageGeneratorNew
├── 模型选择
├── 参数配置（根据模型动态显示）
├── 图片生成
├── 任务轮询
└── 结果展示
```

## 配置

### 环境变量

```env
ALIBABA_BAILIAN_API_KEY=your_api_key_here
```

### 支持的尺寸映射

```php
'1024*1024' => '1:1 正方形'
'720*1280'  => '9:16 竖屏'
'1280*720'  => '16:9 横屏'
'512*512'   => '512×512'
'768*768'   => '768×768'
'1280*1280' => '1280×1280'
'1536*1536' => '1536×1536'
```

## 更新日志

### v1.0 (2026-03-10)
- 集成 wanx-v1 模型
- 集成 Stable Diffusion v1.5
- 集成 Stable Diffusion XL
- 集成 Stable Diffusion 3.5 Large
- 支持模型动态参数配置
- 完整的前后端集成

## 相关文档

- [API 提供商对比](./API_PROVIDERS.md)
- [快速参考](./QUICK_REFERENCE.md)
- [用户指南](./USER_GUIDE.md)

---

**最后更新**: 2026年3月10日  
**版本**: V1.0
