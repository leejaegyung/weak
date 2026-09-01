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
CREATE USER weeklyrpt_user WITH PASSWORD 'weeklyrpt123';
CREATE DATABASE weeklyrpt OWNER weeklyrpt_user ENCODING 'UTF8' LC_COLLATE 'en_US.UTF-8' LC_CTYPE 'en_US.UTF-8' TEMPLATE template0;
GRANT ALL PRIVILEGES ON DATABASE weeklyrpt TO weeklyrpt_user;
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
```bash
sudo -u nginx composer install --no-dev --optimize-autoloader
```
```bash
sudo -u nginx /usr/local/bin/composer install --no-dev --optimize-autoloader
```

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

### 15단계 — DB 자동 백업 설정

매일 새벽 3시에 PostgreSQL 덤프를 뜨고 30일치를 보관합니다.

| 항목 | 값 |
|------|-----|
| 백업 스크립트 | `/data/weak/db_backup.sh` |
| 저장 위치 | `/data/weak/DBbackup/` |
| 로그 파일 | `/data/weak/DBbackup/backup.log` |
| 실행 계정 | `root` |
| 실행 시각 | 매일 03:00 (KST) |
| 보관 기간 | 30일 — 경과분 자동 삭제 |
| 덤프 형식 | `pg_dump -Fc` (custom 포맷, gzip 압축) |

스크립트가 `app/.env` 에서 DB 접속 정보를 읽으므로 **비밀번호를 별도로 적어둘 필요가 없습니다.**
`pg_dump` 경로도 `/usr/pgsql-*/bin` 까지 자동 탐색하며, `flock` 으로 중복 실행을 막고 덤프 파일 권한은 `600` 으로 설정됩니다.

#### 15-1. 실행 권한 부여 및 수동 확인

```bash
cd /data/weak
chmod +x db_backup.sh

# 수동 실행
./db_backup.sh && cat DBbackup/backup.log
```

정상이면 아래처럼 출력됩니다.

```
[2026-09-01 14:10:23] 백업 시작 -> /data/weak/DBbackup/weeklyrpt_20260901_141023.dump
[2026-09-01 14:10:23] [성공] 백업 완료 (164K)
```

`pg_dump: command not found` 가 나오면 클라이언트를 설치합니다.

```bash
dnf install -y postgresql17
```

#### 15-2. 덤프 파일 검증

백업 파일이 생겼다는 것과 복원이 된다는 것은 다릅니다. 한 번은 열어서 확인합니다.

```bash
pg_restore -l /data/weak/DBbackup/weeklyrpt_20260901_141023.dump | head -30
```

`Format: CUSTOM`, `Compression: gzip` 과 함께 테이블 목록이 나오면 정상입니다.
헤더의 `Dumped from database version` 과 `Dumped by pg_dump version` 이 **같은지** 확인하세요.
`pg_dump` 가 서버보다 낮은 버전이면 덤프가 불완전할 수 있습니다.

```bash
# 핵심 테이블이 담겼는지 확인
pg_restore -l /data/weak/DBbackup/*.dump | grep -E 'TABLE public (users|reports|schedules)'
```

#### 15-3. cron 등록 (root)

```bash
cat > /etc/cron.d/weeklyrpt-backup << 'CRON'
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

# 매일 03:00 DB 백업
0 3 * * * root /data/weak/db_backup.sh
CRON

chmod 644 /etc/cron.d/weeklyrpt-backup
chown root:root /etc/cron.d/weeklyrpt-backup
systemctl enable --now crond
systemctl restart crond
```

> `/etc/cron.d/` 방식은 `crontab -e` 와 달리 **시간 다섯 칸 뒤에 실행 계정(`root`)이 들어갑니다.**
> 이 칸이 빠지거나 파일 권한이 `644` 가 아니면 cron 이 아무 말 없이 무시합니다.

등록 확인:

```bash
cat /etc/cron.d/weeklyrpt-backup
systemctl status crond
date                              # 서버 시각이 KST 인지 확인
```

#### 15-4. 새벽 3시까지 기다리지 않고 검증

cron 이 실제로 스크립트를 실행하는지(권한 · PATH · SELinux) 1분 주기로 바꿔 확인합니다.

```bash
# 임시로 매분 실행
sed -i 's|^ *0 3 \* \* \*|*/1 * * * *|' /etc/cron.d/weeklyrpt-backup
systemctl restart crond
```

1~2분 뒤:

```bash
tail -5 /data/weak/DBbackup/backup.log
journalctl -u crond --since '-5 min' --no-pager
```

새 시각의 `[성공] 백업 완료` 가 찍혔으면 연동 성공입니다. **확인 즉시 원복합니다.**

```bash
sed -i 's|^\*/1 \* \* \* \*|0 3 * * *|' /etc/cron.d/weeklyrpt-backup
systemctl restart crond
cat /etc/cron.d/weeklyrpt-backup   # 0 3 * * * root ... 로 돌아왔는지 확인
```

> 원복을 잊으면 1분마다 백업이 쌓입니다.

