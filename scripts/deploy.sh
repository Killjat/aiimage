#!/bin/bash

set -e

echo "=== AI Chat System 部署脚本 ==="
echo ""

# 颜色
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# 配置
DOMAIN=""
PROJECT_DIR=$(pwd)
FRONTEND_DIR="$PROJECT_DIR/frontend"
BACKEND_DIR="$PROJECT_DIR/backend"

# 检查依赖
check_dependencies() {
    echo "检查依赖..."
    
    if ! command -v php &> /dev/null; then
        echo -e "${RED}错误: PHP未安装${NC}"
        exit 1
    fi
    
    if ! command -v composer &> /dev/null; then
        echo -e "${RED}错误: Composer未安装${NC}"
        exit 1
    fi
    
    if ! command -v node &> /dev/null; then
        echo -e "${RED}错误: Node.js未安装${NC}"
        exit 1
    fi
    
    if ! command -v nginx &> /dev/null; then
        echo -e "${YELLOW}警告: Nginx未安装，正在安装...${NC}"
        brew install nginx
    fi
    
    echo -e "${GREEN}✓ 依赖检查完成${NC}"
}

# 输入域名
input_domain() {
    read -p "输入域名（例如：example.com）: " DOMAIN
    if [ -z "$DOMAIN" ]; then
        echo -e "${RED}错误: 域名不能为空${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓ 域名: $DOMAIN${NC}"
}

# 构建前端
build_frontend() {
    echo ""
    echo "构建前端..."
    cd $FRONTEND_DIR
    
    # 更新.env
    if [ -f .env ]; then
        sed -i.bak "s|VITE_API_BASE_URL=.*|VITE_API_BASE_URL=https://$DOMAIN/api|" .env
    fi
    
    npm install
    npm run build
    
    echo -e "${GREEN}✓ 前端构建完成${NC}"
}

# 配置后端
setup_backend() {
    echo ""
    echo "配置后端..."
    cd $BACKEND_DIR
    
    if [ ! -f .env ]; then
        cp .env.example .env
        echo -e "${YELLOW}请编辑 backend/.env 配置数据库和API密钥${NC}"
        read -p "按回车继续..."
    fi
    
    composer install --no-dev --optimize-autoloader
    
    echo -e "${GREEN}✓ 后端配置完成${NC}"
}

# 配置SSL
setup_ssl() {
    echo ""
    echo "配置SSL证书..."
    echo "1. Let's Encrypt（推荐）"
    echo "2. 自签名证书（测试）"
    echo "3. 跳过"
    read -p "选择 (1/2/3): " ssl_choice
    
    case $ssl_choice in
        1)
            if ! command -v certbot &> /dev/null; then
                brew install certbot
            fi
            
            sudo certbot certonly --standalone -d $DOMAIN
            
            CERT_PATH="/etc/letsencrypt/live/$DOMAIN/fullchain.pem"
            KEY_PATH="/etc/letsencrypt/live/$DOMAIN/privkey.pem"
            
            # 添加自动续期
            (crontab -l 2>/dev/null; echo "0 0 * * * certbot renew --quiet && nginx -s reload") | crontab -
            ;;
        2)
            mkdir -p ssl
            openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
                -keyout ssl/privkey.pem \
                -out ssl/fullchain.pem \
                -subj "/CN=$DOMAIN"
            
            CERT_PATH="$PROJECT_DIR/ssl/fullchain.pem"
            KEY_PATH="$PROJECT_DIR/ssl/privkey.pem"
            ;;
        3)
            echo "跳过SSL配置"
            return
            ;;
    esac
    
    echo -e "${GREEN}✓ SSL配置完成${NC}"
}

