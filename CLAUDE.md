# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**IHRD (InHouse Remote Desktop)** — 사내 원격 데스크탑 프로그램. Python 3.11+, Windows 10/11 전용. 외부 서버 없이 사내 LAN P2P 직접 연결 방식.

Two deployable components:
- **Host Agent** (`host/`) — 대상 PC에 설치. 화면 캡처 및 입력 실행
- **Viewer App** (`viewer/`) — 제어 PC에 설치. 화면 렌더링 및 입력 전송

## Development Setup

```bash
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt

python host/main.py   # Run Host Agent
python viewer/main.py # Run Viewer App
```

## Build

```bash
# Host Agent
pyinstaller --onefile --windowed --icon=assets/host.ico --name="IHRD-Host" --add-data="config.ini;." host/main.py

# Viewer App
pyinstaller --onefile --windowed --icon=assets/viewer.ico --name="IHRD-Viewer" --add-data="config.ini;." viewer/main.py
```

Known PyInstaller issues: `--hidden-import=pyautogui` and `--collect-all mss` may be needed. Windows Defender may false-positive on the exe — code signing is recommended for distribution.

## Architecture

### Communication Protocol

All messages use a fixed header + JSON payload:
```
Magic(4B 'IHRD') | Length(4B Big-Endian uint32) | Payload(JSON)
```

Message types: `frame` (Host→Viewer), `mouse` / `keyboard` (Viewer→Host), `control` (bidirectional).

TCP is a stream protocol — always read exact byte counts using a loop:
```python
def recv_exact(sock, n):
    buf = b''
    while len(buf) < n:
        chunk = sock.recv(n - len(buf))
        if not chunk:
            raise ConnectionError("연결 끊김")
        buf += chunk
    return buf
```
Read 8-byte header first, extract Length, then read that many payload bytes.

Mouse coordinates use ratios (0.0–1.0), not pixels. Host converts: `x = x_ratio * screen_width`.

Frame payload: `{"type": "frame", "ts": ..., "width": 1280, "height": 720, "data": "<base64 JPEG>"}`.

### Thread Model

**Host Agent:**
- Main Thread: tkinter event loop (tray icon, approval popup)
- Server Thread: `socket.accept()` loop
- Capture Thread: mss capture → JPEG compress → send (20 FPS)
- Input Thread: receive input events → `InputHandler.handle()`

**Viewer App:**
- Main Thread: PyQt6 event loop (UI rendering, input capture)
- Receive Thread: receive frames → emit `pyqtSignal` → Main Thread renders
- Send Thread: consume input event Queue → send

PyQt6 UI must **only** be updated from Main Thread. Use Qt signal/slot to cross threads. `Queue(maxsize=3)` drops oldest frames under back-pressure to keep display current.

### Performance Targets (1Gbps LAN)

| 항목 | 목표 |
|------|------|
| 화면 FPS | 20 FPS |
| 입력 지연 | ≤ 50ms |
| JPEG 품질 | quality=60 (기본), 지연 증가 시 40까지 낮춤 |
| 해상도 | 1280×720 다운스케일 (기본) |

FPS loop: capture → send → `time.sleep(max(0, FRAME_INTERVAL - elapsed))`.

### Error Handling

| 상황 | 처리 |
|------|------|
| 네트워크 끊김 | 자동 재연결 3회 (3초 간격) |
| 대상 PC 거부 | `control/denied` → Viewer 알림 |
| 포트 충돌 `WinError 10048` | config.ini 포트 변경 안내 |
| 화면 캡처 실패 | 해당 프레임 스킵, 세션 유지 |
| `pyautogui.FailSafeException` | 로그 기록 후 세션 유지 |

Approval popup auto-denies after 30 seconds of no response.

## Security

Three-layer access control:
1. **IP Whitelist** — `config.ini`의 `allowed_ip_range` (CIDR). 미허용 IP는 즉시 소켓 차단.
2. **User Approval Popup** — 30초 무응답 시 자동 거부.
3. **Session PIN** (v1.1+) — 선택적 1회용 6자리 PIN.

v1은 평문 TCP 전송. Wi-Fi 환경에서는 TLS 적용 전까지 사용 금지. v2에서 TLS/mTLS 예정.

IP check implementation:
```python
import ipaddress
def is_allowed(ip, allowed_range):
    return ipaddress.ip_address(ip) in ipaddress.ip_network(allowed_range)
```

## config.ini

```ini
[network]
port = 9000
buffer_size = 65536
reconnect_attempts = 3
socket_timeout = 10

[capture]
fps = 20
quality = 60
scale_width = 1280
scale_height = 720

[security]
allowed_ip_range = 192.168.1.0/24
require_approval = true
session_timeout = 3600

[logging]
log_dir = C:\IHRD\logs
log_level = INFO
```

Config is loaded relative to the executable: `os.path.dirname(os.path.abspath(__file__))`.

## Logging

일별 로그 파일: `C:\IHRD\logs\ihrd_YYYYMMDD.log`. 90일 보존.
Log format: `YYYY-MM-DD HH:MM:SS | LEVEL | EVENT | details`
Events: `CONNECT` (ALLOWED/DENIED), `DISCONNECT` (with duration), `BLOCKED` (IP_NOT_ALLOWED), `CAPTURE` errors.

## Firewall Setup (Host PC)

```powershell
netsh advfirewall firewall add rule `
  name="IHRD Host Agent" dir=in action=allow `
  protocol=TCP localport=9000
```

UAC-restricted app control requires Host Agent to run as Administrator.

## UI Design System

Key rules from [디자인.md](디자인.md):
- **No borders**: define boundaries through background color shifts only, never 1px solid lines
- **No dividers**: separate list items with 12px vertical space or hover state
- **Colors**: `surface` #f7f9fb (workspace), `primary` #001b44 (CTA), `on_surface` #191c1e (text — never pure #000000)
- **Fonts**: Manrope for display/headlines, Inter for titles/body/labels
- **Corner radius**: `0.75rem` for all interactive elements, `1rem` for cards
- **Shadows**: ambient `0px 12px 32px rgba(0, 26, 66, 0.06)` (navy-tinted, not black); only on top-most floating elements
- **"Live" status chip**: `tertiary_fixed` #ffdbcd background (not standard green)

## Document Files (This Repository)

이 저장소는 현재 코드 없이 설계 문서만 포함:
- [`개발.md`](개발.md) — 개발자용 명세 (모듈 API, 프로토콜 상세)
- [`기획.md`](기획.md) — 기획/PM용 (요구사항, 마일스톤)
- [`엔지니어.md`](엔지니어.md) — 엔지니어링 설계 (아키텍처, 성능, 에러 처리)
- [`보안.md`](보안.md) — 보안 설계 (위협 모델, 인증, 감사 로그)
- [`디자인.md`](디자인.md) — UI 디자인 시스템 (색상, 타이포그래피, 컴포넌트)

## v2 Roadmap

File transfer (drag-and-drop), clipboard sync, multi-monitor switching, chat, connection history UI, 1:N sessions. Long-term: relay server with STUN/TURN/WebRTC for external access without VPN.
