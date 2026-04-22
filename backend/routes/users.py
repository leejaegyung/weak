from fastapi import APIRouter, HTTPException, Depends
from database import get_conn
from models import UserCreate, UserUpdate, UserOut
from auth import hash_password, require_admin

router = APIRouter(prefix="/api/users", tags=["users"])


def _row_to_out(row) -> UserOut:
    return UserOut(
        id=row["id"],
        username=row["username"],
        name=row["name"],
        position=row["position"],
        role=row["role"],
        is_active=bool(row["is_active"]),
        created_at=row["created_at"],
        last_login=row["last_login"],
    )


@router.get("", response_model=list[UserOut])
def list_users(admin=Depends(require_admin)):
    with get_conn() as conn:
        rows = conn.execute("SELECT * FROM users ORDER BY name").fetchall()
    return [_row_to_out(r) for r in rows]


@router.post("", response_model=UserOut, status_code=201)
def create_user(body: UserCreate, admin=Depends(require_admin)):
    with get_conn() as conn:
        if conn.execute("SELECT id FROM users WHERE username=?", (body.username,)).fetchone():
            raise HTTPException(status_code=409, detail="이미 존재하는 아이디입니다")
        cur = conn.execute(
            "INSERT INTO users (username, password_hash, name, position, role) VALUES (?,?,?,?,?)",
            (body.username, hash_password(body.password), body.name, body.position, body.role),
        )
        row = conn.execute("SELECT * FROM users WHERE id=?", (cur.lastrowid,)).fetchone()
    return _row_to_out(row)


@router.put("/{user_id}", response_model=UserOut)
def update_user(user_id: int, body: UserUpdate, admin=Depends(require_admin)):
    with get_conn() as conn:
        if not conn.execute("SELECT id FROM users WHERE id=?", (user_id,)).fetchone():
            raise HTTPException(status_code=404, detail="사용자를 찾을 수 없습니다")
        fields, values = [], []
        if body.name is not None:     fields.append("name=?");     values.append(body.name)
        if body.position is not None: fields.append("position=?"); values.append(body.position)
        if body.role is not None:     fields.append("role=?");     values.append(body.role)
        if body.is_active is not None:fields.append("is_active=?");values.append(int(body.is_active))
        if body.password is not None: fields.append("password_hash=?"); values.append(hash_password(body.password))
        if fields:
            conn.execute(f"UPDATE users SET {','.join(fields)} WHERE id=?", (*values, user_id))
        row = conn.execute("SELECT * FROM users WHERE id=?", (user_id,)).fetchone()
    return _row_to_out(row)
