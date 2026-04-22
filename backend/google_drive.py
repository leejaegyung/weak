"""
Google Drive / Sheets 연동 모듈

사전 준비:
1. Google Cloud Console → 프로젝트 생성
2. Google Sheets API + Google Drive API 활성화
3. OAuth2 클라이언트 ID 생성 (데스크톱 앱)
4. credentials.json 다운로드 → backend/ 폴더에 저장
5. 최초 1회: GET /api/drive/auth 접속 → Google 계정 로그인
"""

import json
import os
from pathlib import Path

from google.oauth2.credentials import Credentials
from google.auth.transport.requests import Request
from google_auth_oauthlib.flow import Flow
from googleapiclient.discovery import build

CRED_FILE  = Path(__file__).parent / "credentials.json"
TOKEN_FILE = Path(__file__).parent / "data" / "token.json"
SCOPES = [
    "https://www.googleapis.com/auth/spreadsheets",
    "https://www.googleapis.com/auth/drive.file",
]


# ── 인증 ──────────────────────────────────────────────────────────────

def get_credentials() -> Credentials | None:
    if not TOKEN_FILE.exists():
        return None
    creds = Credentials.from_authorized_user_file(str(TOKEN_FILE), SCOPES)
    if creds and creds.expired and creds.refresh_token:
        creds.refresh(Request())
        _save_token(creds)
    return creds if creds and creds.valid else None


def _save_token(creds: Credentials):
    TOKEN_FILE.parent.mkdir(exist_ok=True)
    TOKEN_FILE.write_text(creds.to_json())


def is_authenticated() -> bool:
    return get_credentials() is not None


def build_flow(redirect_uri: str) -> Flow:
    return Flow.from_client_secrets_file(
        str(CRED_FILE), scopes=SCOPES, redirect_uri=redirect_uri
    )


def save_token_from_code(code: str, redirect_uri: str):
    flow = build_flow(redirect_uri)
    flow.fetch_token(code=code)
    _save_token(flow.credentials)


def credentials_file_exists() -> bool:
    return CRED_FILE.exists()


# ── Sheets / Drive 빌드 ───────────────────────────────────────────────

def _sheets():
    return build("sheets", "v4", credentials=get_credentials())

def _drive():
    return build("drive", "v3", credentials=get_credentials())


# ── 유틸 ─────────────────────────────────────────────────────────────

def _cell(col: int, row: int) -> str:
    """0-based col/row → A1 notation"""
    letters = ""
    col += 1
    while col:
        col, rem = divmod(col - 1, 26)
        letters = chr(65 + rem) + letters
    return f"{letters}{row + 1}"

def _range(c1, r1, c2, r2):
    return f"{_cell(c1,r1)}:{_cell(c2,r2)}"


def _merge(sheet_id, r1, c1, r2, c2):
    return {"mergeCells": {
        "range": {"sheetId": sheet_id, "startRowIndex": r1, "endRowIndex": r2,
                  "startColumnIndex": c1, "endColumnIndex": c2},
        "mergeType": "MERGE_ALL"
    }}

def _fmt_cell(sheet_id, r1, c1, r2, c2, bold=False, bg=None, ha="LEFT", va="MIDDLE", wrap=True, size=10):
    fmt = {
        "textFormat": {"bold": bold, "fontSize": size},
        "horizontalAlignment": ha,
        "verticalAlignment": va,
        "wrapStrategy": "WRAP" if wrap else "OVERFLOW_CELL",
    }
    if bg:
        r, g, b = bg
        fmt["backgroundColor"] = {"red": r/255, "green": g/255, "blue": b/255}
    return {"repeatCell": {
        "range": {"sheetId": sheet_id, "startRowIndex": r1, "endRowIndex": r2,
                  "startColumnIndex": c1, "endColumnIndex": c2},
        "cell": {"userEnteredFormat": fmt},
        "fields": "userEnteredFormat"
    }}

def _border_all(sheet_id, r1, c1, r2, c2):
    line = {"style": "SOLID", "width": 1, "color": {"red":0.5,"green":0.5,"blue":0.5}}
    return {"updateBorders": {
        "range": {"sheetId": sheet_id, "startRowIndex": r1, "endRowIndex": r2,
                  "startColumnIndex": c1, "endColumnIndex": c2},
        "top": line, "bottom": line, "left": line, "right": line,
        "innerHorizontal": line, "innerVertical": line,
    }}

