# Requirements Document

## Introduction

本文档描述 OpenRouter AI Chat System 的第一版本（V1）需求。

V1 版本通过 OpenRouter API 提供 AI 聊天和图片生成功能。用户可以选择不同的大语言模型（如 Grok、Gemini、GPT）进行文本对话，系统会自动检测需要实时信息的问题并使用支持联网搜索的模型（如 Perplexity）获取最新答案。用户也可以通过文本或文本+图片的方式生成新图片。

V1 版本不包含用户注册和账户管理功能。未来版本将扩展用户系统，包括用户注册、充值、账户管理等功能。设计时需考虑这些未来扩展的可能性。

## Glossary

- **System**: OpenRouter AI Chat System，整个系统的统称
- **Chat_Module**: 聊天模块，负责处理用户与 AI 的对话交互
- **Image_Generator**: 图片生成器，负责根据用户输入生成图片
- **Model_Selector**: 模型选择器，允许用户选择使用的 AI 模型
- **OpenRouter_Client**: OpenRouter API 客户端，负责与 OpenRouter API 通信
- **Conversation**: 对话会话，包含用户和 AI 之间的消息历史
- **Model**: AI 大语言模型，如 Grok、Gemini、GPT 等

## Requirements

### Requirement 1: 模型选择

**User Story:** 作为用户，我想要选择不同的 AI 模型进行聊天，以便根据需求使用最合适的模型。

#### Acceptance Criteria

1. THE Model_Selector SHALL 提供 Grok、Gemini、GPT 三个模型选项供用户选择
2. WHEN 用户选择一个模型，THE Model_Selector SHALL 保存该选择并应用于后续聊天对话
3. THE System SHALL 显示当前选中的模型名称
4. WHEN 用户切换模型，THE System SHALL 保留现有对话历史
5. THE Model_Selector SHALL 仅应用于聊天功能，不应用于图片生成功能

### Requirement 2: 文本聊天

**User Story:** 作为用户，我想要与 AI 进行文本对话，以便获取信息或进行交流。

#### Acceptance Criteria

1. WHEN 用户输入文本消息，THE Chat_Module SHALL 将消息发送到选定的模型
2. WHEN OpenRouter API 返回响应，THE Chat_Module SHALL 在 2 秒内显示 AI 的回复
3. THE Chat_Module SHALL 保持对话历史记录，包含用户和 AI 的所有消息
4. WHEN API 调用失败，THE Chat_Module SHALL 显示错误信息并允许用户重试
5. THE Chat_Module SHALL 支持多轮对话，保持上下文连贯性

### Requirement 3: 图片生成（文本输入）

**User Story:** 作为用户，我想要通过文本描述生成图片，以便创建视觉内容。

#### Acceptance Criteria

1. WHEN 用户提供文本描述，THE Image_Generator SHALL 调用 OpenRouter API 生成图片
2. THE Image_Generator SHALL 由 OpenRouter 自动选择图片生成模型，无需用户选择
3. WHEN 图片生成完成，THE Image_Generator SHALL 在 10 秒内显示生成的图片
4. THE Image_Generator SHALL 提供下载按钮，允许用户下载生成的图片到本地
5. WHEN 用户点击下载按钮，THE System SHALL 将图片保存为 PNG 格式
6. IF 图片生成失败，THEN THE Image_Generator SHALL 显示具体错误原因

### Requirement 4: 图片生成（文本+图片输入）

**User Story:** 作为用户，我想要上传图片并添加文本描述来生成新图片，以便基于现有图片进行创作。

#### Acceptance Criteria

1. THE Image_Generator SHALL 支持用户上传 JPG、PNG、WebP 格式的图片
2. WHEN 用户上传图片并提供文本描述，THE Image_Generator SHALL 将两者结合发送到 API
3. THE Image_Generator SHALL 由 OpenRouter 自动选择图片生成模型，无需用户选择
4. THE Image_Generator SHALL 限制上传图片大小不超过 10MB
5. THE Image_Generator SHALL 提供下载按钮，允许用户下载生成的图片到本地
6. WHEN 用户点击下载按钮，THE System SHALL 将图片保存为 PNG 格式
7. IF 上传的图片格式不支持，THEN THE Image_Generator SHALL 提示用户使用支持的格式

### Requirement 5: OpenRouter API 集成

**User Story:** 作为系统，我需要与 OpenRouter API 通信，以便使用各种 AI 模型功能。

