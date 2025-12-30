# 使用輕量級 Alpine + PHP 8.3
FROM php:8.3-fpm-alpine

# 安裝 Nginx, Supervisor 和必要的 PHP 擴充
# 加入 GD 圖片處理函式庫所需的依賴套件 (libpng, libjpeg, libwebp, freetype)
RUN apk add --no-cache \
    nginx \
    supervisor \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) mysqli pdo pdo_mysql opcache gd

# 複製設定檔
COPY nginx.conf /etc/nginx/http.d/default.conf
COPY supervisord.conf /etc/supervisord.conf
COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# 複製程式碼（排除 uploads，因為它掛載在 EFS）
WORKDIR /var/www/html
COPY src/ .

# 修正權限（確保 www-data 使用者有權限讀寫，這對應你 EFS Access Point 的 ID 33）
RUN chown -R www-data:www-data /var/www/html

# 啟動 Supervisor（同時管理 Nginx 和 PHP-FPM）
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