def _col_width(sheet_id, col, width_px):
    return {"updateDimensionProperties": {
        "range": {"sheetId": sheet_id, "dimension": "COLUMNS",
                  "startIndex": col, "endIndex": col+1},
        "properties": {"pixelSize": width_px},
        "fields": "pixelSize"
    }}

def _row_height(sheet_id, row, height_px):
    return {"updateDimensionProperties": {
        "range": {"sheetId": sheet_id, "dimension": "ROWS",
                  "startIndex": row, "endIndex": row+1},
        "properties": {"pixelSize": height_px},
        "fields": "pixelSize"
    }}


# ── 개인 보고서 시트 생성 ─────────────────────────────────────────────

def export_report_to_drive(report: dict, folder_id: str | None = None) -> str:
    """
    보고서 dict → Google Sheet 생성 → Drive 저장 → 시트 URL 반환

    시트 구조 (기존 양식과 동일):
    행0: (빈행)
    행1: [주간업무보고 병합헤더]  F1:전주 G1:기간  H1:이름 I1:이름값
    행2:                         F2:금주 G2:기간  H2:직급 I2:직급값
    행3: (빈행)
    행4: 구분 | 전주 업무 (B~E 병합) | 금주 업무 (F~I 병합)
    행5: 지원 | prev 항목 | curr 항목
    행6: todo항목(일정미정) | 내용 병합
    행7: 내부작업 | 내용 병합
    행8: 공유 | 내용 병합
    """
    sheets_svc = _sheets()
    drive_svc  = _drive()

    name = f"{report['curr_start'][:7].replace('-','.')}.{report['curr_start'][8:]} {report['author_name']} {report['author_position']} 주간업무보고"

    # 1) 스프레드시트 생성
    ss = sheets_svc.spreadsheets().create(body={
        "properties": {"title": name, "locale": "ko_KR"},
        "sheets": [{"properties": {"title": "주간업무보고", "gridProperties": {"rowCount": 30, "columnCount": 10}}}]
    }).execute()

    ss_id    = ss["spreadsheetId"]
    sheet_id = ss["sheets"][0]["properties"]["sheetId"]

    # 2) 값 채우기
    prev_text = _format_support(report.get("prev_support", []))
    curr_text = _format_support(report.get("curr_support", []))
    todo_text = "\n".join(f"- {t['content']}" for t in report.get("todo_items", []))

    prev_range = f"{report['prev_start'][5:].replace('-','.')}~{report['prev_end'][5:].replace('-','.')}"
    curr_range = f"{report['curr_start'][5:].replace('-','.')}~{report['curr_end'][5:].replace('-','.')}"

    values_data = [
        # row 0: blank
        [""],
        # row 1: 주간업무보고 header (A), 전주라벨(F=5), 전주기간(G=6), 이름라벨(H=7), 이름값(I=8)
        ["주간업무보고", "", "", "", "", "전주", prev_range, "이름", report["author_name"]],
        # row 2: 금주, 직급
        ["", "", "", "", "", "금주", curr_range, "직급", report["author_position"]],
        # row 3: blank
        [""],
        # row 4: column headers
        ["구분", f"전주 업무 ({prev_range})", "", "", "", f"금주 업무 ({curr_range})", "", "", ""],
        # row 5: 지원
        ["지원", prev_text, "", "", "", curr_text, "", "", ""],
        # row 6: todo
        ["todo항목\n(일정미정)", todo_text or "(없음)", "", "", "", "", "", "", ""],
        # row 7: 내부작업
        ["내부작업", report.get("internal_work") or "(없음)", "", "", "", "", "", "", ""],
        # row 8: 공유
        ["공유", report.get("shared") or "(없음)", "", "", "", "", "", "", ""],
    ]

    sheets_svc.spreadsheets().values().update(
        spreadsheetId=ss_id,
        range="주간업무보고!A1",
        valueInputOption="RAW",
        body={"values": values_data},
    ).execute()

    # 3) 서식 적용
    LIGHT_BLUE = (197, 217, 241)
    LIGHT_PINK  = (252, 228, 214)
    LIGHT_GRAY  = (242, 242, 242)

    requests = [
        # 열 너비
        _col_width(sheet_id, 0, 90),   # A: 구분
        _col_width(sheet_id, 1, 180),  # B
        _col_width(sheet_id, 2, 60),
        _col_width(sheet_id, 3, 60),
        _col_width(sheet_id, 4, 60),
        _col_width(sheet_id, 5, 180),  # F
        _col_width(sheet_id, 6, 100),  # G
        _col_width(sheet_id, 7, 50),   # H
        _col_width(sheet_id, 8, 80),   # I

        # 행 높이
        _row_height(sheet_id, 4, 24),  # 헤더행
        _row_height(sheet_id, 5, 150), # 지원
        _row_height(sheet_id, 6, 80),  # todo
        _row_height(sheet_id, 7, 80),  # 내부작업
        _row_height(sheet_id, 8, 60),  # 공유

        # 병합: 주간업무보고 제목 A1:E2
        _merge(sheet_id, 0, 0, 2, 5),
        # 병합: 전주 업무 B4:E4 (행4=인덱스4)
        _merge(sheet_id, 4, 1, 5, 5),
        # 병합: 금주 업무 F4:I4
        _merge(sheet_id, 4, 5, 5, 9),
        # 병합: 지원 전주 B5:E5
        _merge(sheet_id, 5, 1, 6, 5),
        # 병합: 지원 금주 F5:I5
        _merge(sheet_id, 5, 5, 6, 9),
        # 병합: todo B6:I6
        _merge(sheet_id, 6, 1, 7, 9),
        # 병합: 내부작업 B7:I7
        _merge(sheet_id, 7, 1, 8, 9),
        # 병합: 공유 B8:I8
        _merge(sheet_id, 8, 1, 9, 9),

        # 서식: 제목
        _fmt_cell(sheet_id, 0, 0, 2, 5, bold=True, bg=LIGHT_BLUE, ha="CENTER", size=14),
        # 서식: 헤더행 (구분/전주업무/금주업무)
        _fmt_cell(sheet_id, 4, 0, 5, 9, bold=True, bg=LIGHT_GRAY, ha="CENTER"),
        # 서식: 구분 열 (A5:A8)
        _fmt_cell(sheet_id, 5, 0, 9, 1, bold=True, bg=LIGHT_GRAY, ha="CENTER"),
        # 서식: 금주 업무 컬럼 배경
        _fmt_cell(sheet_id, 4, 5, 9, 9, bg=LIGHT_PINK),
        # 서식: 전체 내용 wrap
        _fmt_cell(sheet_id, 5, 1, 9, 9, va="TOP"),

        # 테두리
        _border_all(sheet_id, 0, 0, 9, 9),
    ]

    sheets_svc.spreadsheets().batchUpdate(
        spreadsheetId=ss_id, body={"requests": requests}
    ).execute()

    # 4) Drive 폴더로 이동
    if folder_id:
        file = drive_svc.files().get(fileId=ss_id, fields="parents").execute()
        prev_parents = ",".join(file.get("parents", []))
        drive_svc.files().update(
            fileId=ss_id,
            addParents=folder_id,
            removeParents=prev_parents,
            fields="id, parents",
        ).execute()

    return f"https://docs.google.com/spreadsheets/d/{ss_id}/edit"


