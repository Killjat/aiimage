# 实现计划：OpenRouter AI Chat System

## 概述

本实现计划将 OpenRouter AI Chat System 的设计转化为可执行的开发任务。系统采用前后端分离架构：

- **前端**: React + TypeScript + Vite
- **后端**: PHP 8.1+ + Slim Framework + MySQL/PostgreSQL
- **缓存**: Redis（可选，用于会话和限流）

实现将按照从后端到前端的顺序进行：首先搭建后端基础设施（数据库、API、服务层），然后实现前端界面和业务逻辑，最后进行集成和部署。

**架构变更说明**:
- 数据存储从 IndexedDB 改为 MySQL/PostgreSQL 数据库
- API 密钥存储在后端数据库（加密），前端不直接接触
- 所有 OpenRouter API 请求通过后端代理
- 前端通过 REST API 与后端通信
- 支持未来的用户系统、充值功能等扩展

## 任务列表

- [ ] 1. 后端项目初始化
  - [ ] 1.1 创建 PHP 项目结构
    - 创建 backend/ 目录和子目录（src/, public/, config/, migrations/, tests/）
    - 创建 composer.json 配置文件
    - 创建 .env.example 环境变量模板
    - _需求: 所有功能的基础_
  
  - [ ] 1.2 安装后端依赖
    - 安装 Slim Framework 4.x
    - 安装 Guzzle HTTP 客户端
    - 安装 Firebase PHP-JWT
    - 安装 PHPDotenv
    - 安装 PHPUnit 和 Eris（测试库）
    - _需求: 所有功能的基础_
  
  - [ ] 1.3 配置后端环境
    - 创建 .env 文件（数据库连接、JWT 密钥、加密密钥等）
    - 配置 public/index.php 入口文件
    - 配置 PSR-4 自动加载
    - 配置 PHPUnit 测试环境
    - _需求: 所有功能的基础_

- [ ] 2. 数据库设计和迁移
  - [ ] 2.1 创建数据库迁移脚本
    - 001_create_users_table.sql（用户表）
    - 002_create_conversations_table.sql（对话表）
    - 003_create_messages_table.sql（消息表）
    - 004_create_user_configs_table.sql（用户配置表）
    - 005_create_api_logs_table.sql（API 日志表）
    - 006_create_transactions_table.sql（交易记录表，预留）
    - _需求: 6.1, 7.1_
  
  - [ ] 2.2 执行数据库迁移
    - 创建数据库
    - 按顺序执行迁移脚本
    - 验证表结构和索引
    - _需求: 6.1, 7.1_

- [ ] 3. 后端核心基础设施
  - [ ] 3.1 实现数据库连接类 (src/Utils/Database.php)
    - 创建 PDO 连接池
    - 实现连接管理和错误处理
    - _需求: 所有功能的基础_
  
  - [ ] 3.2 实现加密服务 (src/Services/EncryptionService.php)
    - 实现 AES-256-CBC 加密/解密
    - 用于 API 密钥加密存储
    - _需求: 7.2_
  
  - [ ] 3.3 实现错误处理器 (src/Utils/ErrorHandler.php)
    - 实现错误分类逻辑
    - 实现中文错误消息映射
    - 实现错误日志记录
    - _需求: 8.1, 8.2, 8.3, 8.4, 8.5_
  
  - [ ] 3.4 实现自定义异常类
    - OpenRouterException（OpenRouter API 错误）
    - ValidationException（验证错误）
    - AuthException（认证错误）
    - RateLimitException（速率限制错误）
    - _需求: 8.1, 8.2, 8.3_

- [ ] 4. 后端中间件实现
  - [ ] 4.1 实现 CORS 中间件 (src/Middleware/CorsMiddleware.php)
    - 配置允许的来源
    - 处理 OPTIONS 预检请求
    - _需求: 所有功能的基础_
  
  - [ ] 4.2 实现认证中间件 (src/Middleware/AuthMiddleware.php)
    - JWT token 验证
    - 用户身份提取
    - _需求: 7.1（预留）_
  
  - [ ] 4.3 实现速率限制中间件 (src/Middleware/RateLimitMiddleware.php)
    - 使用 Redis 或内存存储限流计数
    - 配置限流规则
    - _需求: 8.2_

