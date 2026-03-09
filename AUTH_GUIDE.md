# 用户认证功能指南

## 功能概述

系统已实现完整的用户注册和登录功能，采用邮箱注册方式，使用 JWT Token 进行身份认证。

## 技术栈

### 后端
- **数据库**: MySQL 8.0+
- **认证方式**: JWT (JSON Web Token)
- **密码加密**: BCrypt
- **PHP 库**: firebase/php-jwt

### 前端
- **UI 框架**: React + TypeScript
- **HTTP 客户端**: Axios
- **状态管理**: React Hooks + localStorage

## 数据库设计

### 用户表 (users)
```sql
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    username VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL,
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active'
);
```

## 安装和配置

### 1. 安装依赖

后端已自动安装 JWT 库：
```bash
cd backend
composer require firebase/php-jwt
```

### 2. 配置环境变量

编辑 `backend/.env`：
```env
# Database Configuration
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ai_chat_system
DB_USER=root
DB_PASS=

# JWT Configuration
JWT_SECRET=your_random_secret_key_here
JWT_EXPIRATION=86400
```

**重要**: 生产环境必须修改 `JWT_SECRET` 为随机字符串！

### 3. 初始化数据库

运行初始化脚本：
```bash
cd backend
php init_database.php
```

这将自动：
- 创建数据库
- 创建所有必要的表
- 验证配置

## API 接口

### 1. 用户注册

**POST** `/api/auth/register`

请求体：
```json
{
  "email": "user@example.com",
  "password": "password123",
  "username": "昵称（可选）"
}
```

成功响应 (201):
```json
{
  "user": {
    "id": 1,
    "email": "user@example.com",
    "username": "昵称"
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

错误响应 (400):
```json
{
  "error": "该邮箱已被注册"
}
```

### 2. 用户登录

**POST** `/api/auth/login`

请求体：
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

成功响应 (200):
```json
{
  "user": {
    "id": 1,
    "email": "user@example.com",
    "username": "昵称"
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

错误响应 (401):
```json
{
  "error": "邮箱或密码错误"
}
```

### 3. 获取当前用户信息

**GET** `/api/auth/me`

请求头：
```
Authorization: Bearer <token>
```

成功响应 (200):
```json
{
  "user": {
    "id": 1,
    "email": "user@example.com",
    "username": "昵称",
    "created_at": "2026-03-08 10:00:00",
    "last_login_at": "2026-03-08 12:00:00"
  }
}
```

### 4. 登出

**POST** `/api/auth/logout`

成功响应 (200):
```json
{
  "message": "登出成功"
}
```

注意：登出主要在客户端完成（删除 token），服务端接口仅用于记录。

## 前端使用

### 1. 登录流程

```typescript
import { login } from './services/ApiClient';

const handleLogin = async () => {
  try {
    const response = await login({ email, password });
    // 保存 token
    localStorage.setItem('auth_token', response.token);
    localStorage.setItem('user', JSON.stringify(response.user));
    // 跳转到主页
  } catch (error) {
    console.error('登录失败', error);
  }
};
```

### 2. 注册流程

```typescript
import { register } from './services/ApiClient';

const handleRegister = async () => {
  try {
    const response = await register({ email, password, username });
    localStorage.setItem('auth_token', response.token);
    localStorage.setItem('user', JSON.stringify(response.user));
  } catch (error) {
    console.error('注册失败', error);
  }
};
```

### 3. 自动添加 Token

ApiClient 已配置请求拦截器，自动添加 token：

```typescript
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});
```

### 4. 登出流程

```typescript
const handleLogout = () => {
  localStorage.removeItem('auth_token');
  localStorage.removeItem('user');
  // 跳转到登录页
};
```

## 安全特性

### 1. 密码安全
- 使用 BCrypt 加密存储
- 最小长度 6 位
- 不在日志中记录密码

### 2. Token 安全
- JWT 签名验证
- 设置过期时间（默认 24 小时）
- HTTPS 传输（生产环境）

### 3. 输入验证
- 邮箱格式验证
- 密码长度验证
- SQL 注入防护（PDO 预处理）

### 4. 用户状态管理
- 支持账户禁用
- 记录最后登录时间
- 可扩展为多状态管理

## 测试

### 1. 测试注册

```bash
curl -X POST http://127.0.0.1:8080/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123",
    "username": "测试用户"
  }'
```

### 2. 测试登录

```bash
curl -X POST http://127.0.0.1:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

### 3. 测试获取用户信息

```bash
curl -X GET http://127.0.0.1:8080/api/auth/me \
  -H "Authorization: Bearer <your_token>"
```

## 常见问题

### Q1: 数据库连接失败
A: 检查 MySQL 服务是否启动，`.env` 配置是否正确。

### Q2: Token 无效或已过期
A: Token 默认 24 小时过期，需要重新登录。可以在 `.env` 中修改 `JWT_EXPIRATION`。

### Q3: 邮箱已被注册
A: 每个邮箱只能注册一次，请使用其他邮箱或登录现有账户。

### Q4: 密码长度不足
A: 密码最少 6 位，建议使用更复杂的密码。

## 后续扩展

### 可选功能
1. **邮箱验证**: 发送验证邮件
2. **密码重置**: 忘记密码功能
3. **第三方登录**: Google, GitHub 等
4. **双因素认证**: 2FA
5. **会话管理**: 多设备登录管理
6. **用户资料**: 头像、个人信息编辑

### 数据库扩展
系统已预留以下表：
- `chat_sessions`: 聊天会话历史
- `chat_messages`: 聊天消息记录
- `image_generations`: 图片生成记录

## 文件结构

```
backend/
├── database/
│   └── schema.sql              # 数据库结构
├── src/
│   ├── Controllers/
│   │   └── AuthController.php  # 认证控制器
│   ├── Services/
│   │   └── AuthService.php     # 认证服务
│   └── Database/
│       └── Database.php        # 数据库连接
├── init_database.php           # 数据库初始化脚本
└── .env                        # 环境配置

frontend/
├── src/
│   ├── components/
│   │   ├── Login.tsx           # 登录组件
│   │   └── Register.tsx        # 注册组件
│   ├── services/
│   │   └── ApiClient.ts        # API 客户端（含认证）
│   └── types.ts                # 类型定义
```

## 部署注意事项

### 生产环境配置

1. **修改 JWT Secret**
```env
JWT_SECRET=使用随机生成的长字符串
```

2. **启用 HTTPS**
- 所有认证请求必须通过 HTTPS
- 配置 SSL 证书

3. **数据库安全**
- 使用强密码
- 限制数据库访问权限
- 定期备份

4. **CORS 配置**
```env
CORS_ALLOWED_ORIGINS=https://your-domain.com
```

5. **错误日志**
- 生产环境不要暴露详细错误信息
- 记录到日志文件

## 更新日志

### 2026-03-08
- ✅ 实现用户注册功能
- ✅ 实现用户登录功能
- ✅ 实现 JWT Token 认证
- ✅ 创建数据库结构
- ✅ 创建前端登录/注册界面
- ✅ 添加自动 Token 注入
- ✅ 创建数据库初始化脚本
