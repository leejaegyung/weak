from pydantic import BaseModel
from typing import Optional


# ── User ─────────────────────────────────────
class UserCreate(BaseModel):
    username: str
    password: str
    name: str
    position: str = "사원"
    role: str = "member"


class UserUpdate(BaseModel):
    name: Optional[str] = None
    position: Optional[str] = None
    role: Optional[str] = None
    is_active: Optional[bool] = None
    password: Optional[str] = None


class UserOut(BaseModel):
    id: int
    username: str
    name: str
    position: str
    role: str
    is_active: bool
    created_at: str
    last_login: Optional[str]


# ── Auth ──────────────────────────────────────
class LoginRequest(BaseModel):
    username: str
    password: str


class TokenResponse(BaseModel):
    access_token: str
    token_type: str = "bearer"
    expires_in: int
    user: UserOut


# ── Report ───────────────────────────────────
class SupportItem(BaseModel):
    title: str
    content: str = ""


class TodoItem(BaseModel):
    content: str


class ReportCreate(BaseModel):
    week: str
    prev_start: str
    prev_end: str
    curr_start: str
    curr_end: str
    prev_support: list[SupportItem] = []
    curr_support: list[SupportItem] = []
    todo_items: list[TodoItem] = []
    internal_work: str = ""
    shared: str = ""


class ReportUpdate(BaseModel):
    prev_support: Optional[list[SupportItem]] = None
    curr_support: Optional[list[SupportItem]] = None
    todo_items: Optional[list[TodoItem]] = None
    internal_work: Optional[str] = None
    shared: Optional[str] = None
    status: Optional[str] = None


class ReportOut(BaseModel):
    id: int
    author_id: int
    author_name: str
    author_position: str
    week: str
    prev_start: str
    prev_end: str
    curr_start: str
    curr_end: str
    prev_support: list[SupportItem]
    curr_support: list[SupportItem]
    todo_items: list[TodoItem]
    internal_work: str
    shared: str
    status: str = '제출됨'
    created_at: str
    updated_at: str


# ── Schedule ──────────────────────────────────
class ScheduleUpsert(BaseModel):
    date: str
    content: str


class ScheduleOut(BaseModel):
    user_id: int
    user_name: str
    date: str
    content: str
