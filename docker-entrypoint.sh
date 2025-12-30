#!/bin/sh
set -e

# 確保 wp-content 目錄存在並設定正確權限
mkdir -p /var/www/html/wp-content/uploads
mkdir -p /var/www/html/wp-content/upgrade
mkdir -p /var/www/html/wp-content/plugins
mkdir -p /var/www/html/wp-content/themes

# 設定 www-data 擁有者權限 (UID 82 in Alpine)
chown -R www-data:www-data /var/www/html/wp-content

# 啟動 Supervisor
exec /usr/bin/supervisord -c /etc/supervisord.conf
