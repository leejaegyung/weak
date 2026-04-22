from fastapi import APIRouter, HTTPException, Depends
from database import get_conn
from models import LoginRequest, TokenResponse, UserOut
from auth import verify_password, create_access_token, hash_password, get_current_user, ACCESS_TOKEN_EXPIRE_HOURS
from pydantic import BaseModel

router = APIRouter(prefix="/api/auth", tags=["auth"])


@router.post("/login", response_model=TokenResponse)
def login(body: LoginRequest):
    with get_conn() as conn:
        row = conn.execute(
            "SELECT * FROM users WHERE username=? AND is_active=1", (body.username,)
        ).fetchone()

    if not row or not verify_password(body.password, row["password_hash"]):
        raise HTTPException(status_code=401, detail="아이디 또는 비밀번호가 올바르지 않습니다")

    with get_conn() as conn:
        conn.execute(
            "UPDATE users SET last_login=datetime('now','localtime') WHERE id=?", (row["id"],)
        )

    token = create_access_token({"sub": str(row["id"]), "username": row["username"], "role": row["role"], "name": row["name"]})

    return TokenResponse(
        access_token=token,
        expires_in=ACCESS_TOKEN_EXPIRE_HOURS * 3600,
        user=UserOut(
            id=row["id"],
            username=row["username"],
            name=row["name"],
            position=row["position"],
            role=row["role"],
            is_active=bool(row["is_active"]),
            created_at=row["created_at"],
            last_login=row["last_login"],
        ),
    )


class RegisterRequest(BaseModel):
    username: str
    password: str
    name: str
    position: str = "사원"


@router.post("/register", response_model=TokenResponse)
def register(body: RegisterRequest):
    if len(body.username.strip()) < 2:
        raise HTTPException(400, "아이디는 2자 이상이어야 합니다")
    if len(body.password) < 4:
        raise HTTPException(400, "비밀번호는 4자 이상이어야 합니다")
    if len(body.name.strip()) < 1:
        raise HTTPException(400, "이름을 입력하세요")

    with get_conn() as conn:
        if conn.execute("SELECT id FROM users WHERE username=?", (body.username.strip(),)).fetchone():
            raise HTTPException(400, "이미 사용 중인 아이디입니다")
        cur = conn.execute(
            """INSERT INTO users (username, password_hash, name, position, role)
               VALUES (?,?,?,?,?)""",
            (body.username.strip(), hash_password(body.password), body.name.strip(), body.position.strip() or "사원", "member"),
        )
        uid = cur.lastrowid
        conn.execute("UPDATE users SET last_login=datetime('now','localtime') WHERE id=?", (uid,))
        row = conn.execute("SELECT * FROM users WHERE id=?", (uid,)).fetchone()

    token = create_access_token({"sub": str(row["id"]), "username": row["username"], "role": row["role"], "name": row["name"]})
    return TokenResponse(
        access_token=token,
        expires_in=ACCESS_TOKEN_EXPIRE_HOURS * 3600,
        user=UserOut(
            id=row["id"], username=row["username"], name=row["name"],
            position=row["position"], role=row["role"], is_active=bool(row["is_active"]),
            created_at=row["created_at"], last_login=row["last_login"],
        ),
    )


@router.get("/me", response_model=UserOut)
def me(user=Depends(get_current_user)):
    with get_conn() as conn:
        row = conn.execute("SELECT * FROM users WHERE id=?", (user["sub"],)).fetchone()
    if not row:
        raise HTTPException(status_code=404, detail="사용자를 찾을 수 없습니다")
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
