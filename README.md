# CJC102 USA WordPress

WordPress 容器化應用，支援本地開發與 AWS Fargate 部署。

## 📋 功能特性

- ✅ WordPress 6.x + PHP 8.3 (Alpine Linux)
- ✅ Nginx + PHP-FPM + Supervisor
- ✅ MySQL 8.0 資料庫
- ✅ 環境變數配置管理
- ✅ CWA（中央氣象署）地震資料 API 整合
- ✅ CI/CD 自動部署到 AWS ECR
- ✅ 支援 AWS Fargate 部署

## 🚀 快速開始

### 本地開發

1. **克隆專案**
```bash
git clone <repository-url>
cd CJC102-USA-wordpress
```

2. **配置環境變數（可選）**
```bash
# 複製範本
cp .env.example .env

# 編輯 .env 填入實際值
vim .env
```

3. **啟動服務**
```bash
docker compose up -d
```

4. **訪問應用**
- WordPress: http://localhost
- Database: localhost:3306

5. **查看日誌**
```bash
docker compose logs -f app
```

## 🔧 環境變數說明

### 必需變數

| 變數名稱 | 說明 | 預設值 | 範例 |
|---------|------|--------|------|
| `WORDPRESS_DB_HOST` | MySQL 資料庫主機 | `db` | `db` or `rds-endpoint.amazonaws.com` |
| `WORDPRESS_DB_USER` | 資料庫使用者 | `root` | `wordpress_user` |
| `WORDPRESS_DB_PASSWORD` | 資料庫密碼 | `rootpassword` | `your-secure-password` |
| `WORDPRESS_DB_NAME` | 資料庫名稱 | `wordpress` | `wordpress_prod` |

### 選用變數

| 變數名稱 | 說明 | 預設值 | 備註 |
|---------|------|--------|------|
| `CWA_API_TOKEN` | 中央氣象署 API Token | _空_ | 用於地震資料功能 |
| `WORDPRESS_DEBUG` | WordPress 除錯模式 | `false` | 開發環境建議 `true` |
| `WORDPRESS_REDIS_HOST` | Redis 主機（快取） | _未設定_ | 效能優化用 |

### WordPress 安全金鑰（生產環境建議設定）

| 變數名稱 | 說明 |
|---------|------|
| `WORDPRESS_AUTH_KEY` | 認證金鑰 |
| `WORDPRESS_SECURE_AUTH_KEY` | 安全認證金鑰 |
| `WORDPRESS_LOGGED_IN_KEY` | 登入金鑰 |
| `WORDPRESS_NONCE_KEY` | Nonce 金鑰 |
| `WORDPRESS_AUTH_SALT` | 認證鹽值 |
| `WORDPRESS_SECURE_AUTH_SALT` | 安全認證鹽值 |
| `WORDPRESS_LOGGED_IN_SALT` | 登入鹽值 |
| `WORDPRESS_NONCE_SALT` | Nonce 鹽值 |

> 💡 **生成安全金鑰**: https://api.wordpress.org/secret-key/1.1/salt/

## ☁️ AWS Fargate 部署

### 前置準備

1. **創建 RDS MySQL 資料庫**
2. **創建 EFS 檔案系統**（用於 wp-content/uploads）
3. **設定 AWS Secrets Manager**（儲存敏感資訊）

### Secrets Manager 配置

建議將敏感變數存放在 AWS Secrets Manager：

```bash
# 創建資料庫密碼 secret
aws secretsmanager create-secret \
  --name wordpress-db-password \
  --secret-string "your-secure-password"

# 創建 CWA API Token secret
aws secretsmanager create-secret \
  --name cwa-api-token \
  --secret-string "CWA-XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX"
```

### ECS Task Definition 範例

