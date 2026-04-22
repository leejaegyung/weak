# 룰 및 코딩 컨벤션 (Coding Conventions & AI Guidelines)

이 문서는 SE-HUB 프로젝트의 코드 일관성을 유지하기 위한 **절대적인 규칙(Absolute Rules)**입니다. Cursor, Claude 등 AI 어시스턴트는 코드를 생성하거나 수정할 때 반드시 이 문서의 지침을 1순위로 따라야 합니다.

## 1. 🤖 AI 행동 지침 (AI System Rules)
* **사전 확인:** 코드를 작성하기 전, 반드시 요구사항이 `PRD.md` 및 `TECH_SPEC.md`와 일치하는지 확인하십시오.
* **불필요한 수정 금지:** 요청받은 기능과 관련 없는 파일이나 기존 로직을 임의로 수정하거나 삭제하지 마십시오.
* **한국어 우선:** 변수명, 클래스명 등 코드 레벨의 네이밍을 제외한 모든 UI 텍스트(라벨, 플레이스홀더, 알림 메시지, 주석 등)는 반드시 **한국어**로 작성하십시오.

## 2. 🎨 프론트엔드 규칙 (Vue 3 + Inertia + Tailwind CSS)

### 2.1. Vue.js (스크립트 및 컴포넌트)
* **Composition API 강제:** 모든 Vue 컴포넌트는 반드시 `<script setup>` 구문을 사용해야 합니다. Options API(`export default { data() ... }`) 사용은 엄격히 금지됩니다.
* **상태 관리:** 폼 전송, 데이터 바인딩, 유효성 검사 에러 처리는 반드시 `@inertiajs/vue3`에서 제공하는 `useForm` 훅을 사용하십시오. 순수 `axios`나 `fetch` 사용은 지양합니다.
* **컴포넌트 분리:** 코드가 150줄 이상 길어지거나 반복되는 UI 요소(버튼, 입력창, 모달, 상태 배지 등)는 `resources/js/Components/` 폴더 하위에 독립된 컴포넌트로 분리하십시오.

### 2.2. 스타일링 (Tailwind CSS)
* **유틸리티 클래스 우선:** 모든 스타일링은 Tailwind CSS 유틸리티 클래스만을 사용하여 HTML 템플릿 내에 작성합니다.
* **커스텀 CSS 금지:** 컴포넌트 내에 `<style>` 태그를 열고 직접 CSS/SCSS를 작성하는 것을 금지합니다. (동적 스타일링이 필요한 경우 `:class="{ 'bg-blue-500': isActive }"` 형태를 사용)
* **디자인 가이드:** * B2B 엔터프라이즈 시스템에 맞게 깔끔하고 여백이 충분한 디자인을 지향합니다. (예: `p-6`, `gap-4`, `shadow-sm`)
  * 모서리는 부드럽게 처리합니다. (예: `rounded-md`, `rounded-lg`)

## 3. ⚙️ 백엔드 규칙 (PHP 8.2+ / Laravel 11.x)

### 3.1. 아키텍처 및 로직 분리
* **Thin Controller, Fat Service:** Controller(`app/Http/Controllers`)는 HTTP 요청을 받고 검증하며, 최종적으로 Inertia 응답을 반환하는 역할만 수행합니다. 비즈니스 로직(DB 쿼리, 데이터 가공 등)은 반드시 `app/Services` 폴더 내의 Service 클래스로 위임하십시오.
* **Inertia 응답:** 데이터 렌더링 시에는 API JSON 응답 리턴이 아닌, `Inertia::render('페이지/경로', [데이터])` 형식을 사용하십시오.

### 3.2. 데이터 검증 (Validation)
* **FormRequest 강제:** Controller 내부에 `$request->validate()` 형식으로 유효성 검사 로직을 하드코딩하지 마십시오. 반드시 `php artisan make:request` 명령어를 통해 `app/Http/Requests` 하위에 독립된 Form Request 클래스를 생성하여 검증하십시오.

## 4. 📝 네이밍 컨벤션 (Naming Conventions)

규칙적이고 예측 가능한 네이밍은 필수입니다.

* **Database (테이블 및 컬럼명):** `snake_case` (예: `weekly_reports`, `start_date`)
* **PHP (클래스, 모델, 컨트롤러):** `PascalCase` 단수형 (예: `WeeklyReport`, `ScheduleController`)
* **PHP (변수, 메서드):** `camelCase` (예: `$weeklyReport`, `getSchedules()`)
* **JavaScript/TypeScript (변수, 함수):** `camelCase` (예: `handleFormSubmit`, `fetchData`)
* **Vue 파일명:** `PascalCase.vue` (예: `DashboardView.vue`, `StatusBadge.vue`)
* **URL 라우트:** `kebab-case` (예: `/weekly-reports/create`)

## 5. 🔒 보안 (Security)
* **권한 검증:** 데이터를 수정하거나 삭제하는 엔드포인트는 반드시 Laravel Policy 또는 Middleware를 통해 해당 유저가 권한이 있는지(본인 데이터인지, 관리자인지) 검증하는 로직을 포함해야 합니다.