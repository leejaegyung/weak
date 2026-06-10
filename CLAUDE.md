# CLAUDE.md

This file provides guidance to Claude Code when working with this repository.

## 프로젝트 개요

**SE-HUB 주간업무보고 시스템** — 사내 팀원 주간 업무 보고 작성·조회·관리 웹 애플리케이션. 사내 LAN 환경, 브라우저 기반.

## 기술 스택

| 역할 | 기술 |
|------|------|
| 웹서버 | Nginx |
| 백엔드 | Laravel 11.x (PHP) |
| 프론트 | Vue 3 + Inertia.js + Tailwind CSS |
| 데이터베이스 | PostgreSQL 17 |
| 인증 | JWT (8시간) + bcrypt |

## 개발 환경

```bash
composer install
npm install && npm run dev
php artisan serve
php artisan migrate
```

## 핵심 원칙 (항상 준수)

- **코딩 표준 최우선:** 코드 작성 전 반드시 `개발.md` 참조
- **언어 규칙:** UI 텍스트, 메시지, 주석은 모두 **한국어**
- **아키텍처:** Thin Controller + Fat Service (`app/Services/`)
- **폼 처리:** Inertia `useForm` 훅 사용 (하드코딩 검증 금지)
- **스타일:** Tailwind 유틸리티 클래스만 (`<style>` 태그 금지)

## 파일별 참조 가이드

에이전트는 아래 기준에 따라 해당 문서를 참조한 후 코드를 작성한다.

| 작업 상황 | 참조 파일 | 참조 이유 |
|-----------|-----------|-----------|
| **모든 코드 작성** | `개발.md` | 코딩 표준, 네이밍 컨벤션, AI 행동 원칙 |
| **기능 추가 / 화면 수정** | `기획.md` | 요구사항, 화면 설계, 데이터 구조 정의 |
| **DB 스키마 / 아키텍처 작업** | `엔지니어.md` | 테이블 구조, 서비스 레이어 설계 |
| **인증 / 권한 / API 보안 작업** | `보안.md` | JWT 정책, RBAC, Policy 가이드 |

## 핵심 데이터 구조 (빠른 참조)

```
users: id, username, password_hash, name, position, role(admin/member), is_active, sort_order
reports: week(2026-W17), prev_support(JSONB), curr_support(JSONB), todo_items(JSONB),
         internal_work(TEXT), shared(TEXT)
schedules: user_id, date(YYYY-MM-DD), content
```

`prev_support` / `curr_support` 는 `[{title, content}]` 형식의 **완전히 독립된** 배열.

## 사용자 역할

| 역할 | 권한 |
|------|------|
| `admin` | 전체 일정 편집, 전체 보고서 열람/삭제, 사용자 관리 |
| `member` | 본인 일정·보고서 작성/수정, 전체 보고서 열람 |

## 현재 버전 / 로드맵

- **v1.0 (현재):** F01~F06 (로그인, 일정판, 보고서 작성/열람/인쇄, 사용자 관리)
- **v2.0:** Google Sheets 연동, PDF 생성, 마감 알림 (상세: `기획.md` §7)
- **v2.1:** 메일 전송 (상세: `기획.md` §7)