- [ ] 5. 后端数据模型实现
  - [ ] 5.1 实现 User 模型 (src/Models/User.php)
    - findById, findByEmail 方法
    - save, verifyPassword 方法
    - _需求: 7.1（预留）_
  
  - [ ] 5.2 实现 Conversation 模型 (src/Models/Conversation.php)
    - findById, findByUserId 方法
    - save, delete（软删除）方法
    - loadMessages 方法
    - _需求: 6.1, 6.2, 6.3, 6.4_
  
  - [ ] 5.3 实现 Message 模型 (src/Models/Message.php)
    - findByConversationId 方法
    - save 方法
    - _需求: 2.3, 6.1_
  
  - [ ] 5.4 实现 UserConfig 模型 (src/Models/UserConfig.php)
    - findByUserId 方法
    - save 方法
    - _需求: 7.1, 7.2_
  
  - [ ] 5.5 实现 ApiLog 模型 (src/Models/ApiLog.php)
    - save 方法
    - 查询统计方法
    - _需求: 5.5_

- [ ] 6. 后端服务层实现
  - [ ] 6.1 实现 OpenRouterService (src/Services/OpenRouterService.php)
    - 实现 chat() 方法（代理聊天请求）
    - 实现 generateImage() 方法（代理图片生成请求）
    - 实现 validateApiKey() 方法
    - 使用 Guzzle 发送 HTTP 请求
    - 实现错误处理和重试逻辑
    - _需求: 2.1, 3.1, 4.2, 5.1, 5.2, 5.3, 5.4_
  
  - [ ]* 6.2 为 OpenRouterService 编写属性测试
    - **Property 13: API 请求包含密钥**
    - **Property 14: API 调用产生日志**
    - **验证需求: 5.1, 5.5**
  
  - [ ] 6.3 实现 ConversationService (src/Services/ConversationService.php)
    - 实现 create, get, list, delete 方法
    - 实现 addMessage 方法
    - 实现 generateTitle 私有方法
    - _需求: 6.1, 6.2, 6.3, 6.4, 6.5_
  
  - [ ]* 6.4 为 ConversationService 编写属性测试
    - **Property 15: 会话数据往返一致性**
    - **Property 16: 创建会话增加列表长度**
    - **Property 17: 删除会话后不可检索**
    - **Property 18: 会话切换加载正确历史**
    - **Property 19: 会话元数据包含必要字段**
    - **验证需求: 6.1, 6.2, 6.3, 6.4, 6.5**
  
  - [ ] 6.5 实现 ConfigService (src/Services/ConfigService.php)
    - 实现 getConfig, saveConfig 方法
    - 实现 saveApiKey, getApiKey 方法（加密/解密）
    - _需求: 7.1, 7.2, 7.3, 7.4_
  
  - [ ]* 6.6 为 ConfigService 编写属性测试
    - **Property 20: API 密钥不明文显示**
    - **Property 21: 保存配置触发验证**
    - **Property 22: 配置更新往返一致性**
    - **验证需求: 7.2, 7.3, 7.4**
  
  - [ ] 6.7 实现 UsageTrackingService (src/Services/UsageTrackingService.php)
    - 实现 logApiCall 方法
    - 实现 getUserUsage 方法
    - 实现 checkQuota 方法（预留）
    - _需求: 5.5_

