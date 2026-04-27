# SE-HUB — 주간업무보고 시스템

Laravel 11 + Inertia.js v3 + Vue 3 기반의 사내 주간업무보고 웹 애플리케이션입니다.

---

## 기술 스택

| 구분 | 기술 |
|------|------|
| Backend | PHP 8.2+, Laravel 11 |
| Frontend | Vue 3, Inertia.js v3, Vite |
| Database | SQLite (기본) / MariaDB 10.6+ |
| Web Server | Nginx + PHP-FPM |
| OS | Rocky Linux 8 / 9 |

---

## Rocky Linux 설치 가이드

> Rocky Linux 8 / 9 기준으로 작성되었습니다. 모든 명령은 `root` 또는 `sudo` 권한으로 실행합니다.

### 1단계 — 시스템 업데이트

```bash
sudo dnf update -y
sudo dnf install -y git curl unzip
```

---

### 2단계 — PHP 8.2 설치 (Remi 저장소)

Rocky Linux 기본 저장소에는 PHP 8.2가 없으므로 Remi 저장소를 추가합니다.

```bash
# EPEL 및 Remi 저장소 추가
sudo dnf install -y epel-release
sudo dnf install -y https://rpms.remirepo.net/enterprise/remi-release-$(rpm -E %rhel).rpm

# PHP 8.2 모듈 활성화
sudo dnf module reset php -y
sudo dnf module enable php:remi-8.2 -y

# PHP 및 필수 확장 설치
sudo dnf install -y \
  php php-fpm php-cli \
  php-mbstring php-xml php-zip \
  php-bcmath php-curl php-json \
  php-sqlite3 php-pdo \
  php-gd php-intl

# 버전 확인
php -v
```

---

### 3단계 — Composer 설치

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# 버전 확인
composer --version
```

---

### 4단계 — Node.js 22 설치 (프론트엔드 빌드용)

```bash
# NodeSource 저장소 추가
curl -fsSL https://rpm.nodesource.com/setup_22.x | sudo bash -

sudo dnf install -y nodejs

# 버전 확인
node -v
npm -v
```

---

### 5단계 — Nginx 설치

```bash
sudo dnf install -y nginx
sudo systemctl enable nginx
sudo systemctl start nginx
```

---

### 6단계 — MariaDB 설치 (선택 — SQLite 사용 시 건너뜁니다)

기본 설정은 SQLite를 사용합니다. MariaDB가 필요한 경우에만 진행합니다.

```bash
sudo dnf install -y mariadb-server
sudo systemctl enable mariadb
sudo systemctl start mariadb

# 보안 초기 설정 (root 비밀번호 설정 등)
sudo mysql_secure_installation

# DB 및 사용자 생성
sudo mysql -u root -p <<'EOF'
CREATE DATABASE sehub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sehub'@'localhost' IDENTIFIED BY '비밀번호를_변경하세요';
GRANT ALL PRIVILEGES ON sehub.* TO 'sehub'@'localhost';
FLUSH PRIVILEGES;
EOF
```

---

### 7단계 — 프로젝트 클론 및 설정

```bash
# 배포 디렉터리로 이동
sudo mkdir -p /var/www
cd /var/www

# 저장소 클론
sudo git clone git@github.com:leejaegyung/weak.git sehub
cd /var/www/sehub/app

# 소유권 설정
sudo chown -R nginx:nginx /var/www/sehub
sudo chmod -R 755 /var/www/sehub
```

---

### 8단계 — 애플리케이션 초기 설정

```bash
cd /var/www/sehub/app

# Composer 패키지 설치
sudo -u nginx composer install --no-dev --optimize-autoloader

# 환경 파일 생성
sudo -u nginx cp .env.example .env

# 아래 항목을 실제 환경에 맞게 수정합니다
sudo vi .env
```

`.env` 주요 설정값:

```ini
APP_NAME="SE-HUB"
APP_ENV=production
APP_KEY=                         # 아래 명령으로 자동 생성
APP_DEBUG=false
APP_URL=http://서버IP또는도메인
APP_TIMEZONE=Asia/Seoul

# SQLite 사용 시 (기본값)
DB_CONNECTION=sqlite
# DB_DATABASE=/var/www/sehub/app/database/database.sqlite

# MariaDB 사용 시 (SQLite 설정 주석 처리 후 아래 활성화)
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=sehub
# DB_USERNAME=sehub
# DB_PASSWORD=비밀번호를_변경하세요

SESSION_DRIVER=database
SESSION_LIFETIME=480
```

```bash
# 앱 키 생성
sudo -u nginx php artisan key:generate

# SQLite DB 파일 생성 (SQLite 사용 시)
sudo -u nginx touch database/database.sqlite

# 마이그레이션 실행
sudo -u nginx php artisan migrate --force

# 스토리지 링크 생성
sudo -u nginx php artisan storage:link

