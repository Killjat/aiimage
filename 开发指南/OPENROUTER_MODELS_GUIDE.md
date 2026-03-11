# OpenRouter 模型使用指南

## 📋 目录

- [聊天对话模型](#聊天对话模型)
- [写作创作模型](#写作创作模型)
- [代码编程模型](#代码编程模型)
- [图片生成模型](#图片生成模型)
- [推理思考模型](#推理思考模型)
- [多模态模型](#多模态模型)
- [成本优化建议](#成本优化建议)

---

## 🗣️ 聊天对话模型

### 推荐模型

#### 1. GPT-5.4 (OpenAI)
- **价格**: $2.50/$15 per 1M tokens
- **上下文**: 1M tokens
- **特点**: 
  - OpenAI 2026年3月最新旗舰模型
  - 统一了 Codex 和 GPT 产品线
  - 内置计算机使用能力
  - 比前代减少 33% 错误率
- **适用场景**: 
  - 复杂多轮对话
  - 需要长上下文的讨论
  - 专业咨询和建议
- **模型ID**: `openai/gpt-5.4`

#### 2. Claude Sonnet 4.6 (Anthropic)
- **价格**: $3/$15 per 1M tokens
- **上下文**: 1M tokens
- **特点**:
  - Anthropic 中端主力模型
  - 改进的推理和代理规划能力
  - 自然流畅的对话风格
  - 理解细微差别和隐含意图
- **适用场景**:
  - 日常聊天对话
  - 客服机器人
  - 教育辅导
- **模型ID**: `anthropic/claude-sonnet-4.6`

#### 3. Gemini 3.1 Flash Lite (Google)
- **价格**: $0.25/$1.50 per 1M tokens
- **上下文**: 1M tokens
- **特点**:
  - Google 最快模型（2026年3月）
  - 首次响应时间比 2.5 Flash 快 2.5 倍
  - 成本仅为 Pro 版本的 1/8
  - 高性价比
- **适用场景**:
  - 大量对话场景
  - 成本敏感应用
  - 快速响应需求
- **模型ID**: `google/gemini-3.1-flash-lite`

#### 4. DeepSeek Chat (DeepSeek)
- **价格**: $0.14/$0.28 per 1M tokens
- **上下文**: 64K tokens
- **特点**:
  - 极致性价比
  - 中文能力强
  - 适合大规模部署
- **适用场景**:
  - 中文对话
  - 预算有限的项目
  - 高并发场景
- **模型ID**: `deepseek/deepseek-chat`

---

## ✍️ 写作创作模型

### 推荐模型

#### 1. Claude Opus 4.6 (Anthropic)
- **价格**: $5/$25 per 1M tokens
- **上下文**: 1M tokens
- **特点**:
  - Anthropic 最强模型
  - 最佳写作质量
  - 精致的文档创作能力
  - 专业级内容输出
- **适用场景**:
  - 长篇文章写作
  - 专业报告撰写
  - 创意内容创作
  - 文学作品
- **模型ID**: `anthropic/claude-opus-4.6`

#### 2. GPT-5.4 (OpenAI)
- **价格**: $2.50/$15 per 1M tokens
- **上下文**: 1M tokens
- **特点**:
  - 多样化写作风格
  - 结构化内容生成
  - 适合技术文档
- **适用场景**:
  - 技术文档
  - 博客文章
  - 营销文案
  - 产品说明
- **模型ID**: `openai/gpt-5.4`

#### 3. Gemini 3.1 Pro (Google)
- **价格**: $2/$12 per 1M tokens
- **上下文**: 1M tokens
- **特点**:
  - 强大的推理能力
  - 多语言支持
  - 适合研究性写作
- **适用场景**:
  - 学术论文
  - 研究报告
  - 多语言内容
- **模型ID**: `google/gemini-3.1-pro`

#### 4. Claude Sonnet 4.6 (Anthropic)
- **价格**: $3/$15 per 1M tokens
- **上下文**: 1M tokens
- **特点**:
  - 性价比高
  - 写作质量优秀
  - 自然流畅
- **适用场景**:
  - 日常写作
  - 社交媒体内容
  - 邮件撰写
- **模型ID**: `anthropic/claude-sonnet-4.6`

---

## 💻 代码编程模型

### 推荐模型

#### 1. GPT-5.4 (OpenAI)
- **价格**: $2.50/$15 per 1M tokens
- **上下文**: 1M tokens
- **特点**:
  - 57.7% SWE-Bench Pro 得分
  - 内置计算机使用能力
  - 构建-运行-验证-修复循环
  - 统一 Codex 产品线
- **适用场景**:
  - 复杂代码生成
  - 代码重构
  - Bug 修复
  - 端到端项目开发
- **模型ID**: `openai/gpt-5.4`

#### 2. Claude Opus 4.6 (Anthropic)
- **价格**: $5/$25 per 1M tokens
- **上下文**: 1M tokens
- **特点**:
  - 最强代码能力
  - 复杂代码库导航
  - 迭代开发能力
  - 项目管理能力
- **适用场景**:
  - 大型项目开发
  - 架构设计
  - 代码审查
- **模型ID**: `anthropic/claude-opus-4.6`

#### 3. Claude Sonnet 4.6 (Anthropic)
- **价格**: $3/$15 per 1M tokens
- **上下文**: 1M tokens
- **特点**:
  - 性价比高
  - 代码质量好
  - 适合日常开发
- **适用场景**:
  - 日常编程
  - 代码补全
  - 简单功能开发
- **模型ID**: `anthropic/claude-sonnet-4.6`

#### 4. DeepSeek V3.2 (DeepSeek)
- **价格**: $0.25/$0.38 per 1M tokens
- **上下文**: 64K tokens
- **特点**:
  - 极致性价比
  - 达到前沿模型 90% 性能
  - 成本仅为 GPT-5.4 的 1/50
- **适用场景**:
  - 成本敏感项目
  - 大规模代码生成
  - AI 编程助手
- **模型ID**: `deepseek/deepseek-v3.2`

#### 5. MiniMax M2.1 (MiniMax)
- **价格**: $0.28/$1.20 per 1M tokens
- **上下文**: 196K tokens
- **特点**:
  - 72.5% SWE-Bench 多语言得分
  - 10B 激活参数
  - 编程性价比之王
- **适用场景**:
  - 多语言编程
  - 预算有限的开发
- **模型ID**: `minimax/m2.1`

---

## 🎨 图片生成模型

### 推荐模型

#### 1. Flux 2 Pro (Black Forest Labs)
- **价格**: ~$0.02 per image
- **特点**:
  - 2026年最强图片生成模型
  - 前 Stability AI 团队打造
  - 超越 DALL-E 3 和 Midjourney v6
  - 提示词遵循度极高
  - 文字生成准确
- **适用场景**:
  - 专业设计
  - 高质量插图
  - 营销素材
  - 艺术创作
- **模型ID**: `black-forest-labs/flux-2-pro`

#### 2. Flux 2 Flex (Black Forest Labs)
- **价格**: ~$0.01 per image
- **特点**:
  - 性价比版本
  - 质量仍然优秀
  - 速度更快
- **适用场景**:
  - 大量图片生成
  - 快速原型设计
  - 社交媒体内容
- **模型ID**: `black-forest-labs/flux-2-flex`

#### 3. GPT Image 1 (OpenAI)
- **价格**: 按 GPT-4o 定价
- **特点**:
  - 基于 GPT-4o 的图片生成
  - 2025年3月替代 DALL-E 3
  - 与文本模型深度集成
- **适用场景**:
  - 文本+图片混合生成
  - ChatGPT 集成应用
- **模型ID**: `openai/gpt-image-1`

#### 4. Stable Diffusion XL (Stability AI)
- **价格**: 开源免费
- **特点**:
  - 开源模型
  - 可本地部署
  - 社区支持强
- **适用场景**:
  - 本地部署
  - 自定义训练
  - 无成本限制
- **模型ID**: `stability-ai/sdxl`

### 注意事项
- 目前系统仅支持 Flux 2 Pro 和 Flux 2 Flex
- 其他模型需要额外配置
- 图片生成不支持流式输出

---

## 🧠 推理思考模型

### 推荐模型

#### 1. GPT-5.4 Pro (OpenAI)
- **价格**: $30/$180 per 1M tokens
- **上下文**: 1M tokens
- **特点**:
  - OpenAI 最强推理模型
  - 强制推理模式
  - 适合关键任务
- **适用场景**:
  - 复杂问题解决
  - 数学推理
  - 逻辑分析
  - 科学研究
- **模型ID**: `openai/gpt-5.4-pro`

#### 2. DeepSeek Reasoner (DeepSeek)
- **价格**: $0.55/$2.19 per 1M tokens
- **上下文**: 64K tokens
- **特点**:
  - 极致性价比
  - 强大推理能力
  - 成本仅为 GPT-4 的 1/100
- **适用场景**:
  - 数学题解答
  - 逻辑推理
  - 预算有限的推理任务
- **模型ID**: `deepseek/deepseek-reasoner`

#### 3. ByteDance Seed 1.6 (ByteDance)
- **价格**: $0.25/$2 per 1M tokens
- **上下文**: 256K tokens
- **特点**:
  - 自适应深度思考
  - 多模态推理
  - 视频理解能力
- **适用场景**:
  - 多模态推理
  - 视频分析
- **模型ID**: `bytedance/seed-1.6`

---

## 🎭 多模态模型

### 推荐模型

#### 1. Gemini 3.1 Pro (Google)
- **价格**: $2/$12 per 1M tokens
- **上下文**: 1M tokens
- **特点**:
  - 多模态领导者
  - 支持文本、图片、音频
  - 视觉理解能力强
- **适用场景**:
  - 图片分析
  - 视频理解
  - 音频处理
- **模型ID**: `google/gemini-3.1-pro`

#### 2. GPT-4o (OpenAI)
- **价格**: $2.50/$10 per 1M tokens
- **上下文**: 128K tokens
- **特点**:
  - 原生多模态
  - 图片生成能力
  - 视觉推理
- **适用场景**:
  - 图文混合任务
  - OCR 识别
  - 图片描述
- **模型ID**: `openai/gpt-4o`

#### 3. Claude Opus 4.6 (Anthropic)
- **价格**: $5/$25 per 1M tokens
- **上下文**: 1M tokens
- **特点**:
  - 视觉理解
  - 计算机使用能力
  - 文档分析
- **适用场景**:
  - PDF 分析
  - 图表理解
  - 截图分析
- **模型ID**: `anthropic/claude-opus-4.6`

---

## 💰 成本优化建议

### 1. 按场景选择模型

| 场景 | 推荐模型 | 月成本估算 |
|------|---------|-----------|
| 日常对话（10万次） | Gemini 3.1 Flash Lite | ~$5 |
| 专业写作（1万次） | Claude Sonnet 4.6 | ~$30 |
| 代码开发（5千次） | DeepSeek V3.2 | ~$2 |
| 图片生成（1千张） | Flux 2 Flex | ~$10 |
| 复杂推理（1千次） | DeepSeek Reasoner | ~$3 |

### 2. 使用级联策略

```
简单任务 → 免费/低成本模型
  ↓ 不满意
中等任务 → 中端模型
  ↓ 不满意
复杂任务 → 旗舰模型
```

### 3. 启用上下文缓存

- Gemini: 缓存 token 折扣 90%
- Claude: 缓存 token 折扣 90%
- OpenAI: 缓存 token 折扣 50%

### 4. 批量处理

- 合并多个请求
- 减少 API 调用次数
- 降低固定成本

### 5. 模型路由

- 使用 OpenRouter 自动路由
- 简单查询 → 便宜模型
- 复杂查询 → 高级模型
- 潜在节省: 60-80%

---

## 📊 模型对比总结

### 聊天对话
1. 🥇 Claude Sonnet 4.6 - 最佳性价比
2. 🥈 GPT-5.4 - 最强能力
3. 🥉 Gemini 3.1 Flash Lite - 最快速度

### 写作创作
1. 🥇 Claude Opus 4.6 - 最佳质量
2. 🥈 GPT-5.4 - 多样风格
3. 🥉 Claude Sonnet 4.6 - 高性价比

### 代码编程
1. 🥇 GPT-5.4 - 最强能力
2. 🥈 Claude Opus 4.6 - 最佳架构
3. 🥉 DeepSeek V3.2 - 最佳性价比

### 图片生成
1. 🥇 Flux 2 Pro - 最高质量
2. 🥈 Flux 2 Flex - 高性价比
3. 🥉 GPT Image 1 - 集成便利

### 推理思考
1. 🥇 GPT-5.4 Pro - 最强推理
2. 🥈 DeepSeek Reasoner - 最佳性价比
3. 🥉 ByteDance Seed 1.6 - 多模态

---

## 🎯 快速选择指南

### 我需要...

**便宜的日常对话**
→ Gemini 3.1 Flash Lite ($0.25/$1.50)

**高质量写作**
→ Claude Opus 4.6 ($5/$25)

**代码开发**
→ GPT-5.4 ($2.50/$15) 或 DeepSeek V3.2 ($0.25/$0.38)

**图片生成**
→ Flux 2 Pro (专业) 或 Flux 2 Flex (性价比)

**复杂推理**
→ GPT-5.4 Pro ($30/$180) 或 DeepSeek Reasoner ($0.55/$2.19)

**多模态任务**
→ Gemini 3.1 Pro ($2/$12)

**极致性价比**
→ DeepSeek 系列

---

## 📚 相关文档

- [API 提供商对比](./API_PROVIDERS.md)
- [快速参考](./QUICK_REFERENCE.md)
- [用户指南](./USER_GUIDE.md)
- [项目结构](./PROJECT_STRUCTURE.md)

---

**最后更新**: 2026年3月9日  
**数据来源**: [OpenRouter](https://openrouter.ai), [TeamDay.ai](https://www.teamday.ai/blog/top-ai-models-openrouter-2026)
