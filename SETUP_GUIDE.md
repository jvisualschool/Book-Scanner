# BOOKSCAN 설정 가이드

## 🚀 초기 설정

### 1. 환경 변수 설정

`.env` 파일을 생성하고 다음 내용을 입력하세요:

```bash
# .env.example 파일을 참고하여 .env 파일 생성
cp .env.example .env
```

`.env` 파일 내용:

```env
# 데이터베이스 설정
DB_HOST=localhost
DB_NAME=book_scanner
DB_USER=root
DB_PASSWORD=your_database_password_here

# Google Gemini API 키
GEMINI_API_KEY=your_gemini_api_key_here

# 로깅 설정
LOG_ENABLED=true
LOG_LEVEL=INFO

# 환경 설정
APP_ENV=production
```

**중요**: 
- `.env` 파일은 절대 Git에 커밋하지 마세요 (이미 `.gitignore`에 포함됨)
- 실제 서버에서는 `.env` 파일을 직접 생성해야 합니다

### 2. 데이터베이스 초기화

```bash
mysql -u root -p < init_db.sql
```

또는 서버에서:

```bash
ssh -i ~/.ssh/jvibeschool_org.pem bitnami@15.164.161.165
mysql -u root -p < /opt/bitnami/apache/htdocs/BOOKSCAN/init_db.sql
```

### 3. 디렉토리 권한 설정

```bash
# 업로드 디렉토리 생성 및 권한 설정
mkdir -p uploads
chmod 755 uploads

# 로그 디렉토리 권한
chmod 644 app.log 2>/dev/null || touch app.log && chmod 644 app.log
chmod 644 debug_log.txt 2>/dev/null || touch debug_log.txt && chmod 644 debug_log.txt
```

### 4. 서버 설정 확인

서버에서 `.env` 파일이 올바르게 설정되었는지 확인:

```bash
ssh -i ~/.ssh/jvibeschool_org.pem bitnami@15.164.161.165
cd /opt/bitnami/apache/htdocs/BOOKSCAN/
cat .env
```

## 📝 주요 변경 사항

### 보안 개선
- ✅ API 키와 비밀번호를 환경 변수로 이동
- ✅ 파일 업로드 검증 추가 (타입, 크기, MIME)
- ✅ 디렉토리 권한 수정 (777 → 755)
- ✅ 에러 메시지 보안 강화 (프로덕션 환경)

### 코드 개선
- ✅ 공통 함수 모듈화 (`common.php`)
- ✅ 트랜잭션 처리 추가
- ✅ 로깅 시스템 구현
- ✅ 데이터베이스 스키마 업데이트

### 배포 개선
- ✅ 테스트 파일 프로덕션 배포 제외
- ✅ 설정 파일 배포 추가 (`config.php`, `common.php`)

## 🔧 문제 해결

### 환경 변수가 로드되지 않는 경우

1. `.env` 파일이 프로젝트 루트에 있는지 확인
2. `config.php`가 올바르게 로드되는지 확인
3. PHP 오류 로그 확인: `tail -f /var/log/apache2/error.log`

### 데이터베이스 연결 오류

1. `.env` 파일의 DB 설정 확인
2. MySQL 서비스 실행 확인: `sudo systemctl status mysql`
3. 데이터베이스 존재 확인: `mysql -u root -p -e "SHOW DATABASES;"`

### 파일 업로드 오류

1. `uploads/` 디렉토리 권한 확인: `ls -la uploads/`
2. PHP `upload_max_filesize` 설정 확인
3. 디스크 공간 확인: `df -h`

### 로그 확인

```bash
# 애플리케이션 로그
tail -f app.log

# 디버그 로그
tail -f debug_log.txt

# Apache 에러 로그
tail -f /var/log/apache2/error.log
```

## 📦 배포 후 확인 사항

1. ✅ `.env` 파일이 서버에 존재하는지 확인
2. ✅ `config.php`와 `common.php`가 배포되었는지 확인
3. ✅ 데이터베이스 스키마가 최신인지 확인
4. ✅ `uploads/` 디렉토리 권한이 올바른지 확인
5. ✅ 로그 파일이 생성되는지 확인

## 🔐 보안 체크리스트

- [ ] `.env` 파일이 `.gitignore`에 포함되어 있음
- [ ] `.env` 파일 권한이 600으로 설정됨
- [ ] `uploads/` 디렉토리 권한이 755로 설정됨
- [ ] 로그 파일이 웹에서 접근 불가능한지 확인
- [ ] API 키가 환경 변수로 관리됨
- [ ] 데이터베이스 비밀번호가 환경 변수로 관리됨