테스트로 생긴 덤프는 정리합니다.

```bash
ls -lt /data/weak/DBbackup/*.dump
rm /data/weak/DBbackup/weeklyrpt_<지울파일>.dump
```

#### 15-5. 복원

> 복원 전 애플리케이션을 먼저 중지합니다.

**기존 DB에 덮어쓰기**

```bash
export PGPASSWORD="$(sed -n 's/^DB_PASSWORD=//p' /data/weak/app/.env | tr -d '\r"')"
pg_restore -h 127.0.0.1 -p 5432 -U weeklyrpt_user -d weeklyrpt \
  --clean --if-exists \
  /data/weak/DBbackup/weeklyrpt_20260901_030000.dump
unset PGPASSWORD
```

**새 DB로 복원 (검증용 — 운영 DB를 건드리지 않음)**

```bash
export PGPASSWORD="$(sed -n 's/^DB_PASSWORD=//p' /data/weak/app/.env | tr -d '\r"')"
createdb -h 127.0.0.1 -U weeklyrpt_user weeklyrpt_restore
pg_restore -h 127.0.0.1 -p 5432 -U weeklyrpt_user -d weeklyrpt_restore \
  /data/weak/DBbackup/weeklyrpt_20260901_030000.dump
unset PGPASSWORD
```

#### 15-6. 운영 시 주의사항

- **원격 사본을 두세요.** 백업이 운영 DB와 같은 `/data` 볼륨에 쌓이므로, 디스크가 통째로 고장나면 원본과 백업이 함께 사라집니다.
  ```
  30 3 * * * root rsync -a --delete /data/weak/DBbackup/ backup@nas:/backup/weeklyrpt/
  ```
- **서버가 꺼져 있으면 그날 백업은 건너뜁니다.** 상시 가동이 아니라면 cron 대신 systemd timer 를 쓰면 부팅 후 밀린 백업을 실행합니다 (`Persistent=true`, 상세는 `DB백업_리눅스.md` §4).
- **분기에 1회는 실제로 복원해 보세요.** 한 번도 복원해 보지 않은 백업은 백업이 아닙니다. 15-5 의 "새 DB로 복원" 이 안전합니다.
- **실패 알림:** 스크립트가 실패하면 0이 아닌 코드로 종료하므로 root 앞으로 cron 메일이 갑니다. 메일을 쓰지 않는 환경이면 `backup.log` 를 주기적으로 확인하세요.
- **로그 관리:** `backup.log` 가 커지면 logrotate 를 겁니다.
  ```bash
  cat > /etc/logrotate.d/weeklyrpt-backup << 'ROT'
  /data/weak/DBbackup/backup.log {
      monthly
      rotate 12
      compress
      missingok
      notifempty
  }
  ROT
  ```

**백업 해제:**

```bash
rm /etc/cron.d/weeklyrpt-backup && systemctl restart crond
```

---

## 업데이트 배포

```bash
cd /data/weak/app

# git 소유권 오류 발생 시 1회만 실행
git config --global --add safe.directory /data/weak

# 서버 로컬 변경사항 초기화 후 최신 코드 pull
git reset --hard HEAD
git pull origin main

# PHP 패키지 업데이트
composer install --no-dev --optimize-autoloader

# DB 마이그레이션
php artisan migrate --force

# 프론트엔드 빌드
npm install
npm run build

# 캐시 초기화
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 권한 설정
chown -R nginx:nginx storage bootstrap/cache
```

> **한방 복붙용 (root 실행 기준)**
> ```bash
> cd /data/weak/app && \
> git reset --hard HEAD && \
> git pull origin main && \
> composer install --no-dev --optimize-autoloader && \
> php artisan migrate --force && \
> npm install && \
> npm run build && \
> php artisan config:clear && \
> php artisan route:clear && \
> php artisan view:clear && \
> chown -R nginx:nginx storage bootstrap/cache
> ```

---

## 디렉터리 구조

```
/data/
└── weak/                        # 프로젝트 루트
    ├── db_backup.sh              # DB 백업 스크립트 (root cron, 매일 03:00)
    ├── DBbackup/                 # 백업 덤프 + backup.log (git 추적 제외)
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
| `pg_dump: command not found` | PostgreSQL 클라이언트 미설치 | `dnf install -y postgresql17` |
| cron이 백업을 실행하지 않음 | `/etc/cron.d` 파일 권한이 `644`가 아니거나 실행 계정(`root`) 칸 누락 | `chmod 644` 적용, 시간 5칸 뒤 `root` 가 있는지 확인 |
| 03:00 백업이 건너뛰어짐 | 해당 시각에 서버가 꺼져 있었음 | `systemctl status crond` 확인, 상시 가동이 아니면 systemd timer(`Persistent=true`)로 전환 |
| 백업 디스크 부족 | 보관 기간이 길거나 DB가 커짐 | `db_backup.sh` 의 `KEEP_DAYS` 조정, `BACKUP_DIR` 을 별도 볼륨으로 지정 |

---

## 라이선스

사내 전용 — 외부 배포 금지
