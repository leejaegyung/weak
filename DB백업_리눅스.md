# DB 백업 / 복원 가이드 (Rocky Linux)

- **배포 경로:** `/data/weak`
- **백업 저장 위치:** `/data/weak/DBbackup`
- **실행 계정:** `root`
- **주기:** 매일 03:00

## 1. 백업 스크립트

`db_backup.sh` — 프로젝트 루트(`/data/weak`)에 위치.

- `app/.env` 에서 DB 접속 정보를 읽음 (**비밀번호 하드코딩 없음**)
- `pg_dump -Fc` (custom 포맷, 자동 압축) → `DBbackup/weeklyrpt_YYYYMMDD_HHMMSS.dump`
- 로그: `DBbackup/backup.log`
- 30일 지난 덤프 자동 삭제
- `flock` 으로 중복 실행 방지, 덤프 파일 권한 `600`

### 환경변수로 조정 가능

| 변수 | 기본값 |
|------|--------|
| `BACKUP_DIR` | `<스크립트 위치>/DBbackup` |
| `KEEP_DAYS` | `30` |
| `ENV_FILE` | `<스크립트 위치>/app/.env` |

## 2. 설치 (서버에서 1회)

```bash
# db_backup.sh 를 /data/weak/ 에 올린 뒤
cd /data/weak
chmod +x db_backup.sh

# 수동 실행으로 동작 확인
./db_backup.sh
cat DBbackup/backup.log
ls -lh DBbackup/
```

`pg_dump: command not found` 이 뜨면 클라이언트를 설치한다.

```bash
sudo dnf install -y postgresql17          # PGDG 저장소 사용 시
# 또는
sudo dnf install -y postgresql
```

## 3. 매일 자동 실행 (root cron)

```bash
cat > /etc/cron.d/weeklyrpt-backup << 'CRON'
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

# 매일 03:00 DB 백업
0 3 * * * root /data/weak/db_backup.sh
CRON

chmod 644 /etc/cron.d/weeklyrpt-backup
systemctl restart crond
```

> `/etc/cron.d/` 파일은 **마지막 줄이 개행으로 끝나야** 한다. 위 heredoc 은 조건을 만족한다.

### 확인

```bash
cat /etc/cron.d/weeklyrpt-backup
systemctl status crond

# 실행 이력
journalctl -u crond --since today | grep db_backup

# 결과
tail -20 /data/weak/DBbackup/backup.log
ls -lh /data/weak/DBbackup/
```

### 크론을 기다리지 않고 즉시 검증
```bash
# 임시로 3분 뒤 실행되게 바꿔 테스트 → 확인 후 원복
```
또는 그냥 `/data/weak/db_backup.sh` 를 root 로 직접 실행해 본다.

## 4. systemd timer (cron 대신 쓸 경우)

```bash
cat > /etc/systemd/system/weeklyrpt-backup.service << 'UNIT'
[Unit]
Description=주간업무보고 DB 백업

[Service]
Type=oneshot
User=root
ExecStart=/data/weak/db_backup.sh
UNIT

cat > /etc/systemd/system/weeklyrpt-backup.timer << 'UNIT'
[Unit]
Description=주간업무보고 DB 백업 (매일 03:00)

[Timer]
OnCalendar=*-*-* 03:00:00
Persistent=true

[Install]
WantedBy=timers.target
UNIT

systemctl daemon-reload
systemctl enable --now weeklyrpt-backup.timer
systemctl list-timers weeklyrpt-backup.timer
```

> cron 과 달리 `Persistent=true` 덕분에 **03:00 에 서버가 꺼져 있었으면 부팅 후 곧바로 밀린 백업을 실행**한다.

## 5. 복원

> 복원 전 애플리케이션을 먼저 중지한다.

### 기존 DB에 덮어쓰기
```bash
export PGPASSWORD="$(sed -n 's/^DB_PASSWORD=//p' /data/weak/app/.env | tr -d '\r"')"
pg_restore -h 127.0.0.1 -p 5432 -U weeklyrpt_user -d weeklyrpt \
  --clean --if-exists \
  /data/weak/DBbackup/weeklyrpt_20260901_030000.dump
unset PGPASSWORD
```

### 새 DB로 복원 (검증용 — 운영 DB를 건드리지 않음)
```bash
export PGPASSWORD="$(sed -n 's/^DB_PASSWORD=//p' /data/weak/app/.env | tr -d '\r"')"
createdb -h 127.0.0.1 -U weeklyrpt_user weeklyrpt_restore
pg_restore -h 127.0.0.1 -p 5432 -U weeklyrpt_user -d weeklyrpt_restore \
  /data/weak/DBbackup/weeklyrpt_20260901_030000.dump
unset PGPASSWORD
```

### 덤프 내용 확인 (복원 없이)
```bash
pg_restore -l /data/weak/DBbackup/weeklyrpt_20260901_030000.dump | head -40
```

## 6. 권장 사항

- **다른 디스크/원격지 사본:** `/data` 볼륨이 통째로 죽으면 백업도 같이 사라진다.
  cron 에 rsync 한 줄을 더 추가한다.
  ```
  30 3 * * * root rsync -a --delete /data/weak/DBbackup/ backup@nas:/backup/weeklyrpt/
  ```
- **복원 훈련:** 분기에 1회 정도 §5 의 "새 DB로 복원"을 실제로 해 본다.
  한 번도 복원해 보지 않은 백업은 백업이 아니다.
- **로그 관리:** `backup.log` 가 계속 커지면 logrotate 를 건다.
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
- **cron 실패 알림:** 백업 실패 시 스크립트가 non-zero 로 종료하므로, root 앞으로 cron 메일이 간다.
  메일을 안 보는 환경이면 `journalctl -u crond` 를 주기적으로 확인하거나 §6 의 로그를 모니터링한다.