- [ ] 7. 后端控制器实现
  - [ ] 7.1 实现 ChatController (src/Controllers/ChatController.php)
    - 实现 send 方法（POST /api/chat/send）
    - 实现 retry 方法（POST /api/chat/retry）
    - 调用 OpenRouterService 和 ConversationService
    - 实现搜索检测逻辑（检测时效性关键词）
    - 实现搜索来源提取
    - _需求: 2.1, 2.2, 2.3, 2.4, 2.5, 9.1, 9.2_
  
  - [ ]* 7.2 为 ChatController 编写属性测试
    - **Property 4: 消息路由到正确模型**
    - **Property 5: 对话历史完整性**
    - **Property 6: API 失败返回错误信息**
    - **Property 7: 多轮对话包含上下文**
    - **Property 25: 搜索模型返回来源链接**
    - **Property 26: 搜索检测触发正确模型**
    - **验证需求: 2.1, 2.3, 2.4, 2.5, 9.1, 9.2**
  
  - [ ] 7.3 实现 ImageController (src/Controllers/ImageController.php)
    - 实现 generate 方法（POST /api/image/generate）
    - 处理文件上传（multipart/form-data）
    - 验证图片格式和大小
    - 调用 OpenRouterService
    - _需求: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_
  
  - [ ]* 7.4 为 ImageController 编写属性测试
    - **Property 8: 文本描述触发图片生成**
    - **Property 9: 图片下载为 PNG 格式**
    - **Property 10: 图片生成失败显示错误**
    - **Property 11: 图片和文本组合发送**
    - **Property 12: 不支持格式返回错误**
    - **验证需求: 3.1, 3.5, 3.6, 4.2, 4.7**
  
  - [ ] 7.5 实现 ConversationController (src/Controllers/ConversationController.php)
    - 实现 list 方法（GET /api/conversations）
    - 实现 get 方法（GET /api/conversations/:id）
    - 实现 create 方法（POST /api/conversations）
    - 实现 delete 方法（DELETE /api/conversations/:id）
    - _需求: 6.2, 6.3, 6.4, 6.5_
  
  - [ ] 7.6 实现 ConfigController (src/Controllers/ConfigController.php)
    - 实现 get 方法（GET /api/config）
    - 实现 update 方法（PUT /api/config）
    - 实现 saveApiKey 方法（POST /api/config/api-key）
    - _需求: 7.1, 7.2, 7.3, 7.4, 7.5_
  
  - [ ] 7.7 实现 AuthController (src/Controllers/AuthController.php)（预留）
    - 实现 register, login, logout 方法
    - 实现 JWT token 生成和验证
    - _需求: 7.1（预留）_

- [ ] 8. 后端路由配置
  - [ ] 8.1 配置 API 路由 (src/routes.php)
    - 配置公开路由（健康检查、认证）
    - 配置受保护路由（聊天、图片、对话、配置）
    - 应用中间件（CORS、认证、速率限制）
    - _需求: 所有功能_

- [ ] 9. Checkpoint - 后端基础设施完成
  - 确保所有后端测试通过，如有问题请询问用户

- [ ] 10. 前端项目初始化
  - [ ] 10.1 创建 React 项目
    - 使用 Vite 创建 React + TypeScript 项目
    - 创建 frontend/ 目录结构
    - _需求: 所有功能的基础_
  
  - [ ] 10.2 安装前端依赖
    - 安装 axios（HTTP 客户端）
    - 安装 react-router-dom（路由）
    - 安装 tailwindcss（CSS 框架）
    - 安装 vitest 和 fast-check（测试库）
    - 安装 @testing-library/react（组件测试）
    - _需求: 所有功能的基础_
  
  - [ ] 10.3 配置前端环境
    - 配置 TypeScript (tsconfig.json)
    - 配置 Tailwind CSS
    - 配置 Vitest 测试环境
    - 创建 .env.example（后端 API 地址）
    - _需求: 所有功能的基础_

- [ ] 11. 前端核心类型和接口
  - [ ] 11.1 创建类型定义文件 (src/types/index.ts)
    - 定义 ModelType, Message, SearchSource, Conversation 等核心类型
    - 定义 API 请求和响应接口 (ApiResponse, ChatRequest, ImageGenerationRequest 等)
    - 定义错误类型 (ApiError, ErrorCode, ValidationResult)
    - _需求: 1.1, 2.1, 3.1, 4.1, 5.1, 6.1, 7.1, 9.2_

