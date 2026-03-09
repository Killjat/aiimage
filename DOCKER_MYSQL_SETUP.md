# Docker MySQL 安装和管理指南

## ✅ 已完成的安装

MySQL 8.0 已通过 Docker 成功安装并运行。

## 容器信息

- **容器名称**: mysql-aiimage
- **MySQL 版本**: 8.0
- **端口**: 3306
- **Root 密码**: root
- **数据库名**: ai_chat_system

## 常用命令

### 启动/停止容器

```bash
# 启动容器
docker start mysql-aiimage

# 停止容器
docker stop mysql-aiimage

# 重启容器
docker restart mysql-aiimage

# 查看容器状态
docker ps | grep mysql-aiimage

# 查看容器日志
docker logs mysql-aiimage
```

### 数据库操作

```bash
# 连接到 MySQL
docker exec -it mysql-aiimage mysql -uroot -proot

# 连接到指定数据库
docker exec -it mysql-aiimage mysql -uroot -proot ai_chat_system

# 执行 SQL 命令
docker exec mysql-aiimage mysql -uroot -proot ai_chat_system -e "SHOW TABLES;"

# 导入 SQL 文件
cat backend/database/schema.sql | docker exec -i mysql-aiimage mysql -uroot -proot ai_chat_system

# 备份数据库
docker exec mysql-aiimage mysqldump -uroot -proot ai_chat_system > backup.sql

# 恢复数据库
cat backup.sql | docker exec -i mysql-aiimage mysql -uroot -proot ai_chat_system
```

### 容器管理

```bash
# 删除容器（保留数据卷）
docker stop mysql-aiimage
docker rm mysql-aiimage

# 完全删除（包括数据）
docker stop mysql-aiimage
docker rm -v mysql-aiimage

# 重新创建容器
docker run -d \
  --name mysql-aiimage \
  -e MYSQL_ROOT_PASSWORD=root \
  -e MYSQL_DATABASE=ai_chat_system \
  -p 3306:3306 \
  mysql:8.0
```

## 数据持久化（可选）

如果想要数据持久化到本地目录：

```bash
# 停止并删除现有容器
docker stop mysql-aiimage
docker rm mysql-aiimage

# 创建带数据卷的容器
docker run -d \
  --name mysql-aiimage \
  -e MYSQL_ROOT_PASSWORD=root \
  -e MYSQL_DATABASE=ai_chat_system \
  -p 3306:3306 \
  -v $(pwd)/mysql-data:/var/lib/mysql \
  mysql:8.0
```

## 配置信息

### backend/.env 配置

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ai_chat_system
DB_USER=root
DB_PASS=root
```

## 初始化数据库

```bash
cd backend
php init_database.php
```

或者手动执行：

```bash
cat backend/database/schema.sql | docker exec -i mysql-aiimage mysql -uroot -proot ai_chat_system
```

## 查看数据

```bash
# 查看所有表
docker exec mysql-aiimage mysql -uroot -proot ai_chat_system -e "SHOW TABLES;"

# 查看用户表
docker exec mysql-aiimage mysql -uroot -proot ai_chat_system -e "SELECT * FROM users;"

# 查看图片生成记录
docker exec mysql-aiimage mysql -uroot -proot ai_chat_system -e "SELECT * FROM image_generations;"
```

## 故障排查

### 容器无法启动

```bash
# 查看日志
docker logs mysql-aiimage

# 检查端口占用
lsof -i :3306

# 删除并重新创建
docker stop mysql-aiimage
docker rm mysql-aiimage
docker run -d --name mysql-aiimage -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=ai_chat_system -p 3306:3306 mysql:8.0
```

### 连接失败

```bash
# 检查容器是否运行
docker ps | grep mysql-aiimage

# 测试连接
docker exec mysql-aiimage mysql -uroot -proot -e "SELECT 1;"

# 检查 .env 配置
cat backend/.env | grep DB_
```

### 表不存在

```bash
# 重新初始化数据库
cd backend
php init_database.php

# 或手动导入
cat database/schema.sql | docker exec -i mysql-aiimage mysql -uroot -proot ai_chat_system
```

## 性能优化（可选）

### 修改 MySQL 配置

创建 `my.cnf` 文件：

```ini
[mysqld]
max_connections=200
innodb_buffer_pool_size=256M
```

使用配置文件启动：

```bash
docker run -d \
  --name mysql-aiimage \
  -e MYSQL_ROOT_PASSWORD=root \
  -e MYSQL_DATABASE=ai_chat_system \
  -p 3306:3306 \
  -v $(pwd)/my.cnf:/etc/mysql/conf.d/my.cnf \
  mysql:8.0
```

## 安全建议

### 生产环境

1. **修改 root 密码**
```bash
docker exec mysql-aiimage mysql -uroot -proot -e "ALTER USER 'root'@'%' IDENTIFIED BY 'strong_password';"
```

2. **创建专用用户**
```bash
docker exec mysql-aiimage mysql -uroot -proot -e "
CREATE USER 'aiimage'@'%' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON ai_chat_system.* TO 'aiimage'@'%';
FLUSH PRIVILEGES;
"
```

3. **更新 .env 配置**
```env
DB_USER=aiimage
DB_PASS=secure_password
```

## 开机自启动

```bash
# 设置容器自动重启
docker update --restart=always mysql-aiimage
```

## 卸载

```bash
# 停止容器
docker stop mysql-aiimage

# 删除容器
docker rm mysql-aiimage

# 删除镜像（可选）
docker rmi mysql:8.0

# 删除数据卷（可选）
docker volume prune
```

## 当前状态

✅ MySQL 容器运行中
✅ 数据库已创建
✅ 表已创建
✅ 后端已连接
✅ 可以正常使用

## 快速命令参考

```bash
# 启动所有服务
docker start mysql-aiimage
cd backend && php -S 0.0.0.0:8080 -t public &
cd frontend && npm run dev

# 停止所有服务
docker stop mysql-aiimage
# 停止 PHP 和 npm 进程

# 重置数据库
cat backend/database/schema.sql | docker exec -i mysql-aiimage mysql -uroot -proot ai_chat_system

# 查看用户
docker exec mysql-aiimage mysql -uroot -proot ai_chat_system -e "SELECT id, email, username, image_quota, image_used FROM users;"
```

## 更新日志

### 2026-03-08
- ✅ 使用 Docker 安装 MySQL 8.0
- ✅ 创建 ai_chat_system 数据库
- ✅ 初始化所有表
- ✅ 配置后端连接
- ✅ 测试注册功能成功
