from fastapi import APIRouter, Depends, Query
from database import get_conn
from models import ScheduleUpsert, ScheduleOut
from auth import get_current_user

router = APIRouter(prefix="/api/schedules", tags=["schedules"])


@router.get("", response_model=list[ScheduleOut])
def get_schedules(
    date_from: str = Query(...),
    date_to: str = Query(...),
    user=Depends(get_current_user),
):
    with get_conn() as conn:
        rows = conn.execute(
            """SELECT s.user_id, u.name as user_name, s.date, s.content
               FROM schedules s JOIN users u ON s.user_id=u.id
               WHERE s.date BETWEEN ? AND ? AND u.is_active=1
               ORDER BY u.name, s.date""",
            (date_from, date_to),
        ).fetchall()
    return [ScheduleOut(**dict(r)) for r in rows]


@router.put("", response_model=ScheduleOut)
def upsert_schedule(body: ScheduleUpsert, user=Depends(get_current_user)):
    with get_conn() as conn:
        conn.execute(
            """INSERT INTO schedules (user_id, date, content, updated_at)
               VALUES (?, ?, ?, datetime('now','localtime'))
               ON CONFLICT(user_id, date) DO UPDATE SET
                 content=excluded.content,
                 updated_at=excluded.updated_at""",
            (user["sub"], body.date, body.content),
        )
        row = conn.execute(
            """SELECT s.user_id, u.name as user_name, s.date, s.content
               FROM schedules s JOIN users u ON s.user_id=u.id
               WHERE s.user_id=? AND s.date=?""",
            (user["sub"], body.date),
        ).fetchone()
    return ScheduleOut(**dict(row))


@router.put("/{user_id}", response_model=ScheduleOut)
def admin_upsert_schedule(user_id: int, body: ScheduleUpsert, user=Depends(get_current_user)):
    if user["role"] != "admin":
        from fastapi import HTTPException
        raise HTTPException(status_code=403, detail="관리자 권한이 필요합니다")
    with get_conn() as conn:
        conn.execute(
            """INSERT INTO schedules (user_id, date, content, updated_at)
               VALUES (?, ?, ?, datetime('now','localtime'))
               ON CONFLICT(user_id, date) DO UPDATE SET
                 content=excluded.content,
                 updated_at=excluded.updated_at""",
            (user_id, body.date, body.content),
        )
        row = conn.execute(
            """SELECT s.user_id, u.name as user_name, s.date, s.content
               FROM schedules s JOIN users u ON s.user_id=u.id
               WHERE s.user_id=? AND s.date=?""",
            (user_id, body.date),
        ).fetchone()
    return ScheduleOut(**dict(row))
