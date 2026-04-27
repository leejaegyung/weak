# SE-HUB — 주간업무보고 시스템

Laravel 11 + Inertia.js v3 + Vue 3 기반의 사내 주간업무보고 웹 애플리케이션입니다.

---

## 기술 스택

| 구분 | 기술 |
|------|------|
| Backend | PHP 8.2+, Laravel 11 |
| Frontend | Vue 3, Inertia.js v3, Vite |
| Database | PostgreSQL 14~17 (기본 17) |
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

### 6단계 — PostgreSQL 17 설치

```bash
# PostgreSQL 공식 저장소 추가
sudo dnf install -y https://download.postgresql.org/pub/repos/yum/reporpms/EL-$(rpm -E %rhel)-x86_64/pgdg-redhat-repo-latest.noarch.rpm

# Rocky Linux 내장 PostgreSQL 모듈 비활성화 (버전 충돌 방지)
sudo dnf -qy module disable postgresql

# PostgreSQL 17 설치
sudo dnf install -y postgresql17-server postgresql17

# DB 초기화 및 서비스 등록
sudo /usr/pgsql-17/bin/postgresql-17-setup initdb
sudo systemctl enable postgresql-17
sudo systemctl start postgresql-17

# 버전 확인
psql --version
```

**DB 및 사용자 생성:**

```bash
sudo -u postgres psql <<'EOF'
CREATE USER weak WITH PASSWORD '비밀번호를_변경하세요';
CREATE DATABASE weak OWNER weak ENCODING 'UTF8' LC_COLLATE 'en_US.UTF-8' LC_CTYPE 'en_US.UTF-8' TEMPLATE template0;
GRANT ALL PRIVILEGES ON DATABASE weak TO weak;
EOF
```

**PostgreSQL 인증 방식 설정 (`pg_hba.conf`):**

```bash
sudo vi /var/lib/pgsql/17/data/pg_hba.conf
```

`127.0.0.1` 줄의 인증 방식을 `ident` → `md5` 로 변경합니다:

```
# 변경 전
host    all    all    127.0.0.1/32    ident
# 변경 후
host    all    all    127.0.0.1/32    md5
```

```bash
sudo systemctl restart postgresql-17
```

**PHP PostgreSQL 드라이버 설치:**

```bash
sudo dnf install -y php-pgsql php-pdo
sudo systemctl restart php-fpm
```

---

### 7단계 — 프로젝트 클론 및 설정

```bash
# data 디렉터리 생성 및 이동
sudo mkdir -p /data
cd /data

# 저장소 클론
sudo git clone git@github.com:leejaegyung/weak.git weak
cd /data/weak/app

# 소유권 설정
sudo chown -R nginx:nginx /data/weak
sudo chmod -R 755 /data/weak
```

---

### 8단계 — 애플리케이션 초기 설정

```bash
cd /data/weak/app

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

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=weak
DB_USERNAME=weak
DB_PASSWORD=비밀번호를_변경하세요

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
cd /data/weak/app

# npm 패키지 설치 및 빌드
sudo -u nginx npm ci
sudo -u nginx npm run build
```

---

### 10단계 — PHP-FPM 설정

```bash
sudo vi /etc/php-fpm.d/weak.conf
```

```ini
[weak]
user = nginx
group = nginx
listen = /run/php-fpm/weak.sock
listen.owner = nginx
listen.group = nginx
listen.mode = 0660

pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10
pm.max_requests = 500

php_admin_value[error_log] = /var/log/php-fpm/weak-error.log
php_admin_flag[log_errors] = on
```

```bash
sudo systemctl restart php-fpm
sudo systemctl enable php-fpm
```

---

### 11단계 — Nginx 설정

```bash
sudo vi /etc/nginx/conf.d/weak.conf
```

```nginx
server {
    listen 80;
    server_name 서버IP또는도메인;

    root /data/weak/app/public;
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
        fastcgi_pass unix:/run/php-fpm/weak.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    access_log /var/log/nginx/weak_access.log;
    error_log  /var/log/nginx/weak_error.log;
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
sudo semanage fcontext -a -t httpd_sys_rw_content_t "/data/weak/app/storage(/.*)?"
sudo semanage fcontext -a -t httpd_sys_rw_content_t "/data/weak/app/bootstrap/cache(/.*)?"
sudo restorecon -Rv /data/weak/app/storage
sudo restorecon -Rv /data/weak/app/bootstrap/cache
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
cd /data/weak/app

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
cd /data/weak/app

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
/data/
└── weak/                        # 프로젝트 루트
    └── app/                      # Laravel 애플리케이션
        ├── app/
        │   ├── Http/Controllers/
        │   ├── Models/
        │   └── Services/
        ├── database/
        │   └── migrations/
        ├── resources/
        │   └── js/Pages/         # Vue 3 + Inertia 페이지
        ├── public/
        │   └── build/            # Vite 빌드 결과물
        ├── storage/              # 로그, 캐시 (쓰기 권한 필요)
        └── routes/
            └── web.php
```

---

## 트러블슈팅

| 증상 | 원인 | 해결 |
|------|------|------|
| 500 Internal Server Error | `.env` 미설정 또는 권한 문제 | `storage/`, `bootstrap/cache/` 권한 확인 |
| 페이지 Not Found (404) | Nginx `try_files` 설정 누락 | Nginx conf의 `try_files $uri /index.php` 확인 |
| DB 연결 실패 | `.env` DB 설정 오류 또는 pg_hba.conf 인증 방식 | `php artisan config:clear` 후 `.env` 재확인, `pg_hba.conf` md5 설정 확인 |
| SELinux Permission Denied | SELinux 컨텍스트 미적용 | 12단계 SELinux 설정 재실행 |
| npm run build 실패 | Node.js 버전 낮음 | Node.js 22 이상 설치 확인 |
| 세션 만료가 너무 빠름 | `SESSION_LIFETIME` 기본값 | `.env`에서 `SESSION_LIFETIME=480` 설정 |

---

## 라이선스

사내 전용 — 외부 배포 금지
