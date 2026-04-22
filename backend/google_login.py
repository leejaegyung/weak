import json
import secrets
import requests as http_requests
from pathlib import Path
from google_auth_oauthlib.flow import Flow

CREDS_FILE = Path(__file__).parent / "data" / "google_login_creds.json"
SCOPES = [
    "openid",
    "https://www.googleapis.com/auth/userinfo.email",
    "https://www.googleapis.com/auth/userinfo.profile",
]


def creds_exist() -> bool:
    return CREDS_FILE.exists()


def save_creds(client_id: str, client_secret: str):
    CREDS_FILE.parent.mkdir(exist_ok=True)
    CREDS_FILE.write_text(json.dumps({"client_id": client_id, "client_secret": client_secret}))


def delete_creds():
    if CREDS_FILE.exists():
        CREDS_FILE.unlink()


def get_client_id() -> str:
    if not CREDS_FILE.exists():
        return ""
    return json.loads(CREDS_FILE.read_text()).get("client_id", "")


def _build_flow(redirect_uri: str) -> Flow:
    creds = json.loads(CREDS_FILE.read_text())
    client_config = {
        "web": {
            "client_id": creds["client_id"],
            "client_secret": creds["client_secret"],
            "auth_uri": "https://accounts.google.com/o/oauth2/auth",
            "token_uri": "https://oauth2.googleapis.com/token",
            "redirect_uris": [redirect_uri],
        }
    }
    return Flow.from_client_config(client_config, scopes=SCOPES, redirect_uri=redirect_uri)


def get_auth_url(redirect_uri: str, state: str) -> str:
    flow = _build_flow(redirect_uri)
    auth_url, _ = flow.authorization_url(
        prompt="select_account",
        access_type="online",
        state=state,
    )
    return auth_url


def fetch_user_info(redirect_uri: str, code: str) -> dict:
    flow = _build_flow(redirect_uri)
    flow.fetch_token(code=code)
    token = flow.credentials.token
    resp = http_requests.get(
        "https://www.googleapis.com/oauth2/v3/userinfo",
        headers={"Authorization": f"Bearer {token}"},
        timeout=10,
    )
    resp.raise_for_status()
    return resp.json()
