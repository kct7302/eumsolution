# EUM Solution 홈페이지

기업 컨설팅 회사용 정적 HTML 홈페이지와 PHP 상담 신청 백엔드로 구성된 로컬 개발 프로젝트입니다.

## 페이지 구성

- `index.html`: 메인 페이지
- `about.html`: 회사소개
- `services.html`: 컨설팅 분야
- `process.html`: 진행 절차
- `cases.html`: 컨설팅 시나리오
- `consult.html`: 상담 신청 화면
- `css/style.css`, `js/main.js`: 공통 디자인 및 화면 동작
- `js/contact.js`: 상담 보안 토큰, 처리 결과 표시 및 입력값 복원
- `contact.php`: 보안 토큰 발급, 상담 정보 검증 및 MariaDB 저장
- `config/db.php`, `config/env.php`: DB 연결 및 환경변수 설정
- `.htaccess`: 기본 페이지와 이전 PHP 주소의 HTML 주소 이동

페이지는 PHP를 실행하지 않는 실제 정적 HTML입니다. 페이지 문구와 회사 정보는 HTML에서 직접 수정합니다. 헤더·푸터는 각 HTML에 포함되어 있으므로 공통 메뉴나 회사 정보 변경 시 6개 페이지에 함께 반영하세요. DB 비밀번호를 HTML 또는 JavaScript에 넣지 마세요.

## 1. 데이터베이스 만들기

MariaDB 클라이언트 또는 HeidiSQL에서 `sql/schema.sql`을 실행합니다. 기본 데이터베이스 이름은 `eumsolution`입니다.

그다음 `sql/create_app_user.example.sql`의 `CHANGE_THIS_PASSWORD` 두 곳을 같은 강한 비밀번호로 바꾸고 관리자 권한으로 실행합니다. 이 프로젝트는 PHP와 호환되는 전용 `eum_app` 계정을 사용하도록 구성되어 있습니다.

## 2. 환경 설정

프로젝트 루트의 `.env.example`을 `.env`라는 이름으로 복사한 다음 로컬 MariaDB 접속 정보를 입력합니다.

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=eumsolution
DB_USER=eum_app
DB_PASSWORD=본인의_MariaDB_비밀번호
DB_CHARSET=utf8mb4
```

`.env`는 `.gitignore`에 포함되어 Git에 저장되지 않습니다.

기존 `.env`의 `SITE_*` 값은 HTML 전환 시 화면에 반영했으며 이후에는 HTML의 회사 정보를 직접 수정합니다. `SITE_*`를 변경해도 정적 페이지가 자동 갱신되지는 않습니다.

## 3. 실행

XAMPP Apache를 실행한 뒤 브라우저에서 아래 주소를 엽니다.

```text
http://localhost/eumsolution/index.html
```

상담 폼 전송 후 `contact_requests` 테이블에 데이터가 추가되는지 확인합니다.

`http://localhost/eumsolution/`도 `index.html`을 엽니다. 기존 `about.php` 등의 화면 주소는 해당 `.html` 주소로 301 이동합니다. PHP 백엔드와 통신하므로 상담 기능은 Apache로 접속하고 JavaScript를 켜서 사용하세요.

### 상담 처리 흐름

1. `consult.html`이 `contact.php?action=form-state`에서 세션별 CSRF 토큰과 이전 처리 결과를 받습니다.
2. 폼을 `contact.php`에 POST하면 서버가 입력과 동의를 검증한 후 PDO 준비문으로 저장합니다.
3. 서버는 303 응답으로 `consult.html`로 이동시키고, 화면에서 성공/실패 메시지를 표시합니다. 검증 실패 시 입력한 내용과 관심 분야를 복원합니다.

보안 토큰과 상담 처리 결과는 정적 HTML에 저장되거나 캐시되지 않습니다. 이전 PHP 화면 소스는 작업 폴더의 `work/html-migration-original`에 백업되어 있습니다.

## 문제 해결

- `could not find driver`: XAMPP `php.ini`에서 `extension=pdo_mysql`이 활성화되어 있는지 확인합니다.
- `Access denied`: `.env`의 사용자 이름과 비밀번호를 확인합니다.
- `Unknown database`: `sql/schema.sql`을 먼저 실행합니다.
- Apache 오류 로그: `C:\xampp\apache\logs\error.log`

Telegram 알림은 상담 저장이 정상 동작한 뒤 추가하는 다음 단계입니다.