- [ ] 12. 前端 API 客户端层
  - [ ] 12.1 创建 ApiClient 类 (src/services/ApiClient.ts)
    - 配置 axios 实例（baseURL, timeout）
    - 实现请求拦截器（添加 token）
    - 实现响应拦截器（错误处理）
    - 实现 sendMessage, generateImage, getConversations 等方法
    - _需求: 所有功能_
  
  - [ ] 12.2 创建 ErrorHandler 类 (src/utils/ErrorHandler.ts)
    - 实现错误分类逻辑（网络、认证、配额等）
    - 实现中文错误消息映射
    - 实现错误日志记录
    - _需求: 8.1, 8.2, 8.3, 8.4, 8.5_
  
  - [ ]* 12.3 为 ApiClient 和 ErrorHandler 编写单元测试
    - 测试 API 调用参数
    - 测试错误处理和分类
    - 测试响应数据转换
    - _需求: 8.1, 8.2, 8.3, 8.4_

- [ ] 13. 前端业务逻辑服务
  - [ ] 13.1 创建 SearchDetector 类 (src/services/SearchDetector.ts)
    - 实现 shouldUseSearch() 方法（检测时效性关键词）
    - 定义时间关键词列表（最新、现在、今天等）
    - 定义搜索关键词列表（天气、新闻、股票等）
    - 实现疑问句模式检测
    - _需求: 9.1_
  
  - [ ] 13.2 创建 SourceExtractor 类 (src/services/SourceExtractor.ts)
    - 实现 extractSources() 方法（从响应中提取引用）
    - 实现 parseCitation() 方法（解析引用格式）
    - 支持多种引用格式（[1] Title - URL 等）
    - _需求: 9.2, 9.3, 9.4, 9.5_
  
  - [ ] 13.3 创建 ChatService 类 (src/services/ChatService.ts)
    - 实现 sendMessage() 方法（调用后端 API）
    - 实现 retryLastMessage() 方法
    - 集成 SearchDetector（自动检测是否需要联网搜索）
    - 集成 SourceExtractor（提取搜索来源）
    - _需求: 2.1, 2.2, 2.3, 2.4, 2.5, 9.1, 9.2_
  
  - [ ] 13.4 创建 ImageService 类 (src/services/ImageService.ts)
    - 实现 generateImage() 方法（支持纯文本和文本+图片）
    - 实现 downloadImage() 方法（下载为 PNG 格式）
    - 实现 validateImageFile() 方法（验证格式和大小）
    - _需求: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_
  
  - [ ] 13.5 创建 ConversationService 类 (src/services/ConversationService.ts)
    - 实现 createConversation() 方法
    - 实现 getConversation() 方法
    - 实现 listConversations() 方法
    - 实现 deleteConversation() 方法
    - _需求: 6.1, 6.2, 6.3, 6.4, 6.5_
  
  - [ ]* 13.6 为前端服务层编写属性测试
    - **Property 1: 模型选择持久化**
    - **Property 2: 模型切换保持历史不变**
    - **Property 3: 模型选择不影响图片生成**
    - **Property 23: 错误消息为中文**
    - **验证需求: 1.2, 1.3, 1.4, 1.5, 8.4**
  
  - [ ]* 13.7 为前端服务层编写单元测试
    - 测试搜索检测逻辑
    - 测试来源提取逻辑
    - 测试文件验证逻辑
    - 测试错误处理
    - _需求: 3.1, 3.6, 4.7, 9.1, 9.2_

- [ ] 14. Checkpoint - 前端服务层完成
  - 确保所有前端服务层测试通过，如有问题请询问用户

- [ ] 15. 前端状态管理
  - [ ] 15.1 创建 AppContext (src/contexts/AppContext.tsx)
    - 定义全局状态类型
    - 实现 useReducer 管理状态
    - 提供 Provider 组件
    - _需求: 所有功能_
  
  - [ ] 15.2 创建 ChatContext (src/contexts/ChatContext.tsx)
    - 管理当前对话状态
    - 管理消息列表
    - 管理加载和错误状态
    - _需求: 2.1, 2.2, 2.3, 2.4, 2.5_
  
  - [ ] 15.3 创建 AuthContext (src/contexts/AuthContext.tsx)（预留）
    - 管理认证状态
    - 管理用户信息
    - _需求: 7.1（预留）_

