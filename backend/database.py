import sqlite3
from pathlib import Path

DB_PATH = Path(__file__).parent / "data" / "reports.db"


def get_conn() -> sqlite3.Connection:
    DB_PATH.parent.mkdir(exist_ok=True)
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    conn.execute("PRAGMA journal_mode=WAL")
    conn.execute("PRAGMA foreign_keys=ON")
    return conn


def init_db():
    with get_conn() as conn:
        conn.executescript("""
            CREATE TABLE IF NOT EXISTS users (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                username      TEXT    UNIQUE NOT NULL,
                password_hash TEXT    NOT NULL,
                name          TEXT    NOT NULL,
                position      TEXT    NOT NULL DEFAULT '사원',
                role          TEXT    NOT NULL DEFAULT 'member',
                is_active     INTEGER NOT NULL DEFAULT 1,
                created_at    TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
                last_login    TEXT,
                google_id     TEXT,
                email         TEXT
            );

            CREATE TABLE IF NOT EXISTS reports (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                author_id     INTEGER NOT NULL REFERENCES users(id),
                week          TEXT    NOT NULL,
                prev_start    TEXT    NOT NULL,
                prev_end      TEXT    NOT NULL,
                curr_start    TEXT    NOT NULL,
                curr_end      TEXT    NOT NULL,
                prev_support  TEXT    NOT NULL DEFAULT '[]',
                curr_support  TEXT    NOT NULL DEFAULT '[]',
                todo_items    TEXT    NOT NULL DEFAULT '[]',
                internal_work TEXT    NOT NULL DEFAULT '',
                shared        TEXT    NOT NULL DEFAULT '',
                status        TEXT    NOT NULL DEFAULT '제출됨',
                created_at    TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
                updated_at    TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
            );

            CREATE TABLE IF NOT EXISTS schedules (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id    INTEGER NOT NULL REFERENCES users(id),
                date       TEXT    NOT NULL,
                content    TEXT    NOT NULL DEFAULT '',
                updated_at TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
                UNIQUE(user_id, date)
            );
        """)
        # 마이그레이션: 기존 DB에 컬럼 추가
        report_cols = [r[1] for r in conn.execute("PRAGMA table_info(reports)").fetchall()]
        if "status" not in report_cols:
            conn.execute("ALTER TABLE reports ADD COLUMN status TEXT NOT NULL DEFAULT '제출됨'")
        user_cols = [r[1] for r in conn.execute("PRAGMA table_info(users)").fetchall()]
        if "google_id" not in user_cols:
            conn.execute("ALTER TABLE users ADD COLUMN google_id TEXT")
        if "email" not in user_cols:
            conn.execute("ALTER TABLE users ADD COLUMN email TEXT")
