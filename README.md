# 📚 책장 스캐너 | BOOK SCANNER

**"책장 사진 한 장으로 완성되는 나만의 스마트 서재"**

이 프로젝트는 책장 사진을 찍으면 AI가 책등을 인식하여 자동으로 도서 정보를 정리해주는 웹 애플리케이션입니다.  
Google Gemini의 강력한 비전 인식 능력과 **네이버 책 검색 API**, **Google Books API**를 결합하여 한국 책과 외국 책 모두 정확하고 풍부한 도서 정보를 제공합니다.

---

## ✨ 주요 기능 (Key Features)

1.  **📸 AI 촬영 및 분석**
    *   책장이 나온 사진을 업로드하거나 바로 촬영하면, **Google Gemini 2.0 Flash** 모델이 이미지 내의 모든 책등을 분석하여 제목, 저자, 출판사를 추출합니다.

2.  **🔍 자동 데이터 보정 (Auto Enrichment)**
    *   AI가 읽어낸 정보를 바탕으로 **네이버 책 검색 API**와 **Google Books API**를 실시간으로 조회하여 **ISBN, 줄거리, 출판일, 정식 표지 이미지**를 자동으로 채워 넣습니다.
    *   **네이버 API 우선 검색**: 한국 책 표지 검색률 대폭 향상
    *   **중복 방지**: ISBN 또는 제목+저자로 중복 저장 방지

3.  **🕵️ 꼼꼼하게 다시 찾기 (Deep Retry)**
    *   표지나 정보가 누락된 책이 있다면? "꼼꼼하게 다시 찾기" 버튼 하나로 AI가 검색 조건을 완화하여(부제 제거, 키워드 검색 등) 집요하게 정보를 찾아냅니다.

4.  **📊 엑셀 내보내기 (Excel Export)**
    *   정리된 나만의 서재 목록을 클릭 한 번으로 `.xlsx` 엑셀 파일로 다운로드할 수 있습니다.

5.  **🎨 감성적이고 직관적인 UI**
    *   **한|영 모드**: 한국어/영어 언어 전환 지원
    *   **독서 명언**: 분석 중 50개의 독서 명언을 랜덤으로 표시
    *   **카운트다운**: 5초 자동 분석 시작 기능
    *   **Dynamic UX**: 생동감 넘치는 분석 로딩 바와 반응형 디자인으로 지루할 틈 없는 사용자 경험을 제공합니다.

---

## 🛠 기술 스택 (Tech Stack)

이 프로젝트는 **AWS EC2** 환경 위의 **LAMP Stack** 기반으로 구축되었습니다.

### Infrastructure & Backend
*   **Server**: AWS EC2 (Linux)
*   **Web Server**: Apache HTTP Server
*   **Language**: PHP 8.x
*   **Database**: MySQL (MariaDB)

### Frontend
*   **Core**: HTML5, JavaScript (Vanilla ES6+)
*   **Styling**: Tailwind CSS (CDN 방식, No Build Tool)
*   **Libraries**: SheetJS (xlsx) for Excel export, FontAwesome for icons

### AI & API
*   **Vision AI**: Google Gemini API (`gemini-2.0-flash`)
*   **Data Source**: 
    *   네이버 책 검색 API (한국 책 우선)
    *   Google Books API (외국 책/폴백)

---

## 📂 프로젝트 구조 (Structure)

```
/opt/bitnami/apache/htdocs/BOOKSCAN/
├── index.html              # 메인 UI (싱글 페이지 애플리케이션)
├── api_vision.php          # 핵심 로직: 이미지 업로드 -> Gemini 분석 -> 1차 DB 저장
├── api_retry_enrich.php    # 보정 로직: 누락된 정보 재검색 및 업데이트
├── api_books.php           # 조회 로직: DB에 저장된 도서 목록 조회 (JSON)
├── api_reset.php           # 초기화 로직: 모든 데이터 삭제
├── config.php              # 환경 변수 및 설정 관리
├── common.php              # 공통 함수 모듈 (API 요청, 로깅 등)
├── db_connect.php          # 데이터베이스 연결 설정
├── init_db.sql             # 데이터베이스 스키마
├── .env                    # 환경 변수 (서버에만 존재)
└── uploads/                # 업로드된 책장 이미지 저장소
```

## 🚀 설치 및 실행 (Setup)

### 1. 환경 설정
PHP 8.x와 MySQL이 설치된 웹 서버(Apache/Nginx)가 필요합니다.

### 2. 데이터베이스 설정
```bash
mysql -u root -p < init_db.sql
```

### 3. 환경 변수 설정
서버에 `.env` 파일을 생성합니다:
```bash
cp .env.example .env
nano .env  # 또는 원하는 에디터로 편집
```

`.env.example` 파일을 참고하여 실제 값으로 수정하세요.

### 4. API 키 발급
- **Google Gemini API**: [Google AI Studio](https://makersuite.google.com/app/apikey)
  - 필요 권한: Generative Language API (Gemini)
- **네이버 책 검색 API**: [네이버 개발자 센터](https://developers.naver.com)
  - 애플리케이션 등록 → "검색" API 선택
  - Client ID, Client Secret 발급

### 5. 권한 설정
```bash
chmod 755 uploads/
chmod 666 app.log debug_log.txt
```

### 6. 배포
```bash
./deploy.sh
```

---

## 📝 개발자 노트

### 성능 최적화
- 대용량 이미지 처리 시 서버 타임아웃을 방지하기 위해 로직을 최적화
- 클라이언트 측에서 비동기(`fetch`) 처리로 UX 개선
- 네이버 API 우선 검색으로 한국 책 검색 속도 향상

### 보안
- 업로드된 이미지는 유니크한 파일명으로 저장
- SQL Injection 방지를 위해 PDO Prepared Statement 사용
- 파일 업로드 검증 (타입, 크기, MIME 타입)
- 환경 변수로 민감한 정보 관리

### 주요 기능
- **중복 방지**: ISBN 또는 제목+저자로 중복 저장 방지
- **에러 처리**: 상세한 에러 로깅 및 사용자 친화적 메시지
- **트랜잭션**: 데이터 일관성 보장
- **로깅 시스템**: 구조화된 로그 레벨 관리

### 프로젝트 구조
- **모듈화**: 공통 함수를 `common.php`로 분리
- **설정 관리**: `config.php`로 중앙화된 설정 관리
- **배포 자동화**: `deploy.sh` 스크립트로 간편한 배포

---

## 📄 관련 문서

- [CHANGELOG.md](CHANGELOG.md) - 변경 이력
- [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) - 배포 가이드
- [SETUP_GUIDE.md](SETUP_GUIDE.md) - 설정 가이드

---

**개발자**: [Jinho Jung](mailto:jvisualschool@gmail.com)  
**라이선스**: MIT  
**버전**: 2.0
