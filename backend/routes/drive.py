import json
from pathlib import Path
from fastapi import APIRouter, HTTPException, Depends, Request, UploadFile, File
from fastapi.responses import RedirectResponse
from pydantic import BaseModel
from auth import get_current_user, require_admin
from database import get_conn
import google_drive as gd

router = APIRouter(prefix="/api/drive", tags=["drive"])

SETTINGS_FILE = Path(__file__).parent.parent / "data" / "drive_settings.json"
REDIRECT_URI  = "http://localhost:7700/api/drive/callback"


def _load_settings() -> dict:
    if SETTINGS_FILE.exists():
        return json.loads(SETTINGS_FILE.read_text())
    return {"folder_id": None, "folder_name": None, "team_name": "SE팀"}


def _save_settings(data: dict):
    SETTINGS_FILE.parent.mkdir(exist_ok=True)
    SETTINGS_FILE.write_text(json.dumps(data, ensure_ascii=False))


# ── 상태 확인 ────────────────────────────────────────────────────────

@router.get("/status")
def drive_status(user=Depends(get_current_user)):
    has_cred = gd.credentials_file_exists()
    authed   = gd.is_authenticated() if has_cred else False
    settings = _load_settings()
    return {
        "credentials_file": has_cred,
        "authenticated": authed,
        "folder_id":   settings.get("folder_id"),
        "folder_name": settings.get("folder_name"),
        "team_name":   settings.get("team_name", "SE팀"),
    }


# ── credentials.json 업로드 ─────────────────────────────────────────

@router.post("/credentials")
async def upload_credentials(request: Request, admin=Depends(require_admin)):
    body = await request.json()
    if "installed" not in body and "web" not in body:
        raise HTTPException(400, "올바른 credentials.json 형식이 아닙니다 (installed 또는 web 키가 필요합니다)")
    gd.CRED_FILE.write_text(json.dumps(body, ensure_ascii=False, indent=2))
    return {"ok": True}


@router.delete("/credentials")
def delete_credentials(admin=Depends(require_admin)):
    if gd.CRED_FILE.exists():
        gd.CRED_FILE.unlink()
    token = Path(__file__).parent.parent / "data" / "token.json"
    if token.exists():
        token.unlink()
    return {"ok": True}


# ── OAuth 인증 ────────────────────────────────────────────────────────

@router.get("/auth")
def start_auth(admin=Depends(require_admin)):
    if not gd.credentials_file_exists():
        raise HTTPException(400, "credentials.json 파일이 backend/ 폴더에 없습니다. Google Cloud Console에서 다운로드하세요.")
    flow = gd.build_flow(REDIRECT_URI)
    auth_url, _ = flow.authorization_url(prompt="consent", access_type="offline")
    return {"auth_url": auth_url}


@router.get("/callback")
def oauth_callback(code: str, request: Request):
    try:
        gd.save_token_from_code(code, REDIRECT_URI)
    except Exception as e:
        raise HTTPException(400, f"인증 실패: {e}")
    return RedirectResponse(url="/settings.html?drive=ok")


# ── 설정 ────────────────────────────────────────────────────────────

class FolderSetting(BaseModel):
    folder_id: str | None = None
    folder_name: str | None = None
    team_name: str = "SE팀"


@router.post("/settings")
def save_drive_settings(body: FolderSetting, admin=Depends(require_admin)):
    settings = _load_settings()
    settings.update(body.model_dump())
    _save_settings(settings)
    return settings


@router.get("/folders")
def list_folders(q: str = "", admin=Depends(require_admin)):
    if not gd.is_authenticated():
        raise HTTPException(401, "Google 계정 인증이 필요합니다")
    return gd.list_drive_folders(q)


# ── 보고서 내보내기 ──────────────────────────────────────────────────

@router.post("/export/report/{report_id}")
def export_report(report_id: int, user=Depends(get_current_user)):
    if not gd.is_authenticated():
        raise HTTPException(401, "Google 계정 인증이 필요합니다")

    with get_conn() as conn:
        row = conn.execute(
            """SELECT r.*, u.name as author_name, u.position as author_position
               FROM reports r JOIN users u ON r.author_id=u.id WHERE r.id=?""",
            (report_id,)
        ).fetchone()
    if not row:
        raise HTTPException(404, "보고서를 찾을 수 없습니다")

    import json as _json
    report = dict(row)
    report["prev_support"] = _json.loads(report["prev_support"])
    report["curr_support"] = _json.loads(report["curr_support"])
    report["todo_items"]   = _json.loads(report["todo_items"])

    settings = _load_settings()
    try:
        url = gd.export_report_to_drive(report, folder_id=settings.get("folder_id"))
    except Exception as e:
        raise HTTPException(500, f"Google Sheets 생성 실패: {e}")

    return {"url": url}


# ── 팀 일정 내보내기 ─────────────────────────────────────────────────

class ScheduleExportBody(BaseModel):
    date_from: str
    date_to:   str
    dates:     list[dict]  # [{date, label, week}]


@router.post("/export/schedule")
def export_schedule(body: ScheduleExportBody, user=Depends(get_current_user)):
    if not gd.is_authenticated():
        raise HTTPException(401, "Google 계정 인증이 필요합니다")

    with get_conn() as conn:
        users = conn.execute(
            "SELECT id, name FROM users WHERE is_active=1 ORDER BY name"
        ).fetchall()
        schedules = conn.execute(
            """SELECT s.user_id, s.date, s.content
               FROM schedules s JOIN users u ON s.user_id=u.id
               WHERE s.date BETWEEN ? AND ? AND u.is_active=1""",
            (body.date_from, body.date_to)
        ).fetchall()

    settings = _load_settings()
    try:
        url = gd.export_schedule_to_drive(
            users=[dict(u) for u in users],
            schedules=[dict(s) for s in schedules],
            dates=body.dates,
            team_name=settings.get("team_name", "SE팀"),
            folder_id=settings.get("folder_id"),
        )
    except Exception as e:
        raise HTTPException(500, f"Google Sheets 생성 실패: {e}")

    return {"url": url}