```json
{
  "family": "wordpress-app",
  "networkMode": "awsvpc",
  "requiresCompatibilities": ["FARGATE"],
  "cpu": "512",
  "memory": "1024",
  "containerDefinitions": [
    {
      "name": "wordpress",
      "image": "<account-id>.dkr.ecr.<region>.amazonaws.com/cjc102-usa-wordpress:latest",
      "portMappings": [
        {
          "containerPort": 80,
          "protocol": "tcp"
        }
      ],
      "environment": [
        {
          "name": "WORDPRESS_DB_HOST",
          "value": "your-rds-endpoint.rds.amazonaws.com"
        },
        {
          "name": "WORDPRESS_DB_USER",
          "value": "wordpress_user"
        },
        {
          "name": "WORDPRESS_DB_NAME",
          "value": "wordpress"
        },
        {
          "name": "WORDPRESS_DEBUG",
          "value": "false"
        }
      ],
      "secrets": [
        {
          "name": "WORDPRESS_DB_PASSWORD",
          "valueFrom": "arn:aws:secretsmanager:region:account-id:secret:wordpress-db-password"
        },
        {
          "name": "CWA_API_TOKEN",
          "valueFrom": "arn:aws:secretsmanager:region:account-id:secret:cwa-api-token"
        }
      ],
      "logConfiguration": {
        "logDriver": "awslogs",
        "options": {
          "awslogs-group": "/ecs/wordpress-app",
          "awslogs-region": "us-east-1",
          "awslogs-stream-prefix": "ecs"
        }
      },
      "mountPoints": [
        {
          "sourceVolume": "efs-uploads",
          "containerPath": "/var/www/html/wp-content/uploads",
          "readOnly": false
        }
      ]
    }
  ],
  "volumes": [
    {
      "name": "efs-uploads",
      "efsVolumeConfiguration": {
        "fileSystemId": "fs-xxxxxxxxx",
        "transitEncryption": "ENABLED",
        "authorizationConfig": {
          "accessPointId": "fsap-xxxxxxxxx",
          "iam": "ENABLED"
        }
      }
    }
  ]
}
```

### 部署流程

1. **推送 Image 到 ECR**
```bash
VERSION=$(cat VERSION)
docker build -t cjc102-usa-wordpress:$VERSION .
docker tag cjc102-usa-wordpress:$VERSION <account-id>.dkr.ecr.<region>.amazonaws.com/cjc102-usa-wordpress:$VERSION
docker push <account-id>.dkr.ecr.<region>.amazonaws.com/cjc102-usa-wordpress:$VERSION
```

2. **註冊 Task Definition**
```bash
aws ecs register-task-definition --cli-input-json file://task-definition.json
```

3. **更新 ECS Service**
```bash
aws ecs update-service \
  --cluster your-cluster \
  --service wordpress-service \
  --task-definition wordpress-app:latest \
  --force-new-deployment
```

## 🛠️ 開發工具

### 資料庫備份

```bash
# 備份資料庫（會自動過濾敏感資訊）
./dump_db.sh
```

### 查看版本

```bash
cat VERSION
```

### 進入容器

```bash
# 進入 WordPress 容器
docker exec -it wordpress-app sh

# 進入資料庫容器
docker exec -it wordpress-db mysql -u root -p
```

## 📦 CI/CD

當推送到 GitHub 時，會自動觸發 CI/CD 流程：

1. ✅ 讀取 `VERSION` 檔案
2. ✅ 檢查版本是否已存在於 ECR
3. ✅ Build Docker Image
4. ✅ 推送到 ECR (標籤：latest, vX, vX.Y, vX.Y.Z)
5. ✅ 上傳資料庫備份到 S3

## 🔐 安全性

### Token 管理

- ❌ **不要**將真實 token 提交到 Git
- ✅ **使用** `.env` 檔案或環境變數
- ✅ **生產環境**使用 AWS Secrets Manager

### 資料庫備份

`./dump_db.sh` 會自動將所有 CWA API token 替換為 placeholder：
```
CWA-XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
```

## 📝 CWA 地震 API 使用

### 取得 API Token

1. 前往 https://opendata.cwa.gov.tw/
2. 註冊帳號
3. 申請 API Token
4. 設定環境變數 `CWA_API_TOKEN`

### 在 WordPress 中使用

地震資料短碼：
```
[my_eq_list]
```

## 🔍 故障排除

### 容器無法啟動
```bash
# 查看日誌
docker compose logs app

# 檢查環境變數
docker exec wordpress-app printenv
```

### WordPress 無法連接資料庫
- 檢查 `WORDPRESS_DB_HOST` 是否正確
- 確認資料庫服務已啟動
- 驗證資料庫帳號密碼

### 地震 API 不運作
```bash
# 檢查 token 是否設定
docker exec wordpress-app printenv CWA_API_TOKEN

# 測試 API
curl -H "Authorization: YOUR_TOKEN" \
  "https://opendata.cwa.gov.tw/api/v1/rest/datastore/E-A0015-001"
```

## 📚 參考資料

- [WordPress Docker 官方文件](https://hub.docker.com/_/wordpress)
- [AWS ECS Fargate 指南](https://docs.aws.amazon.com/ecs/latest/developerguide/AWS_Fargate.html)
- [CWA 開放資料平台](https://opendata.cwa.gov.tw/)

## 📄 授權

請參考專案授權文件。
