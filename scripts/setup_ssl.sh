#!/bin/bash

# SSL证书设置脚本

echo "=== SSL证书设置 ==="
echo ""
echo "选择方式："
echo "1. 使用Let's Encrypt免费证书（推荐）"
echo "2. 使用自签名证书（仅测试）"
echo "3. 使用已有证书"
echo ""
read -p "选择 (1/2/3): " choice

DOMAIN="your-domain.com"
read -p "输入域名 [$DOMAIN]: " input_domain
if [ ! -z "$input_domain" ]; then
    DOMAIN=$input_domain
fi

case $choice in
    1)
        echo "安装certbot..."
        brew install certbot
        
        echo "获取证书..."
        sudo certbot certonly --standalone -d $DOMAIN
        
        CERT_PATH="/etc/letsencrypt/live/$DOMAIN/fullchain.pem"
        KEY_PATH="/etc/letsencrypt/live/$DOMAIN/privkey.pem"
        
        echo "证书路径："
        echo "  证书: $CERT_PATH"
        echo "  密钥: $KEY_PATH"
        
        # 更新nginx配置
        sed -i.bak "s|ssl_certificate .*|ssl_certificate $CERT_PATH;|" nginx.conf
        sed -i.bak "s|ssl_certificate_key .*|ssl_certificate_key $KEY_PATH;|" nginx.conf
        sed -i.bak "s|server_name .*|server_name $DOMAIN;|g" nginx.conf
        
        echo "自动续期（添加到crontab）："
        echo "0 0 * * * certbot renew --quiet && nginx -s reload"
        ;;
        
    2)
        echo "生成自签名证书..."
        mkdir -p ssl
        openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
            -keyout ssl/privkey.pem \
            -out ssl/fullchain.pem \
            -subj "/CN=$DOMAIN"
        
        CERT_PATH="$(pwd)/ssl/fullchain.pem"
        KEY_PATH="$(pwd)/ssl/privkey.pem"
        
        sed -i.bak "s|ssl_certificate .*|ssl_certificate $CERT_PATH;|" nginx.conf
        sed -i.bak "s|ssl_certificate_key .*|ssl_certificate_key $KEY_PATH;|" nginx.conf
        sed -i.bak "s|server_name .*|server_name $DOMAIN;|g" nginx.conf
        
        echo "⚠️  自签名证书仅用于测试，浏览器会显示不安全警告"
        ;;
        
    3)
        read -p "证书文件路径: " CERT_PATH
        read -p "密钥文件路径: " KEY_PATH
        
        sed -i.bak "s|ssl_certificate .*|ssl_certificate $CERT_PATH;|" nginx.conf
        sed -i.bak "s|ssl_certificate_key .*|ssl_certificate_key $KEY_PATH;|" nginx.conf
        sed -i.bak "s|server_name .*|server_name $DOMAIN;|g" nginx.conf
        ;;
esac

echo ""
echo "✅ SSL配置完成"
echo ""
echo "下一步："
echo "1. 构建前端: cd frontend && npm run build"
echo "2. 更新nginx.conf中的路径"
echo "3. 启动nginx: sudo nginx 或 sudo nginx -s reload"