# ── 팀 일정 시트 생성 ────────────────────────────────────────────────

def export_schedule_to_drive(users: list, schedules: list, dates: list, team_name: str, folder_id: str | None = None) -> str:
    """
    팀 주간 일정 → Google Sheet 생성

    dates: [{"date": "2026-04-13", "label": "13"}, ...]  (금주 5일 + 차주 5일)
    users: [{"id": int, "name": str}, ...]
    schedules: [{"user_id": int, "date": str, "content": str}, ...]
    """
    sheets_svc = _sheets()
    drive_svc  = _drive()

    base_date = dates[0]["date"] if dates else ""
    name = f"{base_date[:7].replace('-','.')} {team_name} 팀 주간일정"

    ss = sheets_svc.spreadsheets().create(body={
        "properties": {"title": name, "locale": "ko_KR"},
        "sheets": [{"properties": {"title": "주간일정", "gridProperties": {
            "rowCount": len(users)+10, "columnCount": len(dates)+2
        }}}]
    }).execute()

    ss_id    = ss["spreadsheetId"]
    sheet_id = ss["sheets"][0]["properties"]["sheetId"]

    # 일정 인덱스 빌드
    sched_map = {(s["user_id"], s["date"]): s["content"] for s in schedules}

    this_week = [d for d in dates if d.get("week") == "this"]
    next_week = [d for d in dates if d.get("week") == "next"]

    # 값 구성
    rows = [
        [""],  # row 0: blank
        # row 1: 이름 | 금주 (span) | (blank*4) | 차주 (span)
        ["이름"] + ["금주"] + [""]*4 + ["차주"] + [""]*4,
        # row 2: 이름 | 날짜 라벨들
        ["이름"] + [d["label"] for d in dates],
    ]
    for user in users:
        row = [user["name"]]
        for d in dates:
            row.append(sched_map.get((user["id"], d["date"]), ""))
        rows.append(row)

    sheets_svc.spreadsheets().values().update(
        spreadsheetId=ss_id, range="주간일정!A1",
        valueInputOption="RAW", body={"values": rows}
    ).execute()

    LIGHT_BLUE = (197, 217, 241)
    LIGHT_PINK  = (252, 228, 214)
    LIGHT_GRAY  = (242, 242, 242)

    n_this = len(this_week)
    n_next = len(next_week)
    n_users = len(users)

    requests = [
        # 열 너비
        _col_width(sheet_id, 0, 80),
        *[_col_width(sheet_id, i+1, 100) for i in range(len(dates))],

        # 행 높이
        _row_height(sheet_id, 1, 28),
        _row_height(sheet_id, 2, 28),
        *[_row_height(sheet_id, 3+i, 60) for i in range(n_users)],

        # 병합: 금주 헤더
        _merge(sheet_id, 1, 1, 2, 1+n_this),
        # 병합: 차주 헤더
        _merge(sheet_id, 1, 1+n_this, 2, 1+n_this+n_next),

        # 서식: 금주 헤더
        _fmt_cell(sheet_id, 1, 1, 2, 1+n_this, bold=True, bg=LIGHT_BLUE, ha="CENTER"),
        # 서식: 차주 헤더
        _fmt_cell(sheet_id, 1, 1+n_this, 2, 1+n_this+n_next, bold=True, bg=LIGHT_PINK, ha="CENTER"),
        # 서식: 날짜행
        _fmt_cell(sheet_id, 2, 0, 3, 1+len(dates), bold=True, bg=LIGHT_GRAY, ha="CENTER"),
        # 서식: 이름 열
        _fmt_cell(sheet_id, 1, 0, 3+n_users, 1, bold=True, bg=LIGHT_GRAY, ha="CENTER"),
        # 서식: 내용 wrap
        _fmt_cell(sheet_id, 3, 1, 3+n_users, 1+len(dates), va="TOP"),

        # 테두리
        _border_all(sheet_id, 0, 0, 3+n_users, 1+len(dates)),
    ]

    sheets_svc.spreadsheets().batchUpdate(
        spreadsheetId=ss_id, body={"requests": requests}
    ).execute()

    if folder_id:
        file = drive_svc.files().get(fileId=ss_id, fields="parents").execute()
        prev_parents = ",".join(file.get("parents", []))
        drive_svc.files().update(
            fileId=ss_id,
            addParents=folder_id,
            removeParents=prev_parents,
            fields="id, parents",
        ).execute()

    return f"https://docs.google.com/spreadsheets/d/{ss_id}/edit"


# ── Drive 폴더 목록 조회 ──────────────────────────────────────────────

def list_drive_folders(query: str = "") -> list:
    drive_svc = _drive()
    q = "mimeType='application/vnd.google-apps.folder' and trashed=false"
    if query:
        q += f" and name contains '{query}'"
    result = drive_svc.files().list(q=q, fields="files(id, name)", pageSize=30).execute()
    return result.get("files", [])


# ── 내부 헬퍼 ────────────────────────────────────────────────────────

def _format_support(items: list) -> str:
    if not items:
        return ""
    lines = []
    for i, item in enumerate(items, 1):
        lines.append(f"{i}. {item.get('title','')}")
        if item.get("content"):
            for line in item["content"].strip().splitlines():
                lines.append(f"   {line}")
    return "\n".join(lines)
