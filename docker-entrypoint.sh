#!/bin/sh
set -e

# 確保 wp-content 目錄存在並設定正確權限
mkdir -p /var/www/html/wp-content/uploads
mkdir -p /var/www/html/wp-content/upgrade
mkdir -p /var/www/html/wp-content/plugins
mkdir -p /var/www/html/wp-content/themes

# 設定 www-data 擁有者權限 (UID 82 in Alpine)
chown -R www-data:www-data /var/www/html/wp-content

# 等待 MySQL 準備就緒（最多等待 30 秒）
echo "⏳ 等待資料庫準備就緒..."
for i in $(seq 1 30); do
    if wp db check --allow-root 2>/dev/null; then
        echo "✅ 資料庫連線成功"
        break
    fi
    echo "⏳ 等待資料庫... ($i/30)"
    sleep 1
done

# 讀取 VERSION 文件並更新網站標題
if [ -f "/var/www/html/VERSION" ]; then
    VERSION=$(cat /var/www/html/VERSION | tr -d '\r\n')
    SITE_TITLE="USA V${VERSION}"
    
    echo "📝 更新網站標題為: $SITE_TITLE"
    
    # 使用 WP-CLI 更新網站標題
    if wp core is-installed --allow-root 2>/dev/null; then
        wp option update blogname "$SITE_TITLE" --allow-root
        echo "✅ 網站標題已更新: $SITE_TITLE"
    else
        echo "⚠️  WordPress 尚未安裝，跳過標題更新"
    fi
else
    echo "⚠️  VERSION 文件不存在，使用預設標題"
fi

# 啟動 Supervisor
exec /usr/bin/supervisord -c /etc/supervisord.conf