- [ ] 16. 前端 UI 组件 - 模型选择器
  - [ ] 16.1 创建 ModelSelector 组件 (src/components/ModelSelector.tsx)
    - 显示 Grok、Gemini、GPT 三个选项
    - 实现模型选择逻辑
    - 显示当前选中的模型
    - 使用 Tailwind CSS 样式
    - _需求: 1.1, 1.2, 1.3_
  
  - [ ]* 16.2 为 ModelSelector 编写组件测试
    - 测试三个模型选项的渲染
    - 测试模型切换交互
    - 测试当前选中状态显示
    - _需求: 1.1, 1.2, 1.3_

- [ ] 17. 前端 UI 组件 - 聊天界面
  - [ ] 17.1 创建 MessageComponent 组件 (src/components/chat/MessageComponent.tsx)
    - 显示消息内容
    - 区分用户和 AI 消息样式
    - 显示搜索来源（如果有）
    - 显示"已联网搜索"标记
    - _需求: 2.3, 9.3, 9.4_
  
  - [ ] 17.2 创建 ChatInput 组件 (src/components/chat/ChatInput.tsx)
    - 文本输入框
    - 发送按钮
    - 加载状态显示
    - 错误提示显示
    - _需求: 2.1, 2.2, 2.4_
  
  - [ ] 17.3 创建 ChatInterface 组件 (src/components/chat/ChatInterface.tsx)
    - 集成 MessageComponent 显示消息列表
    - 集成 ChatInput 处理用户输入
    - 集成 ModelSelector
    - 实现消息发送逻辑（调用 ChatService）
    - 实现自动滚动到最新消息
    - 实现错误处理和重试
    - _需求: 2.1, 2.2, 2.3, 2.4, 2.5, 9.1, 9.3, 9.4_
  
  - [ ]* 17.4 为聊天界面编写组件测试
    - 测试消息显示
    - 测试用户输入和发送
    - 测试错误状态显示
    - 测试搜索来源显示
    - _需求: 2.1, 2.2, 2.3, 2.4, 9.3, 9.4_

- [ ] 18. 前端 UI 组件 - 对话管理
  - [ ] 18.1 创建 ConversationListItem 组件 (src/components/conversation/ConversationListItem.tsx)
    - 显示对话标题
    - 显示创建时间和消息数量
    - 显示删除按钮
    - _需求: 6.5_
  
  - [ ] 18.2 创建 ConversationList 组件 (src/components/conversation/ConversationList.tsx)
    - 显示对话列表
    - 实现新建对话按钮
    - 实现对话切换
    - 实现对话删除
    - _需求: 6.2, 6.3, 6.4, 6.5_
  
  - [ ]* 18.3 为对话管理编写组件测试
    - 测试对话列表渲染
    - 测试新建对话
    - 测试对话切换
    - 测试对话删除
    - _需求: 6.2, 6.3, 6.4, 6.5_

- [ ] 19. 前端 UI 组件 - 图片生成
  - [ ] 19.1 创建 ImageUpload 组件 (src/components/image/ImageUpload.tsx)
    - 文件上传区域（拖拽或点击）
    - 文件格式和大小验证
    - 图片预览
    - _需求: 4.1, 4.4, 4.7_
  
  - [ ] 19.2 创建 ImagePromptInput 组件 (src/components/image/ImagePromptInput.tsx)
    - 文本描述输入框
    - 生成按钮
    - 加载状态显示
    - _需求: 3.1, 4.2_
  
  - [ ] 19.3 创建 GeneratedImageDisplay 组件 (src/components/image/GeneratedImageDisplay.tsx)
    - 显示生成的图片
    - 下载按钮
    - 错误提示显示
    - _需求: 3.3, 3.4, 3.5, 3.6, 4.5, 4.6_
  
  - [ ] 19.4 创建 ImageGenerator 组件 (src/components/image/ImageGenerator.tsx)
    - 集成 ImageUpload（可选）
    - 集成 ImagePromptInput
    - 集成 GeneratedImageDisplay
    - 实现图片生成逻辑（调用 ImageService）
    - 实现下载功能
    - 实现错误处理
    - _需求: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_
  
  - [ ]* 19.5 为图片生成界面编写组件测试
    - 测试文件上传和验证
    - 测试图片生成流程
    - 测试下载功能
    - 测试错误显示
    - _需求: 3.1, 3.3, 3.4, 3.5, 3.6, 4.1, 4.4, 4.7_