# 配置Nginx
setup_nginx() {
    echo ""
    echo "配置Nginx..."
    
    # 生成nginx配置
    cat > /tmp/aiimage.conf << EOF
# HTTP重定向到HTTPS
server {
    listen 80;
    server_name $DOMAIN;
    return 301 https://\$server_name\$request_uri;
}

# HTTPS配置
server {
    listen 443 ssl http2;
    server_name $DOMAIN;

    ssl_certificate $CERT_PATH;
    ssl_certificate_key $KEY_PATH;
    
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;

    # 前端
    location / {
        root $FRONTEND_DIR/dist;
        try_files \$uri \$uri/ /index.html;
        
        location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
            expires 1y;
            add_header Cache-Control "public, immutable";
        }
    }

    # 后端API
    location /api {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }

    # 安全头
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
}
EOF

    # 复制配置
    NGINX_CONF_DIR="/usr/local/etc/nginx/servers"
    if [ ! -d "$NGINX_CONF_DIR" ]; then
        sudo mkdir -p $NGINX_CONF_DIR
    fi
    
    sudo cp /tmp/aiimage.conf $NGINX_CONF_DIR/aiimage.conf
    
    # 测试配置
    sudo nginx -t
    
    echo -e "${GREEN}✓ Nginx配置完成${NC}"
}

# 启动服务
start_services() {
    echo ""
    echo "启动服务..."
    
    # 启动MySQL
    if ! docker ps | grep -q mysql-aiimage; then
        echo "启动MySQL..."
        docker start mysql-aiimage || docker run -d \
            --name mysql-aiimage \
            -p 3306:3306 \
            -e MYSQL_ROOT_PASSWORD=root \
            -e MYSQL_DATABASE=ai_chat_system \
            mysql:8.0
        sleep 5
    fi
    
    # 初始化数据库
    cd $BACKEND_DIR
    php init_database.php
    
    # 启动后端
    echo "启动后端..."
    pkill -f "php -S 0.0.0.0:8080" || true
    nohup php -S 0.0.0.0:8080 -t public > /tmp/backend.log 2>&1 &
    
    # 启动/重载Nginx
    if pgrep nginx > /dev/null; then
        sudo nginx -s reload
    else
        sudo nginx
    fi
    
    echo -e "${GREEN}✓ 服务启动完成${NC}"
}

# 创建systemd服务（Linux）
create_systemd_service() {
    if [[ "$OSTYPE" == "linux-gnu"* ]]; then
        echo ""
        echo "创建systemd服务..."
        
        sudo tee /etc/systemd/system/aiimage-backend.service > /dev/null << EOF
[Unit]
Description=AI Image Backend
After=network.target mysql.service

[Service]
Type=simple
User=$USER
WorkingDirectory=$BACKEND_DIR
ExecStart=/usr/bin/php -S 0.0.0.0:8080 -t public
Restart=always

[Install]
WantedBy=multi-user.target
EOF

        sudo systemctl daemon-reload
        sudo systemctl enable aiimage-backend
        sudo systemctl start aiimage-backend
        
        echo -e "${GREEN}✓ Systemd服务创建完成${NC}"
    fi
}

# 显示信息
show_info() {
    echo ""
    echo "=========================================="
    echo -e "${GREEN}部署完成！${NC}"
    echo "=========================================="
    echo ""
    echo "访问地址: https://$DOMAIN"
    echo ""
    echo "服务状态："
    echo "  - 前端: $FRONTEND_DIR/dist"
    echo "  - 后端: http://127.0.0.1:8080"
    echo "  - Nginx: 运行中"
    echo "  - MySQL: docker ps | grep mysql-aiimage"
    echo ""
    echo "日志："
    echo "  - 后端: /tmp/backend.log"
    echo "  - Nginx: /usr/local/var/log/nginx/"
    echo ""
    echo "管理命令："
    echo "  - 重启后端: pkill -f 'php -S' && cd backend && nohup php -S 0.0.0.0:8080 -t public &"
    echo "  - 重载Nginx: sudo nginx -s reload"
    echo "  - 查看日志: tail -f /tmp/backend.log"
    echo ""
}

# 主流程
main() {
    check_dependencies
    input_domain
    build_frontend
    setup_backend
    setup_ssl
    setup_nginx
    start_services
    create_systemd_service
    show_info
}

main
