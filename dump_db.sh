#!/bin/bash

# MySQL Database Dump Script
# 使用方式: ./dump_db.sh

echo "🗄️  開始備份 MySQL 資料庫..."

# 執行 mysqldump
docker exec wordpress-db mysqldump -u root -prootpassword wordpress > db_dump_temp.sql

# 檢查是否成功
if [ $? -eq 0 ]; then
    echo "🔒 正在移除敏感資訊（API tokens）..."
    
    # 使用 sed 將 CWA API token 替換為 placeholder
    # 匹配格式: CWA-XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
    sed -E 's/CWA-[A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12}/CWA-XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/g' db_dump_temp.sql > db_dump.sql
    
    # 刪除臨時檔案
    rm db_dump_temp.sql
    
    echo "✅ 備份完成！檔案已儲存至: db_dump.sql"
    echo "📊 檔案大小: $(du -h db_dump.sql | cut -f1)"
    echo "🔐 所有 CWA API tokens 已被替換為 placeholder"
else
    echo "❌ 備份失敗！"
    exit 1
fi
