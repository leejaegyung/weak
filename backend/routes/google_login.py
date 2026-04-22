import secrets
from fastapi import APIRouter, HTTPException, Depends
from fastapi.responses import RedirectResponse
from pydantic import BaseModel
import google_login as gl
from database import get_conn
from auth import create_access_token, hash_password, require_admin

router = APIRouter(prefix="/api/auth/google", tags=["google-login"])

REDIRECT_URI = "http://localhost:7700/api/auth/google/callback"

_states: dict[str, bool] = {}


@router.get("/status")
def google_status():
    return {
        "enabled": gl.creds_exist(),
        "client_id": gl.get_client_id() if gl.creds_exist() else "",
    }


@router.get("/login")
def google_login():
    if not gl.creds_exist():
        return RedirectResponse("/login.html?error=google_not_configured")
    state = secrets.token_urlsafe(16)
    _states[state] = True
    auth_url = gl.get_auth_url(REDIRECT_URI, state)
    return RedirectResponse(auth_url)


@router.get("/callback")
def google_callback(code: str = "", state: str = "", error: str = ""):
    if error:
        return RedirectResponse(f"/login.html?error={error}")
    if not code:
        return RedirectResponse("/login.html?error=no_code")
    if state not in _states:
        return RedirectResponse("/login.html?error=invalid_state")
    del _states[state]

    try:
        info = gl.fetch_user_info(REDIRECT_URI, code)
    except Exception as e:
        return RedirectResponse(f"/login.html?error=google_failed")

    google_id = info.get("sub", "")
    email     = info.get("email", "")
    name      = info.get("name") or email.split("@")[0]

    if not google_id or not email:
        return RedirectResponse("/login.html?error=no_email")

    with get_conn() as conn:
        row = conn.execute(
            "SELECT * FROM users WHERE google_id=? OR (email=? AND email IS NOT NULL AND email!='')",
            (google_id, email),
        ).fetchone()

        if row:
            if not row["is_active"]:
                return RedirectResponse("/login.html?error=inactive")
            if not row["google_id"]:
                conn.execute(
                    "UPDATE users SET google_id=?, email=? WHERE id=?",
                    (google_id, email, row["id"]),
                )
            conn.execute(
                "UPDATE users SET last_login=datetime('now','localtime') WHERE id=?",
                (row["id"],),
            )
            user = dict(row)
        else:
            cur = conn.execute(
                """INSERT INTO users (username, password_hash, name, position, role, google_id, email)
                   VALUES (?,?,?,?,?,?,?)""",
                (email, hash_password(secrets.token_hex(16)), name, "사원", "member", google_id, email),
            )
            uid = cur.lastrowid
            conn.execute(
                "UPDATE users SET last_login=datetime('now','localtime') WHERE id=?", (uid,)
            )
            user = dict(conn.execute("SELECT * FROM users WHERE id=?", (uid,)).fetchone())

    token = create_access_token({
        "sub":  str(user["id"]),
        "role": user["role"],
        "name": user["name"],
    })
    return RedirectResponse(f"/login.html?token={token}&uname={user['name']}&role={user['role']}")


class GoogleSetupBody(BaseModel):
    client_id: str
    client_secret: str


@router.post("/setup")
def setup_google_login(body: GoogleSetupBody, admin=Depends(require_admin)):
    if not body.client_id.strip() or not body.client_secret.strip():
        raise HTTPException(400, "Client ID와 Client Secret을 모두 입력하세요")
    gl.save_creds(body.client_id.strip(), body.client_secret.strip())
    return {"ok": True}


@router.delete("/setup")
def remove_google_login(admin=Depends(require_admin)):
    gl.delete_creds()
    return {"ok": True}