- [ ] 20. 前端 UI 组件 - 设置面板
  - [ ] 20.1 创建 SettingsPanel 组件 (src/components/settings/SettingsPanel.tsx)
    - API 密钥输入框（遮蔽显示）
    - 保存按钮
    - 验证状态显示
    - 默认模型选择
    - 清除配置按钮
    - _需求: 7.1, 7.2, 7.3, 7.4, 7.5_
  
  - [ ]* 20.2 为设置面板编写组件测试
    - 测试 API 密钥输入和遮蔽
    - 测试配置保存
    - 测试验证流程
    - _需求: 7.1, 7.2, 7.3, 7.4_

- [ ] 21. 前端主应用组件
  - [ ] 21.1 创建 App 组件 (src/App.tsx)
    - 集成所有 Context Providers
    - 实现路由或标签页切换（聊天 / 图片生成 / 设置）
    - 实现初始化逻辑（检查配置）
    - 实现配置未设置时的引导界面
    - _需求: 7.5, 所有功能_
  
  - [ ] 21.2 创建入口文件 (src/main.tsx)
    - 渲染 App 组件
    - 配置全局样式
    - _需求: 所有功能_

- [ ] 22. Checkpoint - 前端 UI 组件完成
  - 确保所有组件测试通过，如有问题请询问用户

- [ ] 23. 样式和 UI 优化
  - [ ] 23.1 实现响应式布局
    - 移动端适配
    - 平板和桌面端布局
    - _需求: 所有功能_
  
  - [ ] 23.2 实现主题样式
    - 使用 Tailwind CSS 定义颜色方案
    - 实现深色/浅色主题（可选）
    - 统一组件样式
    - _需求: 所有功能_
  
  - [ ] 23.3 实现加载和过渡动画
    - 消息加载动画
    - 图片生成加载动画
    - 页面切换过渡
    - _需求: 2.2, 3.3_

- [ ] 24. 错误提示优化
  - [ ] 24.1 创建 ErrorToast 组件 (src/components/common/ErrorToast.tsx)
    - 显示错误消息
    - 自动消失或手动关闭
    - 支持不同错误级别（错误、警告、信息）
    - _需求: 8.1, 8.2, 8.3, 8.4_
  
  - [ ] 24.2 集成错误提示到各个组件
    - 在 ChatInterface 中显示聊天错误
    - 在 ImageGenerator 中显示图片生成错误
    - 在 SettingsPanel 中显示配置错误
    - _需求: 2.4, 3.6, 7.3, 8.1, 8.2, 8.3_

- [ ] 25. 前后端集成测试
  - [ ] 25.1 测试聊天功能端到端流程
    - 测试消息发送和接收
    - 测试多轮对话
    - 测试搜索功能
    - _需求: 2.1, 2.2, 2.3, 2.5, 9.1, 9.2_
  
  - [ ] 25.2 测试图片生成端到端流程
    - 测试纯文本生成图片
    - 测试文本+图片生成图片
    - 测试图片下载
    - _需求: 3.1, 3.2, 3.3, 3.5, 4.1, 4.2, 4.6_
  
  - [ ] 25.3 测试对话管理端到端流程
    - 测试创建、切换、删除对话
    - 测试对话历史加载
    - _需求: 6.2, 6.3, 6.4, 6.5_
  
  - [ ] 25.4 测试配置管理端到端流程
    - 测试 API 密钥保存和验证
    - 测试配置更新
    - _需求: 7.1, 7.2, 7.3, 7.4_

