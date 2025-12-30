<?php
/**
 * AWS ECS + Docker 專用 wp-config.php
 * 此檔案應位於 src/ 資料夾內
 */

// =================================================================
// 1. 資料庫設定 (從環境變數讀取)
// =================================================================
define( 'DB_NAME',     getenv('WORDPRESS_DB_NAME') ?: 'wordpress' );
define( 'DB_USER',     getenv('WORDPRESS_DB_USER') ?: 'root' );
define( 'DB_PASSWORD', getenv('WORDPRESS_DB_PASSWORD') ?: 'rootpassword' );
define( 'DB_HOST',     getenv('WORDPRESS_DB_HOST') ?: 'db' );
define( 'DB_CHARSET',  'utf8mb4' );
define( 'DB_COLLATE',  '' );

// =================================================================
// 2. AWS ALB SSL 修正 (解決無限迴圈)
// =================================================================
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

// =================================================================
// 3. 動態網址設定 (讓 Image 可移植)
// =================================================================
if (isset($_SERVER['HTTP_HOST'])) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    define('WP_HOME', $protocol . $_SERVER['HTTP_HOST']);
    define('WP_SITEURL', $protocol . $_SERVER['HTTP_HOST']);
}

// =================================================================
// 4. CI/CD 與 Docker 策略
// =================================================================
// 編輯程式碼：禁止 (防止在後台不小心改壞 PHP 檔)
define( 'DISALLOW_FILE_EDIT', true );

// 安裝外掛/更新核心：【完全開放】
// 設為 false，代表你在 AWS 正式環境也可以自由安裝外掛、更新佈景
define( 'DISALLOW_FILE_MODS', false );

// 禁止自動更新核心 (核心版本建議由 Docker Image 控制)
define( 'WP_AUTO_UPDATE_CORE', false );

// =================================================================
// 5. 效能與記憶體
// =================================================================
define( 'WP_MEMORY_LIMIT', '256M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );

// =================================================================
// 6. Redis 設定 (預留)
// =================================================================
if (getenv('WORDPRESS_REDIS_HOST')) {
    define('WP_REDIS_HOST', getenv('WORDPRESS_REDIS_HOST'));
    define('WP_REDIS_PORT', 6379);
}

// =================================================================
// 7. 安全金鑰 (Salts) - 讀取 AWS 環境變數
// =================================================================
// 在本地開發如果沒設環境變數，給一個預設值 (dev-key) 以免報錯，也方便開發
define( 'AUTH_KEY',         getenv('WORDPRESS_AUTH_KEY')         ?: 'dev-key-123' );
define( 'SECURE_AUTH_KEY',  getenv('WORDPRESS_SECURE_AUTH_KEY')  ?: 'dev-key-123' );
define( 'LOGGED_IN_KEY',    getenv('WORDPRESS_LOGGED_IN_KEY')    ?: 'dev-key-123' );
define( 'NONCE_KEY',        getenv('WORDPRESS_NONCE_KEY')        ?: 'dev-key-123' );
define( 'AUTH_SALT',        getenv('WORDPRESS_AUTH_SALT')        ?: 'dev-key-123' );
define( 'SECURE_AUTH_SALT', getenv('WORDPRESS_SECURE_AUTH_SALT') ?: 'dev-key-123' );
define( 'LOGGED_IN_SALT',   getenv('WORDPRESS_LOGGED_IN_SALT')   ?: 'dev-key-123' );
define( 'NONCE_SALT',       getenv('WORDPRESS_NONCE_SALT')       ?: 'dev-key-123' );

$table_prefix = 'wp_';

// =================================================================
// 8. Debug 設定 (將 Log 導向 stderr 讓 CloudWatch 抓取)
// =================================================================
if ( getenv('WORDPRESS_DEBUG') === 'true' ) {
    define( 'WP_DEBUG', true );
    define( 'WP_DEBUG_LOG', '/dev/stderr' ); // 關鍵：寫入 stderr
    define( 'WP_DEBUG_DISPLAY', false );
} else {
    define( 'WP_DEBUG', false );
}

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';