#### Acceptance Criteria

1. THE OpenRouter_Client SHALL 使用用户提供的 API 密钥进行身份验证
2. WHEN API 密钥无效，THE OpenRouter_Client SHALL 返回认证错误信息
3. THE OpenRouter_Client SHALL 支持聊天和图片生成两种 API 端点
4. WHEN API 返回速率限制错误，THE OpenRouter_Client SHALL 通知用户并建议等待时间
5. THE OpenRouter_Client SHALL 记录 API 调用日志，包含请求时间和响应状态

### Requirement 6: 对话管理

**User Story:** 作为用户，我想要管理我的对话历史，以便查看或清除之前的聊天记录。

#### Acceptance Criteria

1. THE System SHALL 保存每个对话会话的完整历史记录
2. THE System SHALL 允许用户创建新的对话会话
3. THE System SHALL 允许用户删除现有对话会话
4. WHEN 用户切换对话会话，THE System SHALL 加载对应的历史记录
5. THE System SHALL 为每个对话会话显示创建时间和消息数量

### Requirement 7: 用户配置

**User Story:** 作为用户，我想要配置 API 密钥和其他设置，以便系统能够正常工作。

#### Acceptance Criteria

1. THE System SHALL 提供配置界面供用户输入 OpenRouter API 密钥
2. THE System SHALL 安全存储 API 密钥，不以明文显示
3. WHEN 用户保存配置，THE System SHALL 验证 API 密钥的有效性
4. THE System SHALL 允许用户修改已保存的配置
5. IF API 密钥未配置，THEN THE System SHALL 提示用户先进行配置

### Requirement 8: 错误处理

**User Story:** 作为用户，我想要在出现错误时获得清晰的提示，以便了解问题并采取行动。

#### Acceptance Criteria

1. WHEN 网络连接失败，THE System SHALL 显示"网络连接失败，请检查网络设置"
2. WHEN API 配额耗尽，THE System SHALL 显示"API 配额已用完，请充值或稍后再试"
3. WHEN 输入内容违反内容政策，THE System SHALL 显示具体的违规原因
4. THE System SHALL 为所有错误提供用户友好的中文提示信息
5. WHEN 发生未预期错误，THE System SHALL 记录错误详情并显示通用错误消息

### Requirement 9: 智能联网搜索

**User Story:** 作为用户，我想要系统自动识别需要实时信息的问题并联网搜索，以便获得最新、准确的答案。

#### Acceptance Criteria

1. WHEN 用户提问包含时效性关键词（如"最新"、"现在"、"今天"），THE System SHALL 自动使用支持搜索的模型（如 Perplexity）
2. WHEN 使用搜索模型回复，THE System SHALL 提取并保存搜索来源链接
3. THE System SHALL 在 UI 中标注该回复使用了联网搜索
4. WHEN 显示搜索结果，THE System SHALL 在回复下方显示参考来源列表
5. THE System SHALL 将搜索来源保存到对话历史中
6. WHEN 搜索功能不可用，THE System SHALL 降级使用普通模型并提示用户
7. THE System SHALL 不需要用户配置额外的搜索 API 密钥

## 可扩展性考虑

本节描述 V1 版本设计时需要考虑的未来功能扩展，以确保系统架构能够平滑演进。

### 未来功能规划

V1 版本之后，系统将逐步引入以下功能：

1. **用户注册系统**: 允许用户创建账户、登录、管理个人信息
2. **用户充值功能**: 支持用户充值 API 使用额度或订阅服务
3. **用户账户管理**: 包括使用历史、消费记录、配额管理等

### V1 设计约束

为支持未来扩展，V1 版本的设计应遵循以下原则：

1. **数据结构可扩展**: 对话历史、配置信息等数据结构应设计为可关联用户 ID 的格式
2. **API 密钥管理**: 当前的 API 密钥配置机制应设计为可迁移到用户账户体系的方式
3. **会话隔离**: 对话会话管理应支持未来按用户隔离的需求
4. **配额追踪**: 虽然 V1 不限制使用量，但应预留追踪 API 调用次数和成本的能力
5. **认证预留**: 系统架构应预留未来添加身份认证层的空间

### V1 不包含的功能

以下功能明确不在 V1 范围内，将在后续版本实现：

- 用户注册和登录
- 多用户数据隔离
- 使用配额限制
- 充值和付费功能
- 用户使用统计和报表
