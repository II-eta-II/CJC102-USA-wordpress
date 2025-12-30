#!/bin/bash

# MySQL Database Dump Script
# 使用方式: ./dump_db.sh

echo "🗄️  開始備份 MySQL 資料庫..."

# 執行 mysqldump
docker exec wordpress-db mysqldump -u root -prootpassword wordpress > db_dump.sql

# 檢查是否成功
if [ $? -eq 0 ]; then
    echo "✅ 備份完成！檔案已儲存至: db_dump.sql"
    echo "📊 檔案大小: $(du -h db_dump.sql | cut -f1)"
else
    echo "❌ 備份失敗！"
    exit 1
fi
