# 部署策略 - 混合方案

## 📋 方案说明

采用混合部署策略，兼顾开发效率和版本管理：

- **日常开发**：使用本地直接同步，快速部署测试
- **稳定版本**：推送到 GitHub，保留版本记录

---

## 🚀 日常开发流程（快速部署）

### 1. 本地测试
```bash
# 启动本地服务测试
cd backend && php -S 0.0.0.0:8080 -t public
cd frontend && npm run dev
```

### 2. 直接同步到服务器
```bash
# 从本地直接推送到远程服务器
./scripts/sync_to_server.sh
```

这个命令会：
- 使用 rsync 直接同步代码
- 自动安装依赖
- 自动构建前端
- 自动重启服务
- 大约 1-2 分钟完成

**适用场景**：
- 快速修复 bug
- 测试新功能
- 频繁迭代开发

---

## 📦 稳定版本发布（GitHub）

### 1. 提交到 GitHub
```bash
git add .
git commit -m "描述你的修改"
git push origin main
```

### 2. 服务器拉取部署
```bash
# SSH 登录服务器
ssh root@165.154.235.9

# 执行部署脚本
cd /var/www/aiimage
./scripts/deploy_from_github.sh
```

**适用场景**：
- 重要功能上线
- 版本发布
- 需要版本回滚能力

---

## 🔄 两种方式对比

| 特性 | 本地同步 | GitHub 部署 |
|------|---------|------------|
| 速度 | ⚡ 快（1-2分钟） | 🐢 慢（需要先推送） |
| 版本控制 | ❌ 无 | ✅ 有 |
| 回滚能力 | ❌ 困难 | ✅ 简单 |
| 网络依赖 | 🔗 直连服务器 | 🌐 需要 GitHub |
| 适用场景 | 开发测试 | 正式发布 |

---

## 📝 推荐工作流

```
本地开发 → 本地测试 → 快速同步到服务器 → 测试
                                    ↓
                            功能稳定后
                                    ↓
                    提交到 GitHub → 服务器拉取部署
```

---

## 🛠️ 快速命令

### 本地同步部署
```bash
./scripts/sync_to_server.sh
```

### GitHub 部署
```bash
# 本地提交
git add .
git commit -m "你的修改"
git push origin main

# 服务器部署
ssh root@165.154.235.9 "cd /var/www/aiimage && ./scripts/deploy_from_github.sh"
```

### 一键提交并同步
```bash
# 创建快捷命令
git add . && git commit -m "快速更新" && ./scripts/sync_to_server.sh
```

---

## 🔐 安全建议

1. **敏感文件不同步**
   - `.env` 文件已自动排除
   - 配置文件保留在服务器本地

2. **备份策略**
   - 重要更新前先推送到 GitHub
   - 服务器定期备份数据库

3. **测试流程**
   - 本地测试通过后再同步
   - 服务器部署后检查日志

---

## 📊 部署后检查

```bash
# 检查服务状态
ssh root@165.154.235.9 "systemctl status aiimage-backend"

# 查看日志
ssh root@165.154.235.9 "journalctl -u aiimage-backend -n 50"

# 测试 API
curl https://165.154.235.9/api/health
```

---

**最后更新**: 2026年3月9日
