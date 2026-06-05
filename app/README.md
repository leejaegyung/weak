# 주간업무보고 시스템

SE팀 주간 업무 보고 및 팀 일정 관리 웹 애플리케이션

**Stack:** Laravel 11 · Vue 3 · Inertia.js · PostgreSQL · Nginx

---

## 📦 설치

```bash
# 의존성 설치
composer install
npm install

# 환경 설정
cp .env.example .env
php artisan key:generate

# DB 마이그레이션
php artisan migrate

# 프론트엔드 빌드
npm run build
```

---

## ⚙️ 서버 배포 (Linux)

```bash
# 코드 업데이트
cd /data/weak/app
git pull

# 프론트엔드 빌드 (코드 변경 시)
npm run build

# 캐시 초기화 (설정 변경 시)
php artisan config:clear
php artisan cache:clear
```

---

## ⏰ Laravel 스케줄러 크론잡 설정 (필수)

Webhook·카카오 자동 발송 기능이 동작하려면 **서버에 크론잡을 한 번만 등록**하면 됩니다.

```bash
crontab -e
```

아래 한 줄을 추가하고 저장:

```
* * * * * cd /data/weak/app && php artisan schedule:run >> /dev/null 2>&1
```

등록 확인:

```bash
crontab -l
```

수동 테스트 (즉시 실행):

```bash
cd /data/weak/app && php artisan schedule:run
```

> 크론잡 하나로 **Webhook 자동발송**과 **카카오 자동발송** 모두 작동합니다.

---

## 🔔 자동 발송 기능 설정

크론잡 등록 후 관리자 페이지에서 설정합니다.

### Webhook (Slack / Mattermost)

**알림 설정 → Webhook 설정** 에서:

| 항목 | 설명 |
|------|------|
| Webhook URL | Slack / Mattermost Incoming Webhook URL 입력 |
| Webhook 활성화 | 토글 ON |
| 자동 발송 활성화 | 토글 ON |
| 발송 시간 | 원하는 시간 설정 (예: 오전 09:00) |

- 평일(월~금) 지정 시간에 당일 팀 일정을 자동 발송
- **"오늘 일정 지금 발송"** 버튼으로 즉시 테스트 가능

### 카카오톡

**알림 설정 → 카카오 연동** 에서:

| 항목 | 설명 |
|------|------|
| REST API 키 | 카카오 개발자 콘솔에서 발급 |
| 자동 발송 활성화 | 토글 ON |
| 발송 시간 | 원하는 시간 설정 (예: 오전 09:00) |

- 카카오 연동된 팀원 **전원에게 개별 발송** (미연동 팀원 제외)
- **"오늘 일정 지금 발송 (테스트)"** 버튼으로 즉시 테스트 가능
- 카카오 메시지 200자 제한으로 내용이 길면 앞 195자만 발송됨

> Webhook과 카카오를 동시에 활성화해도 크론잡은 하나면 됩니다.

---

## 🗄️ 데이터베이스

- PostgreSQL 14~17 (기본: 17)
- `.env` 파일에서 `DB_CONNECTION=pgsql` 설정

---

## 🔑 주요 환경 변수 (.env)

```env
APP_URL=https://your-domain.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=weeklyrpt
DB_USERNAME=postgres
DB_PASSWORD=your_password

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=your@email.com
MAIL_FROM_NAME="주간업무보고"
```

---

## 👤 최초 관리자 계정

설치 후 회원가입 → DB에서 직접 role을 `admin`으로 변경:

```sql
UPDATE users SET role = 'admin' WHERE username = 'your_username';
```
