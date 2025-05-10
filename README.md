# 🇯🇵 sws-jlpt - Backend

**sws-jlpt**는 일본어 학습을 위한 AI 기반 웹 서비스의 백엔드입니다. 이 프로젝트는 Laravel 프레임워크를 기반으로 구축되었으며, 사용자 맞춤형 AI 설정, GPT 연동 대화 처리, 단어 및 문장 퀴즈 생성, 즐겨찾기 기능 등 다양한 기능을 제공합니다. 프론트엔드(Vue)와 RESTful API를 통해 통신하며, JWT 기반 인증 구조를 채택하고 있습니다.

---

## 🚀 주요 기능

* 🔐 **카카오 로그인 기반 JWT 인증 시스템**
* ⚙️ **사용자 맞춤 AI 설정**: 아바타, 성격, 어투, 목소리, JLPT 레벨 등
* 💬 **GPT 대화 처리 및 대화 내용 요약** 저장
* 🧠 **문장 툴팁 요청 처리** (단어/문법/해석 JSON 분석)
* ⭐ **단어/문장/문법 즐겨찾기 기능** 및 즐겨찾기 리스트 분류 시스템
* 🧪 **GPT 퀴즈 생성 API** (문장 기반 퀴즈 3종 자동 생성 구조 포함)

---

## 🧩 폴더 구조

```
app/Http/Controllers/
├── ChatController.php              # GPT 대화 처리 및 툴팁 응답
├── FavoriteController.php          # 즐겨찾기 항목 처리 (단어/문장/문법)
├── FavoriteListController.php      # 즐겨찾기 리스트 생성/조회/삭제
├── QuizController.php              # 문장/문법 기반 퀴즈 자동 생성 API
├── TooltipController.php           # 문장 내 단어/문법 분석 GPT 요청
├── UserController.php              # 카카오 로그인 및 AI 설정 처리
```

---

## 📬 API 요약

| 메서드        | 경로                              | 설명                  |
| ---------- | ------------------------------- | ------------------- |
| POST       | `/api/login`                    | 카카오 로그인 및 JWT 발급    |
| GET/POST   | `/api/ai-settings`              | 사용자 AI 설정 조회 및 저장   |
| POST       | `/api/chat`                     | 사용자 입력 기반 GPT 대화 요청 |
| GET        | `/api/chat-memories`            | 이전 대화 요약 불러오기       |
| POST       | `/api/chat/tooltip`             | 문장 분석 및 단어/문법 툴팁 요청 |
| GET        | `/api/favorites/lists`          | 즐겨찾기 리스트 목록 조회      |
| POST       | `/api/favorites/lists`          | 새 즐겨찾기 리스트 생성       |
| PUT/DELETE | `/api/favorites/lists/{id}`     | 리스트 제목 수정/삭제        |
| GET        | `/api/favorites/words/{listId}` | 특정 리스트 단어 즐겨찾기 조회   |
| POST       | `/api/favorites/words/toggle`   | 단어 즐겨찾기 등록/해제       |

---

## 🔧 사용 기술

| 분류     | 기술                                |
| ------ | --------------------------------- |
| 언어     | PHP 8.x                           |
| 프레임워크  | Laravel 10.x                      |
| 인증     | JWT (tymon/jwt-auth)              |
| API 통신 | RESTful 구조, Laravel Router        |
| 데이터베이스 | MySQL                             |
| 기타     | Laravel artisan, .env 설정 기반 환경 분리 |

---

## ⚙️ 실행 방법

```bash
git clone https://github.com/사용자명/sws-jlpt-backend.git
cd sws-jlpt-backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

> `.env` 파일에서 DB 접속 정보, JWT\_SECRET 등 반드시 설정 필요

---

## 🧪 테스트

```bash
php artisan test
```

또는 API별 개별 호출 테스트는 Postman/Insomnia를 이용해 JWT 인증 후 테스트 가능합니다.

---

## 👨‍💻 개발자

* 손우성 ([@captain9802](https://github.com/captain9802))

---
