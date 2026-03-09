# 脚本目录

本目录包含所有用于部署、测试和管理系统的脚本。

## 📁 目录结构

```
scripts/
├── README.md                      # 本文件
├── deploy_remote.sh.example       # 远程部署脚本模板
├── setup_https.sh.example         # HTTPS配置模板（域名）
├── setup_https_ip.sh.example      # HTTPS配置模板（IP）
├── start_system.sh                # 启动本地系统
├── stop_system.sh                 # 停止本地系统
├── check_system_status.sh         # 检查系统状态
└── test_*.sh                      # 各种测试脚本
```

## 🚀 部署脚本

### 本地部署

```bash
# 启动本地开发环境
./scripts/start_system.sh

# 停止本地环境
./scripts/stop_system.sh

# 检查系统状态
./scripts/check_system_status.sh
```

### 远程部署

**首次使用前需要配置：**

```bash
# 1. 复制示例文件
cp scripts/deploy_remote.sh.example scripts/deploy_remote.sh
cp scripts/setup_https_ip.sh.example scripts/setup_https_ip.sh

# 2. 编辑文件，填写服务器信息和密码
vim scripts/deploy_remote.sh
vim scripts/setup_https_ip.sh

# 3. 执行部署
./scripts/deploy_remote.sh

# 4. 配置HTTPS
./scripts/setup_https_ip.sh
```

## 🧪 测试脚本

```bash
# 测试认证API
./scripts/test_auth_api.sh

# 测试游客模式
./scripts/test_guest_mode.sh

# 测试配额系统
./scripts/test_quota_system.sh
```

## 📋 脚本说明

### 部署相关

| 脚本 | 说明 | 用途 |
|------|------|------|
| `deploy_remote.sh` | 远程部署 | 将代码部署到远程服务器 |
| `setup_https.sh` | HTTPS配置（域名） | 使用Let's Encrypt配置HTTPS |
| `setup_https_ip.sh` | HTTPS配置（IP） | 使用自签名证书配置HTTPS |
| `deploy.sh` | 本地部署 | 本地环境部署 |

### 系统管理

| 脚本 | 说明 | 用途 |
|------|------|------|
| `start_system.sh` | 启动系统 | 启动后端、前端和MySQL |
| `stop_system.sh` | 停止系统 | 停止所有服务 |
| `check_system_status.sh` | 状态检查 | 检查所有服务状态 |
| `check_frontend_status.sh` | 前端状态 | 检查前端服务状态 |

### 测试脚本

| 脚本 | 说明 | 用途 |
|------|------|------|
| `test_auth_api.sh` | 认证测试 | 测试登录注册功能 |
| `test_guest_mode.sh` | 游客测试 | 测试游客模式 |
| `test_quota_system.sh` | 配额测试 | 测试配额系统 |

## ⚠️ 安全注意事项

1. **不要提交包含密码的脚本**
   - `deploy_remote.sh`
   - `setup_https.sh`
   - `setup_https_ip.sh`
   
   这些文件已在 `.gitignore` 中配置忽略。

2. **使用示例文件**
   - 使用 `.example` 文件作为模板
   - 复制后填写你的实际配置

3. **权限设置**
   ```bash
   # 确保脚本可执行
   chmod +x scripts/*.sh
   ```

## 📝 使用示例

### 完整的本地开发流程

```bash
# 1. 启动系统
./scripts/start_system.sh

# 2. 检查状态
./scripts/check_system_status.sh

# 3. 运行测试
./scripts/test_auth_api.sh

# 4. 停止系统
./scripts/stop_system.sh
```

### 完整的远程部署流程

```bash
# 1. 配置部署脚本
cp scripts/deploy_remote.sh.example scripts/deploy_remote.sh
vim scripts/deploy_remote.sh  # 填写服务器信息

# 2. 部署代码
./scripts/deploy_remote.sh

# 3. 配置HTTPS
cp scripts/setup_https_ip.sh.example scripts/setup_https_ip.sh
vim scripts/setup_https_ip.sh  # 填写服务器信息
./scripts/setup_https_ip.sh

# 4. 验证部署
curl -k https://your-server-ip/api/health
```

## 🔧 故障排查

如果脚本执行失败，检查：

1. **权限问题**
   ```bash
   chmod +x scripts/*.sh
   ```

2. **依赖检查**
   ```bash
   # 检查sshpass（远程部署需要）
   which sshpass
   
   # macOS安装
   brew install hudochenkov/sshpass/sshpass
   
   # Linux安装
   sudo apt-get install sshpass  # Ubuntu/Debian
   sudo yum install sshpass       # CentOS/RHEL
   ```

3. **路径问题**
   - 确保在项目根目录执行脚本
   - 或使用绝对路径

## 📚 更多信息

详细的部署指南请参考：[DEPLOYMENT_GUIDE.md](../DEPLOYMENT_GUIDE.md)
