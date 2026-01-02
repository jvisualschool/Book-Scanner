# GitHub 배포 가이드

## 📋 배포 파일 목록

### ✅ 포함할 파일 (서비스 필수)

#### 핵심 애플리케이션 파일
- `index.html` - 메인 UI
- `api_vision.php` - 이미지 분석 API
- `api_books.php` - 도서 목록 조회 API
- `api_retry_enrich.php` - 도서 정보 재검색 API
- `api_reset.php` - 데이터 초기화 API
- `common.php` - 공통 함수 모듈
- `config.php` - 설정 관리
- `db_connect.php` - 데이터베이스 연결

#### 데이터베이스
- `init_db.sql` - 초기 데이터베이스 스키마
- `migrate_db.sql` - 마이그레이션 스크립트

#### 설정 파일
- `.env.example` - 환경 변수 템플릿
- `.gitignore` - Git 제외 목록
- `.gitattributes` - Git 속성 설정

#### 문서
- `README.md` - 프로젝트 설명서
- `LICENSE` - MIT 라이선스
- `CHANGELOG.md` - 변경 이력
- `SETUP_GUIDE.md` - 설정 가이드
- `DEPLOYMENT_GUIDE.md` - 배포 가이드 (민감 정보 제거)

#### 배포 스크립트
- `deploy.sh` - 배포 스크립트

#### 리소스
- `splash.jpg` - 스플래시 이미지 (16:9)

### ❌ 제외할 파일 (.gitignore에 포함)

#### 민감한 정보
- `.env` - 환경 변수 (실제 API 키, 비밀번호)
- `server_config.json` - 서버 설정 (실제 비밀번호)

#### 로그 파일
- `app.log`
- `debug_log.txt`
- `*.log`

#### 업로드 파일
- `uploads/` - 사용자가 업로드한 이미지

#### 테스트 파일
- `test_api.php` - 하드코딩된 API 키 포함
- `test_books_api.php` - 하드코딩된 API 키 포함
- `test_gemini_api.php` - 테스트 전용

#### 서버 전용 파일
- `deploy_auto.sh` - 자동 배포 스크립트
- `setup_server.sh` - 서버 설정 스크립트 (하드코딩된 API 키 포함)
- `DEPLOYMENT_COMPLETE.md` - 배포 완료 문서

#### 시스템 파일
- `.DS_Store` - macOS
- `*.tmp`, `*.bak` - 임시/백업 파일

---

## 🔒 보안 체크리스트

### ✅ 완료된 보안 조치

- [x] **환경 변수 분리**: 모든 API 키와 비밀번호를 `.env` 파일로 이동
- [x] **하드코딩 제거**: 프로덕션 코드에서 민감한 정보 제거
- [x] **.gitignore 설정**: 민감한 파일 자동 제외
- [x] **템플릿 파일 제공**: `.env.example`로 설정 가이드 제공
- [x] **SQL Injection 방지**: PDO Prepared Statement 사용
- [x] **파일 업로드 검증**: 타입, 크기, MIME 타입 검증
- [x] **에러 메시지**: 프로덕션에서 상세 에러 숨김

### ⚠️ 주의사항

1. **테스트 파일**: `test_*.php` 파일은 하드코딩된 API 키를 포함하므로 배포하지 않음
2. **문서 파일**: `DEPLOYMENT_GUIDE.md`에 실제 비밀번호가 있을 수 있으므로 확인 필요
3. **서버 설정**: `server_config.json`은 실제 서버 정보를 포함하므로 배포하지 않음

---

## 🚀 배포 절차

### 1. 최종 보안 검사
```bash
# 하드코딩된 API 키 검색
grep -r "AIzaSy" . --exclude-dir=.git --exclude="*.md"
grep -r "XvHxGox" . --exclude-dir=.git --exclude="*.md"

# 민감한 정보 검색
grep -r "jvibeschool_org.pem" . --exclude-dir=.git
grep -r "15.164.161.165" . --exclude-dir=.git
```

### 2. Git 초기화 및 커밋
```bash
# Git 저장소 초기화
git init

# 파일 추가
git add .

# 첫 커밋
git commit -m "Initial commit: 책장 스캐너 v2.0

- AI 기반 책장 스캔 기능
- 네이버 책 검색 API 통합
- 중복 방지 로직
- 한|영 모드 지원
- 스플래시 모달 추가"
```

### 3. GitHub 저장소 연결
```bash
# 원격 저장소 추가
git remote add origin https://github.com/your-username/book-scanner.git

# 브랜치 이름 변경
git branch -M main

# 푸시
git push -u origin main
```

### 4. 배포 후 확인
- [ ] README.md가 올바르게 표시되는지 확인
- [ ] LICENSE 파일이 있는지 확인
- [ ] .env.example이 포함되어 있는지 확인
- [ ] 민감한 정보가 코드에 없는지 확인

---

## 📝 배포 전 체크리스트

- [ ] 모든 하드코딩된 API 키 제거 확인
- [ ] 모든 하드코딩된 비밀번호 제거 확인
- [ ] .gitignore에 모든 민감한 파일 포함 확인
- [ ] .env.example 파일이 최신 상태인지 확인
- [ ] README.md가 최신 정보를 포함하는지 확인
- [ ] 테스트 파일이 제외되었는지 확인
- [ ] 서버 전용 파일이 제외되었는지 확인
- [ ] LICENSE 파일이 포함되었는지 확인

---

## 🔍 보안 검사 명령어

```bash
# 1. 하드코딩된 API 키 검색
grep -r "AIzaSy" . --exclude-dir=.git --exclude="*.md" --exclude="*.log"

# 2. 하드코딩된 비밀번호 검색
grep -r "XvHxGox" . --exclude-dir=.git --exclude="*.md" --exclude="*.log"

# 3. 서버 IP 주소 검색
grep -r "15.164.161.165" . --exclude-dir=.git --exclude="*.md"

# 4. SSH 키 경로 검색
grep -r "jvibeschool_org.pem" . --exclude-dir=.git --exclude="*.md"

# 5. .env 파일이 포함되지 않았는지 확인
git ls-files | grep -E "\.env$|server_config\.json"

# 6. 테스트 파일이 포함되지 않았는지 확인
git ls-files | grep "test_"
```

---

## 📦 최종 배포 파일 목록

```
✅ index.html
✅ api_*.php (api_books.php, api_reset.php, api_retry_enrich.php, api_vision.php)
✅ common.php
✅ config.php
✅ db_connect.php
✅ init_db.sql
✅ migrate_db.sql
✅ .env.example
✅ .gitignore
✅ .gitattributes
✅ README.md
✅ LICENSE
✅ CHANGELOG.md
✅ SETUP_GUIDE.md
✅ DEPLOYMENT_GUIDE.md (민감 정보 제거 확인)
✅ deploy.sh
✅ splash.jpg
```

---

**마지막 업데이트**: 2026-01-02

