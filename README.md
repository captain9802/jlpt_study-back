# 🇯🇵 sws-jlpt - Backend

**sws-jlpt**는 일본어 학습을 위한 AI 기반 웹 서비스의 백역데인드입니다. Laravel 12 기반으로 구축되어 있고, 사용자 맞춤 AI 설정, GPT 대화, JLPT 단어 클릭, 퀴즈 생성, TTS(음성 추가), 번역, 즐겨찾기 기능을 제공합니다.

---

## 🚀 주요 기능

* 🔐 **카카오 로그인 기반 JWT 인증**
* ⚙️ **사용자 맞춤 AI 설정**: 아바타, 성격, 어투, 모고시, JLPT 레벨
* 💬 **GPT 대화** 및 **대화 요약 저장**
* 🧠 **문장 툴핑 요청 처리**: 단어/문법/해석 분석
* ⭐ **단어/문장/문법 즐겨찾기** 및 **리스트 기반 분류**
* 🧪 **GPT 기반 문장/문법 퀴즈 3종 자동 생성 API**
* 📢 **TTS 기능 (OpenJTalk)**: 일본어 문장을 음성으로 변환
* 🌐 **번역 기능 (GPT 기반 번역)**
* 📆 **오늘의 단어 제공 및 JLPT 레벨별 단어 조회**

---

## 🧩 폴더 구조

```
app/Http/Controllers/
├── ChatController.php              # GPT 대화 및 툴팁 처리
├── FavoriteController.php          # 즐겨찾기 항목 처리
├── FavoriteListController.php      # 즐겨찾기 리스트 관리
├── QuizController.php              # 문장/문법 퀴즈 생성
├── TooltipController.php           # 문장 내 단어/문법 분석 요청
├── UserController.php              # 카카오 로그인 및 AI 설정
├── JlptWordController.php          # JLPT 단어 제공 및 오늘의 단어
├── TranslateController.php         # GPT 기반 문장 번역 처리
├── TtsController.php               # OpenJTalk 음성 변환 처리
```

---

## 📬 주요 API 요약

| 메서드        | 경로                               | 설명                  |
| ---------- | -------------------------------- | ------------------- |
| POST       | `/api/login`                     | 카카오 로그인 및 JWT 발급    |
| GET/POST   | `/api/ai-settings`               | AI 설정 조회 및 저장       |
| PUT        | `/api/ai-settings/language-mode` | AI 언어 모드 업데이트       |
| POST       | `/api/chat`                      | 사용자 입력 기반 GPT 대화 요청 |
| GET        | `/api/chat-memories`             | 대화 요약 불러오기          |
| POST       | `/api/chat/tooltip`              | 문장 분석 및 툴팁 요청       |
| GET        | `/api/favorites/lists`           | 즐겨찾기 리스트 조회         |
| POST       | `/api/favorites/lists`           | 즐겨찾기 리스트 생성         |
| PUT/DELETE | `/api/favorites/lists/{id}`      | 리스트 수정/삭제           |
| GET        | `/api/favorites/words/{listId}`  | 단어 즐겨찾기 항목 조회       |
| POST       | `/api/favorites/words/toggle`    | 단어 즐겨찾기 등록/해제       |
| GET        | `/api/words`                     | JLPT 단어 조회 (레벨 기반)  |
| GET        | `/api/today-word`                | 오늘의 단어 반환           |
| POST       | `/api/translate`                 | 문장 번역 요청            |
| POST       | `/api/tts`                       | TTS 음성 변환 요청        |

---

## 🔧 사용 기술 스택

| 분류     | 기술 명세                                       |
| ------ | ------------------------------------------- |
| 언어     | PHP ^8.2                                    |
| 프레임워크  | Laravel ^12.0                               |
| 인증     | JWT (`tymon/jwt-auth`)                      |
| 데이터베이스 | MySQL                                       |
| TTS 엔진 | OpenJTalk                                   |
| 번역 API | GPT 기반 문장 분석                                |
| 기타     | Laravel Artisan, RESTful API, .env 기반 설정 분리 |

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

> `.env`에서 DB 접속 정보 및 `JWT_SECRET` 필수 설정 필요

---

## 🧪 테스트 방법

```bash
php artisan test
```

또는 Postman/Insomnia를 통해 JWT 인증 후 API 개별 테스트 가능

---

## 🧑‍💻 개발자

* 손우성 ([@captain9802](https://github.com/captain9802))

---