- [ ] 26. 性能优化
  - [ ] 26.1 优化后端性能
    - 实现数据库查询优化（索引、查询缓存）
    - 实现 Redis 缓存（可选）
    - 优化 API 响应时间
    - _需求: 所有功能_
  
  - [ ] 26.2 优化前端性能
    - 实现消息虚拟滚动（如果消息量大）
    - 实现图片懒加载
    - 优化组件渲染性能
    - _需求: 2.3, 3.3_

- [ ] 27. 文档和部署准备
  - [ ] 27.1 编写后端 README.md
    - 项目介绍
    - 安装和运行指南
    - API 文档
    - 环境变量配置说明
    - _需求: 所有功能_
  
  - [ ] 27.2 编写前端 README.md
    - 项目介绍
    - 功能说明
    - 安装和运行指南
    - 配置说明（如何获取 OpenRouter API 密钥）
    - _需求: 所有功能_
  
  - [ ] 27.3 配置生产构建
    - 优化 Vite 构建配置（前端）
    - 配置 PHP 生产环境（后端）
    - 配置环境变量
    - 测试生产构建
    - _需求: 所有功能_
  
  - [ ] 27.4 准备部署配置
    - 创建 Docker Compose 配置（可选）
    - 配置 Nginx（后端反向代理）
    - 配置 HTTPS 和 CORS
    - 配置数据库备份策略
    - _需求: 所有功能_

- [ ] 28. Final Checkpoint - 完整系统验证
  - 运行所有测试（前端和后端）
  - 验证所有需求已实现
  - 确认系统可以正常部署和运行
  - 如有问题请询问用户

## 注意事项

- 标记 `*` 的任务为可选任务，可以跳过以加快 MVP 开发
- 每个任务都引用了具体的需求编号，确保可追溯性
- Checkpoint 任务确保增量验证，及早发现问题
- 属性测试验证通用正确性属性
- 单元测试验证具体示例和边界情况
- 实现顺序：后端基础设施 → 后端 API → 前端服务层 → 前端 UI → 集成测试
- 后端和前端可以并行开发，但需要先定义好 API 接口

## 架构说明

**后端职责**:
- 数据库管理（MySQL/PostgreSQL）
- API 密钥加密存储
- OpenRouter API 代理
- 用户认证和授权（预留）
- API 使用追踪和日志

**前端职责**:
- 用户界面和交互
- 状态管理
- API 调用和错误处理
- 客户端验证

**数据流**:
1. 用户在前端输入消息
2. 前端调用后端 API（POST /api/chat/send）
3. 后端验证请求，从数据库获取 API 密钥
4. 后端代理请求到 OpenRouter API
5. 后端保存消息到数据库，记录 API 日志
6. 后端返回响应给前端
7. 前端显示消息

## 测试配置

**前端测试**:
- 测试框架: Vitest
- 属性测试库: fast-check
- UI 测试: React Testing Library

**后端测试**:
- 测试框架: PHPUnit
- 属性测试库: Eris
- API 测试: PHPUnit + HTTP 客户端

所有属性测试必须：
- 运行至少 100 次迭代
- 使用注释标签引用设计文档中的属性
- 标签格式: `// Feature: openrouter-ai-chat-system, Property {N}: {property_text}`

前端示例：
```typescript
// Feature: openrouter-ai-chat-system, Property 15: 会话数据往返一致性
it('should preserve conversation data through save/load cycle', () => {
  fc.assert(
    fc.property(/* ... */),
    { numRuns: 100 }
  );
});
```

后端示例：
```php
<?php
// Feature: openrouter-ai-chat-system, Property 13: API 请求包含密钥
public function testApiRequestIncludesKey() {
    $this->forAll(
        Generator\string(),
        function ($apiKey) {
            // 测试逻辑
        }
    )->shouldHold();
}
```
