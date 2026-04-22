import json
from fastapi import APIRouter, HTTPException, Depends, Query
from database import get_conn
from models import ReportCreate, ReportUpdate, ReportOut, SupportItem, TodoItem
from auth import get_current_user

router = APIRouter(prefix="/api/reports", tags=["reports"])

_JOIN = """
    SELECT r.*, u.name as author_name, u.position as author_position
    FROM reports r JOIN users u ON r.author_id = u.id
"""


def _row_to_out(row) -> ReportOut:
    return ReportOut(
        id=row["id"],
        author_id=row["author_id"],
        author_name=row["author_name"],
        author_position=row["author_position"],
        week=row["week"],
        prev_start=row["prev_start"],
        prev_end=row["prev_end"],
        curr_start=row["curr_start"],
        curr_end=row["curr_end"],
        prev_support=[SupportItem(**i) for i in json.loads(row["prev_support"])],
        curr_support=[SupportItem(**i) for i in json.loads(row["curr_support"])],
        todo_items=[TodoItem(**i) for i in json.loads(row["todo_items"])],
        internal_work=row["internal_work"],
        shared=row["shared"],
        status=row["status"] if row["status"] else "제출됨",
        created_at=row["created_at"],
        updated_at=row["updated_at"],
    )


def _dump(items): return json.dumps([i.model_dump() for i in items], ensure_ascii=False)


@router.get("", response_model=list[ReportOut])
def list_reports(limit: int = Query(30, ge=1, le=1000), offset: int = Query(0, ge=0), user=Depends(get_current_user)):
    with get_conn() as conn:
        rows = conn.execute(f"{_JOIN} ORDER BY r.week DESC, r.created_at DESC LIMIT ? OFFSET ?", (limit, offset)).fetchall()
    return [_row_to_out(r) for r in rows]


@router.post("", response_model=ReportOut, status_code=201)
def create_report(body: ReportCreate, user=Depends(get_current_user)):
    with get_conn() as conn:
        existing = conn.execute(
            "SELECT id FROM reports WHERE author_id=? AND week=?",
            (user["sub"], body.week)
        ).fetchone()
        if existing:
            raise HTTPException(
                status_code=409,
                detail=f"DUPLICATE:{existing['id']}"
            )
        cur = conn.execute(
            """INSERT INTO reports
               (author_id, week, prev_start, prev_end, curr_start, curr_end,
                prev_support, curr_support, todo_items, internal_work, shared)
               VALUES (?,?,?,?,?,?,?,?,?,?,?)""",
            (user["sub"], body.week, body.prev_start, body.prev_end, body.curr_start, body.curr_end,
             _dump(body.prev_support), _dump(body.curr_support), _dump(body.todo_items),
             body.internal_work, body.shared),
        )
        row = conn.execute(f"{_JOIN} WHERE r.id=?", (cur.lastrowid,)).fetchone()
    return _row_to_out(row)


@router.get("/{report_id}", response_model=ReportOut)
def get_report(report_id: int, user=Depends(get_current_user)):
    with get_conn() as conn:
        row = conn.execute(f"{_JOIN} WHERE r.id=?", (report_id,)).fetchone()
    if not row:
        raise HTTPException(status_code=404, detail="보고서를 찾을 수 없습니다")
    return _row_to_out(row)


@router.put("/{report_id}", response_model=ReportOut)
def update_report(report_id: int, body: ReportUpdate, user=Depends(get_current_user)):
    with get_conn() as conn:
        row = conn.execute("SELECT * FROM reports WHERE id=?", (report_id,)).fetchone()
        if not row:
            raise HTTPException(status_code=404, detail="보고서를 찾을 수 없습니다")
        if str(row["author_id"]) != user["sub"] and user["role"] != "admin":
            raise HTTPException(status_code=403, detail="수정 권한이 없습니다")

        fields, values = [], []
        if body.prev_support  is not None: fields.append("prev_support=?");  values.append(_dump(body.prev_support))
        if body.curr_support  is not None: fields.append("curr_support=?");  values.append(_dump(body.curr_support))
        if body.todo_items    is not None: fields.append("todo_items=?");    values.append(_dump(body.todo_items))
        if body.internal_work is not None: fields.append("internal_work=?"); values.append(body.internal_work)
        if body.shared        is not None: fields.append("shared=?");        values.append(body.shared)
        if body.status        is not None: fields.append("status=?");        values.append(body.status)
        if fields:
            fields.append("updated_at=datetime('now','localtime')")
            conn.execute(f"UPDATE reports SET {','.join(fields)} WHERE id=?", (*values, report_id))

        updated = conn.execute(f"{_JOIN} WHERE r.id=?", (report_id,)).fetchone()
    return _row_to_out(updated)


@router.delete("/{report_id}", status_code=204)
def delete_report(report_id: int, user=Depends(get_current_user)):
    with get_conn() as conn:
        row = conn.execute("SELECT author_id FROM reports WHERE id=?", (report_id,)).fetchone()
        if not row:
            raise HTTPException(status_code=404, detail="보고서를 찾을 수 없습니다")
        if str(row["author_id"]) != user["sub"] and user["role"] != "admin":
            raise HTTPException(status_code=403, detail="삭제 권한이 없습니다")
        conn.execute("DELETE FROM reports WHERE id=?", (report_id,))
