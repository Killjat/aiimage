# 开发指南

欢迎来到项目开发指南！这里包含了所有的文档和指南。

## 快速导航

### 🚀 快速开始
- [快速开始](QUICK_START.md) - 项目快速启动指南
- [快速参考](QUICK_REFERENCE.md) - 常用命令和配置速查表
- [快速参考卡](QUICK_REFERENCE_CARD.md) - 开发者速查卡

### 📦 部署指南
- [部署README](DEPLOYMENT_README.md) - 完整部署指南
- [快速部署指南](QUICK_DEPLOY_GUIDE.md) - 一键部署说明
- [部署策略](DEPLOYMENT_STRATEGY.md) - 部署架构和策略
- [本地部署](LOCAL_DEPLOYMENT.md) - 本地开发环境部署
- [Docker MySQL 设置](DOCKER_MYSQL_SETUP.md) - Docker 数据库配置

### 🔧 环境配置
- [环境变量配置](ENV_CONFIGURATION.md) - .env 文件配置指南
- [项目结构](PROJECT_STRUCTURE.md) - 项目目录结构说明

### 🎨 功能指南
- [用户指南](USER_GUIDE.md) - 应用使用说明
- [认证指南](AUTH_GUIDE.md) - 用户认证和授权
- [API 提供商](API_PROVIDERS.md) - 支持的 API 服务商

### 🖼️ 图片库功能
- [图片库指南](IMAGE_GALLERY_GUIDE.md) - 图片库功能说明
- [图片库前端指南](IMAGE_GALLERY_FRONTEND_GUIDE.md) - 前端实现细节
- [图片库完成报告](IMAGE_GALLERY_COMPLETE.md) - 功能完成情况
- [图片库生成策略](GALLERY_GENERATION_STRATEGY.md) - 图片生成策略

### 🤖 AI 模型集成
- [Alibaba 百练模型](ALIBABA_BAILIAN_MODELS.md) - 阿里百练模型列表
- [Alibaba 模型快速开始](ALIBABA_MODELS_QUICK_START.md) - 快速集成指南
- [Alibaba 集成完成](ALIBABA_INTEGRATION_COMPLETE.md) - 集成完成报告
- [OpenRouter 模型指南](OPENROUTER_MODELS_GUIDE.md) - OpenRouter 模型说明
- [Alibaba + OpenRouter 集成](ALIBABA_OPENROUTER_INTEGRATION.md) - 多模型集成

### 🔍 功能模块
- [Notte 指南](NOTTE_GUIDE.md) - Notte 自动化工具使用
- [智能分析指南](SMART_ANALYSIS_GUIDE.md) - 智能分析功能说明

### 🐛 问题修复和优化
- [数据库修复总结](DATABASE_FIX_SUMMARY.md) - 数据库问题修复
- [LocalStorage 修复](LOCALSTORAGE_FIX.md) - 存储问题解决
- [Flux 模型实现](FLUX_MODELS_IMPLEMENTATION.md) - Flux 模型集成
- [移动端优化](MOBILE_OPTIMIZATION.md) - 移动设备适配

### 📋 集成和总结
- [集成清单](INTEGRATION_CHECKLIST.md) - 功能集成检查清单
- [集成总结](INTEGRATION_SUMMARY.md) - 集成工作总结
- [实现总结](IMPLEMENTATION_SUMMARY.md) - 实现细节总结
- [最终报告](FINAL_REPORT.md) - 项目最终报告

### 📊 部署状态
- [部署状态](DEPLOYMENT_STATUS.md) - 当前部署状态
- [部署完成](DEPLOYMENT_COMPLETE.md) - 部署完成情况
- [部署成功](DEPLOYMENT_SUCCESS.md) - 部署成功报告
- [部署就绪](DEPLOYMENT_READY.md) - 部署就绪检查

## 按用途分类

### 对于新开发者
1. 阅读 [快速开始](QUICK_START.md)
2. 查看 [项目结构](PROJECT_STRUCTURE.md)
3. 参考 [快速参考](QUICK_REFERENCE.md)
4. 按需查看具体功能指南

### 对于部署人员
1. 阅读 [部署README](DEPLOYMENT_README.md)
2. 查看 [环境变量配置](ENV_CONFIGURATION.md)
3. 执行 [快速部署指南](QUICK_DEPLOY_GUIDE.md)
4. 检查 [部署状态](DEPLOYMENT_STATUS.md)

### 对于功能开发
1. 查看相关功能指南（如 [图片库指南](IMAGE_GALLERY_GUIDE.md)）
2. 参考 [API 提供商](API_PROVIDERS.md)
3. 查看 [项目结构](PROJECT_STRUCTURE.md) 了解代码组织

### 对于问题排查
1. 查看 [快速参考](QUICK_REFERENCE.md) 的常见问题
2. 查看相关的修复文档（如 [数据库修复总结](DATABASE_FIX_SUMMARY.md)）
3. 查看 [部署状态](DEPLOYMENT_STATUS.md)

## 文件统计

- 总文档数: 45+
- 部署相关: 8 个
- 功能指南: 12 个
- 模型集成: 6 个
- 问题修复: 5 个
- 其他: 14 个

## 最近更新

- ✅ 环境变量配置指南
- ✅ 快速部署脚本
- ✅ 部署README
- ✅ 图片库功能完成
- ✅ Alibaba + OpenRouter 集成

## 常用命令

```bash
# 本地开发
cp frontend/.env.local frontend/.env
cp backend/.env.local backend/.env
php -S 0.0.0.0:8080 -t backend/public
cd frontend && npm run dev

# 快速部署
bash scripts/quick_deploy.sh

# 测试部署
bash scripts/test_deployment.sh
```

## 获取帮助

- 查看相关的指南文档
- 检查 [快速参考](QUICK_REFERENCE.md) 中的常见问题
- 查看 [部署状态](DEPLOYMENT_STATUS.md) 了解当前状态

---

**最后更新**: 2026-03-11
