#!/usr/bin/env bash
#
# 주간업무보고 PostgreSQL 일일 백업 스크립트
#   - app/.env 에서 DB 접속 정보를 읽어 pg_dump 실행 (비밀번호 하드코딩 없음)
#   - custom 포맷(-Fc)으로 덤프 → pg_restore 로 복원
#   - 저장 위치: /data/weak/DBbackup  (root 크론으로 매일 실행)
#   - KEEP_DAYS 이전 덤프는 자동 삭제
#
# 사용법:  ./db_backup.sh
# 환경변수로 덮어쓰기 가능:  BACKUP_DIR, KEEP_DAYS, ENV_FILE
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${ENV_FILE:-$SCRIPT_DIR/app/.env}"
BACKUP_DIR="${BACKUP_DIR:-$SCRIPT_DIR/DBbackup}"
KEEP_DAYS="${KEEP_DAYS:-30}"

mkdir -p "$BACKUP_DIR"
LOG_FILE="$BACKUP_DIR/backup.log"

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" >> "$LOG_FILE"; }
die() { log "[실패] $*"; echo "백업 실패: $*" >&2; exit 1; }

# 중복 실행 방지 (이전 백업이 아직 돌고 있으면 조용히 종료)
if command -v flock >/dev/null 2>&1; then
  exec 9>"$BACKUP_DIR/.lock"
  flock -n 9 || { log "[건너뜀] 이전 백업이 실행 중"; exit 0; }
fi

[ -f "$ENV_FILE" ] || die ".env 파일을 찾을 수 없음: $ENV_FILE"

# .env 값 읽기 (앞뒤 따옴표 · CR 제거)
get_env() {
  sed -n "s/^[[:space:]]*$1[[:space:]]*=[[:space:]]*//p" "$ENV_FILE" \
    | head -n1 | tr -d '\r' \
    | sed -e 's/^"\(.*\)"$/\1/' -e "s/^'\(.*\)'$/\1/"
}

DB_HOST="$(get_env DB_HOST)";         DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="$(get_env DB_PORT)";         DB_PORT="${DB_PORT:-5432}"
DB_DATABASE="$(get_env DB_DATABASE)"
DB_USERNAME="$(get_env DB_USERNAME)"
DB_PASSWORD="$(get_env DB_PASSWORD)"

[ -n "$DB_DATABASE" ] || die ".env 에서 DB_DATABASE 를 읽지 못함"
[ -n "$DB_USERNAME" ] || die ".env 에서 DB_USERNAME 을 읽지 못함"

# pg_dump 경로 탐색 (PGDG 패키지 설치 시 /usr/pgsql-NN/bin 아래에 있음)
PG_DUMP="$(command -v pg_dump || true)"
for d in /usr/pgsql-*/bin /usr/lib/postgresql/*/bin; do
  [ -x "$d/pg_dump" ] && PG_DUMP="$d/pg_dump"
done
[ -n "$PG_DUMP" ] || die "pg_dump 를 찾을 수 없음 (postgresql client 설치 필요)"

TS="$(date '+%Y%m%d_%H%M%S')"
DUMP_FILE="$BACKUP_DIR/${DB_DATABASE}_${TS}.dump"

log "백업 시작 -> $DUMP_FILE"

if ! PGPASSWORD="$DB_PASSWORD" "$PG_DUMP" \
      -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" \
      -Fc -f "$DUMP_FILE" 2>>"$LOG_FILE"; then
  rm -f "$DUMP_FILE"
  die "pg_dump 오류 발생 (상세 내용은 $LOG_FILE 참조)"
fi

chmod 600 "$DUMP_FILE"
log "[성공] 백업 완료 ($(du -h "$DUMP_FILE" | cut -f1))"

# 보관 기간이 지난 덤프 정리
find "$BACKUP_DIR" -maxdepth 1 -type f -name "${DB_DATABASE}_*.dump" -mtime "+$KEEP_DAYS" \
  -printf '[정리] 삭제: %f\n' -delete >> "$LOG_FILE" 2>/dev/null || true

exit 0
