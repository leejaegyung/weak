from contextlib import asynccontextmanager
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from database import init_db, get_conn
from auth import hash_password
from routes import auth, reports, users, schedules, meta, drive, google_login, export_excel


@asynccontextmanager
async def lifespan(app):
    init_db()
    _seed_admin()
    yield


app = FastAPI(title="주간업무보고 API", version="2.0.0", lifespan=lifespan)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(auth.router)
app.include_router(reports.router)
app.include_router(users.router)
app.include_router(schedules.router)
app.include_router(meta.router)
app.include_router(drive.router)
app.include_router(google_login.router)
app.include_router(export_excel.router)


def _seed_admin():
    with get_conn() as conn:
        if not conn.execute("SELECT id FROM users WHERE username='admin'").fetchone():
            conn.execute(
                "INSERT INTO users (username, password_hash, name, position, role) VALUES (?,?,?,?,?)",
                ("admin", hash_password("admin1234"), "관리자", "관리자", "admin"),
            )
            print("[startup] 기본 관리자 계정 생성: admin / admin1234")


@app.get("/api/health")
def health():
    return {"status": "ok"}


if __name__ == "__main__":
    import uvicorn
    uvicorn.run("main:app", host="0.0.0.0", port=8000, reload=True)