# 캐시 최적화
sudo -u nginx php artisan config:cache
sudo -u nginx php artisan route:cache
sudo -u nginx php artisan view:cache
```

---

### 9단계 — 프론트엔드 빌드

```bash
cd /var/www/sehub/app

# npm 패키지 설치 및 빌드
sudo -u nginx npm ci
sudo -u nginx npm run build
```

---

### 10단계 — PHP-FPM 설정

```bash
sudo vi /etc/php-fpm.d/sehub.conf
```

```ini
[sehub]
user = nginx
group = nginx
listen = /run/php-fpm/sehub.sock
listen.owner = nginx
listen.group = nginx
listen.mode = 0660

pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10
pm.max_requests = 500

php_admin_value[error_log] = /var/log/php-fpm/sehub-error.log
php_admin_flag[log_errors] = on
```

```bash
sudo systemctl restart php-fpm
sudo systemctl enable php-fpm
```

---

### 11단계 — Nginx 설정

```bash
sudo vi /etc/nginx/conf.d/sehub.conf
```

```nginx
server {
    listen 80;
    server_name 서버IP또는도메인;

    root /var/www/sehub/app/public;
    index index.php;

    charset utf-8;
    client_max_body_size 20M;

    # 정적 자산 캐시
    location /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php-fpm/sehub.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    access_log /var/log/nginx/sehub_access.log;
    error_log  /var/log/nginx/sehub_error.log;
}
```

```bash
# 설정 문법 검사
sudo nginx -t

# Nginx 재시작
sudo systemctl restart nginx
```

---

### 12단계 — SELinux 설정 (Rocky Linux 기본 활성화)

```bash
# Nginx가 네트워크 및 파일에 접근할 수 있도록 허용
sudo setsebool -P httpd_can_network_connect 1
sudo setsebool -P httpd_unified 1

# 애플리케이션 디렉터리 컨텍스트 적용
sudo semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/sehub/app/storage(/.*)?"
sudo semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/sehub/app/bootstrap/cache(/.*)?"
sudo restorecon -Rv /var/www/sehub/app/storage
sudo restorecon -Rv /var/www/sehub/app/bootstrap/cache
```

---

### 13단계 — 방화벽 설정

```bash
# HTTP (80) 포트 개방
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --reload

# HTTPS(443) 사용 시
# sudo firewall-cmd --permanent --add-service=https
# sudo firewall-cmd --reload
```

---

### 14단계 — 관리자 계정 생성

```bash
cd /var/www/sehub/app

sudo -u nginx php artisan tinker --execute="
\App\Models\User::create([
    'name'     => '관리자',
    'username' => 'admin',
    'email'    => 'admin@company.local',
    'password' => bcrypt('초기비밀번호'),
    'role'     => 'admin',
    'is_active' => true,
    'registration_status' => 'approved',
]);
"
```

---

## 업데이트 배포

```bash
cd /var/www/sehub/app

# 코드 업데이트
sudo -u nginx git pull origin main

# 패키지 업데이트
sudo -u nginx composer install --no-dev --optimize-autoloader
sudo -u nginx npm ci
sudo -u nginx npm run build

# 마이그레이션 실행
sudo -u nginx php artisan migrate --force

# 캐시 갱신
sudo -u nginx php artisan config:cache
sudo -u nginx php artisan route:cache
sudo -u nginx php artisan view:cache
```

---

## 디렉터리 구조

```
/var/www/sehub/
├── app/                  # Laravel 애플리케이션
│   ├── app/
│   │   ├── Http/Controllers/
│   │   ├── Models/
│   │   └── Services/
│   ├── database/
│   │   └── migrations/
│   ├── resources/
│   │   └── js/Pages/     # Vue 3 + Inertia 페이지
│   ├── public/
│   │   └── build/        # Vite 빌드 결과물
│   └── routes/
│       └── web.php
└── nginx/                # Nginx 설정 참고용
```

---

## 트러블슈팅

| 증상 | 원인 | 해결 |
|------|------|------|
| 500 Internal Server Error | `.env` 미설정 또는 권한 문제 | `storage/`, `bootstrap/cache/` 권한 확인 |
| 페이지 Not Found (404) | Nginx `try_files` 설정 누락 | Nginx conf의 `try_files $uri /index.php` 확인 |
| DB 연결 실패 | `.env` DB 설정 오류 | `php artisan config:clear` 후 `.env` 재확인 |
| SELinux Permission Denied | SELinux 컨텍스트 미적용 | 12단계 SELinux 설정 재실행 |
| npm run build 실패 | Node.js 버전 낮음 | Node.js 22 이상 설치 확인 |
| 세션 만료가 너무 빠름 | `SESSION_LIFETIME` 기본값 | `.env`에서 `SESSION_LIFETIME=480` 설정 |

---

## 라이선스

사내 전용 — 외부 배포 금지
