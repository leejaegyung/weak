# 💻 개발 명세서 — 사내 원격 데스크탑 프로그램 (IHRD)
**대상 독자: 개발자**

---

## 목차

1. [개발 환경 설정](#1-개발-환경-설정)
2. [기술 스택](#2-기술-스택)
3. [디렉토리 구조](#3-디렉토리-구조)
4. [통신 프로토콜 명세](#4-통신-프로토콜-명세)
5. [모듈별 개발 명세](#5-모듈별-개발-명세)
6. [UI 명세](#6-ui-명세)
7. [빌드 및 패키징](#7-빌드-및-패키징)

---

## 1. 개발 환경 설정

### 요구 사항

```
Python   : 3.11.x
OS       : Windows 10/11
IDE      : VS Code 또는 PyCharm
가상환경  : venv
```

### 초기 세팅

```bash
# 1. 가상환경 생성
python -m venv .venv
.venv\Scripts\activate

# 2. 패키지 설치
pip install -r requirements.txt

# 3. Host Agent 실행 (개발 테스트)
python host/main.py

# 4. Viewer App 실행 (개발 테스트)
python viewer/main.py
```

### requirements.txt

```txt
mss>=9.0.0
Pillow>=10.0.0
pyautogui>=0.9.54
pynput>=1.7.6
PyQt6>=6.5.0
pyinstaller>=6.0.0
```

---

## 2. 기술 스택

| 분류 | 라이브러리 | 버전 | 용도 |
|------|-----------|------|------|
| 화면 캡처 | `mss` | ≥9.0 | 고속 스크린샷 (GDI 기반) |
| 이미지 처리 | `Pillow` | ≥10.0 | JPEG 압축, 리사이즈 |
| 입력 제어 | `pyautogui` | ≥0.9 | 마우스/키보드 실행 |
| 입력 후킹 | `pynput` | ≥1.7 | 키 이벤트 캡처 (Viewer) |
| 네트워크 | `socket` | stdlib | TCP 소켓 통신 |
| UI (Viewer) | `PyQt6` | ≥6.5 | 뷰어 렌더링 창 |
| UI (Agent) | `tkinter` | stdlib | 허용 팝업 |
| 패키징 | `PyInstaller` | ≥6.0 | .exe 빌드 |
| 로깅 | `logging` | stdlib | 연결 이벤트 기록 |

---

## 3. 디렉토리 구조

```
ihrd/
├── host/                    # Host Agent (대상 PC에 설치)
│   ├── main.py              # 진입점 — Agent 초기화 및 실행
│   ├── server.py            # TCP 서버 — Viewer 연결 수락
│   ├── capture.py           # 화면 캡처 모듈
│   ├── input_handler.py     # 수신된 입력 이벤트 실행
│   ├── tray.py              # 시스템 트레이 아이콘
│   └── popup.py             # 접속 허용/거부 팝업 UI
│
├── viewer/                  # Viewer App (제어 PC에 설치)
│   ├── main.py              # 진입점 — Viewer 초기화 및 실행
│   ├── client.py            # TCP 클라이언트 — Host 연결
│   ├── renderer.py          # 화면 렌더링 (PyQt6 QWidget)
│   ├── input_sender.py      # 마우스/키보드 이벤트 전송
│   └── ui/
│       ├── main_window.py   # 메인 윈도우
│       └── connect_dialog.py# 연결 다이얼로그 (IP 입력)
│
├── common/
│   ├── protocol.py          # 패킷 정의 및 직렬화/역직렬화
│   ├── constants.py         # 공통 상수 (포트, 버퍼 크기 등)
│   └── logger.py            # 공통 로거 설정
│
├── build/                   # PyInstaller 빌드 결과물
├── config.ini               # 설정 파일
├── requirements.txt
└── README.md
```

---

## 4. 통신 프로토콜 명세

### 4.1 패킷 구조

모든 메시지는 아래 고정 헤더 + JSON payload 형식을 따른다.

```
┌──────────┬──────────┬──────────────────────────────┐
│  Magic   │  Length  │         Payload (JSON)        │
│  4 bytes │  4 bytes │         (variable)            │
└──────────┴──────────┴──────────────────────────────┘

Magic: b'IHRD'  (고정 식별자)
Length: Payload 바이트 길이 (Big Endian uint32)
```

### 4.2 메시지 타입

```python
MSG_FRAME     = "frame"      # 화면 프레임 (base64 JPEG)
MSG_MOUSE     = "mouse"      # 마우스 이벤트
MSG_KEYBOARD  = "keyboard"   # 키보드 이벤트
MSG_CONTROL   = "control"    # 제어 메시지 (연결/해제/핑)
```

### 4.3 Payload 상세

#### frame (Host → Viewer)

```json
{
  "type": "frame",
  "ts": 1713000000.123,
  "width": 1280,
  "height": 720,
  "data": "<base64 encoded JPEG bytes>"
}
```

#### mouse (Viewer → Host)

```json
{
  "type": "mouse",
  "action": "move",
  "button": "left",
  "x_ratio": 0.512,
  "y_ratio": 0.334,
  "scroll_dy": 0
}
```

- `action`: `move` | `press` | `release` | `scroll`
- `button`: `left` | `right` | `middle`
- `x_ratio`, `y_ratio`: 화면 해상도 대비 비율 (0.0 ~ 1.0)
  - Host에서 실제 좌표로 역산: `x = x_ratio * screen_width`

#### keyboard (Viewer → Host)

```json
{
  "type": "keyboard",
  "action": "press",
  "key": "ctrl",
  "char": null
}
```

- `action`: `press` | `release`
- `key`: pynput `Key` 열거값 이름 (예: `ctrl`, `alt`, `f1`)
- `char`: 문자 키의 경우 해당 문자 (예: `"a"`, `"1"`)

#### control (양방향)

```json
{
  "type": "control",
  "action": "connect",
  "payload": {}
}
```

- `action`: `connect` | `disconnect` | `ping` | `pong` | `approved` | `denied`

---

## 5. 모듈별 개발 명세

### 5.1 `host/capture.py`

```python
class ScreenCapture:
    """
    mss 기반 고속 화면 캡처
    """

    def __init__(self):
        self.sct = mss.mss()
        self.monitor_index = 1  # 기본: 첫 번째 모니터

    def capture_frame(self, quality: int = 60) -> bytes:
        """
        현재 화면을 JPEG bytes로 반환
        quality: 1~95 (낮을수록 빠름, 화질 저하)
        """

    def get_resolution(self) -> tuple[int, int]:
        """현재 모니터 해상도 (width, height) 반환"""

    def set_monitor(self, index: int = 1):
        """캡처 대상 모니터 설정 (다중 모니터 지원)"""
```

### 5.2 `host/server.py`

```python
class RemoteServer:
    """
    TCP 서버 — Viewer 연결 수락 및 스트리밍 관리
    """

    def start(self, port: int = 9000):
        """
        서버 시작, 연결 대기 루프 실행
        연결 수락 → popup.py 호출 → 허용 시 스트리밍 시작
        """

    def stop(self):
        """서버 종료 및 모든 세션 해제"""

    def _handle_client(self, conn, addr):
        """
        클라이언트 연결 핸들러 (스레드 실행)
        1. popup.py로 허용 팝업 표시
        2. 허용 시: 캡처 스레드 + 입력 수신 루프 시작
        3. 거부 시: control/denied 전송 후 연결 해제
        """

    def _stream_loop(self, conn):
        """캡처 → 압축 → 전송 루프 (별도 스레드)"""

    def _recv_loop(self, conn):
        """입력 이벤트 수신 루프 (별도 스레드)"""
```

### 5.3 `host/input_handler.py`

```python
class InputHandler:
    """
    수신된 입력 이벤트를 pyautogui로 실행
    """

    def handle(self, event: dict):
        """이벤트 타입에 따라 분기 처리"""

    def _handle_mouse(self, event: dict):
        """
        x_ratio, y_ratio → 실제 픽셀 좌표 변환 후 실행
        pyautogui.moveTo / click / scroll 호출
        """

    def _handle_keyboard(self, event: dict):
        """
        key 또는 char에 따라 pyautogui.keyDown / keyUp 호출
        """
```

### 5.4 `viewer/client.py`

```python
class RemoteClient:
    """
    TCP 클라이언트 — Host 연결 및 데이터 송수신
    """

    def connect(self, host: str, port: int = 9000) -> bool:
        """Host에 연결 시도, 성공 여부 반환"""

    def disconnect(self):
        """연결 해제"""

    def start_receive(self, on_frame: Callable[[bytes], None]):
        """
        프레임 수신 루프 시작 (별도 스레드)
        on_frame: 프레임 수신 시 콜백 (JPEG bytes 전달)
        """

    def send_mouse(self, action, button, x_ratio, y_ratio, scroll_dy=0):
        """마우스 이벤트 전송"""

    def send_keyboard(self, action, key=None, char=None):
        """키보드 이벤트 전송"""
```

### 5.5 `viewer/renderer.py`

```python
class RemoteRenderer(QWidget):
    """
    PyQt6 기반 원격 화면 렌더링 위젯
    """

    mouse_event = pyqtSignal(dict)     # 마우스 이벤트 발생 시
    keyboard_event = pyqtSignal(dict)  # 키보드 이벤트 발생 시

    def update_frame(self, jpeg_bytes: bytes):
        """
        수신된 JPEG bytes → QPixmap 변환 → 렌더링
        창 크기에 맞게 비율 유지하며 스케일링
        """

    def mouseMoveEvent(self, event):
        """마우스 좌표 → x_ratio, y_ratio 변환 후 시그널 emit"""

    def mousePressEvent(self, event): ...
    def mouseReleaseEvent(self, event): ...
    def wheelEvent(self, event): ...
    def keyPressEvent(self, event): ...
    def keyReleaseEvent(self, event): ...
```

### 5.6 `common/protocol.py`

```python
MAGIC = b'IHRD'

def pack(payload: dict) -> bytes:
    """dict → JSON → bytes + 헤더 추가"""

def unpack(data: bytes) -> dict:
    """bytes → 헤더 파싱 → JSON → dict"""

def recv_message(sock: socket.socket) -> dict:
    """
    소켓에서 정확한 크기만큼 읽어 메시지 반환
    헤더 4+4 bytes 먼저 읽고, Length만큼 payload 읽기
    """

def send_message(sock: socket.socket, payload: dict):
    """payload dict를 소켓으로 전송"""
```

---

## 6. UI 명세

### 6.1 Viewer 메인 윈도우

```
┌─────────────────────────────────────────────┐
│  IHRD Viewer           [연결] [연결 해제] [─][□][X] │
├─────────────────────────────────────────────┤
│                                             │
│          [ 원격 화면 렌더링 영역 ]            │
│           (RemoteRenderer 위젯)              │
│                                             │
├─────────────────────────────────────────────┤
│ 상태: 연결됨 | 192.168.1.42 | 24 FPS | 68ms │
└─────────────────────────────────────────────┘
```

### 6.2 연결 다이얼로그

```
┌─────────────────────────┐
│       원격 접속          │
├─────────────────────────┤
│ 대상 IP: [____________] │
│ 포  트: [9000_________] │
├─────────────────────────┤
│      [취소]   [연결]     │
└─────────────────────────┘
```

### 6.3 Host 허용 팝업

```
┌─────────────────────────────────┐
│  🖥️ 원격 접속 요청               │
├─────────────────────────────────┤
│  192.168.1.42 에서               │
│  원격 접속을 요청했습니다.         │
│                                 │
│  허용하시겠습니까?                │
│  (30초 내 응답 없으면 자동 거부)  │
├─────────────────────────────────┤
│        [거부]     [허용]         │
└─────────────────────────────────┘
```

---

## 7. 빌드 및 패키징

### PyInstaller 빌드 명령어

```bash
# Host Agent 빌드
pyinstaller --onefile --windowed \
  --icon=assets/host.ico \
  --name="IHRD-Host" \
  --add-data="config.ini;." \
  host/main.py

# Viewer App 빌드
pyinstaller --onefile --windowed \
  --icon=assets/viewer.ico \
  --name="IHRD-Viewer" \
  --add-data="config.ini;." \
  viewer/main.py
```

### 빌드 결과

```
build/
├── IHRD-Host.exe    # 대상 PC에 배포
└── IHRD-Viewer.exe  # 제어 PC에 배포
```

### 주의사항

- `pyautogui`는 빌드 시 `--hidden-import=pyautogui` 필요할 수 있음
- `mss`는 `--collect-all mss` 옵션 필요할 수 있음
- Windows Defender에서 오탐지 가능 → 코드 서명 인증서 적용 권장

---

*문서 버전: v1.0 | 작성일: 2026-04-15 | 역할: 개발*
