# BOOKSCAN 북스캔 - 서버 정보 및 배포 가이드

## 🔐 보안 주의사항

**중요**: `server_config.json` 파일에는 민감한 서버 접속 정보가 포함되어 있습니다.
- 이 파일을 GitHub 등 공개 저장소에 **절대 업로드하지 마세요**
- `.gitignore`에 `server_config.json`이 포함되어 있는지 확인하세요

## 📁 생성된 파일

### 1. `server_config.json`
서버 접속 정보를 저장하는 설정 파일입니다.

```json
{
    "project_name": "jvibeschool_org",
    "domain": "https://jvibeschool.org",
    "server_ip": "15.164.161.165",
    "ssh_user": "bitnami",
    "ssh_key_path": "~/.ssh/jvibeschool_org.pem",
    "remote_web_root": "/opt/bitnami/apache/htdocs/",
    "remote_project_path": "/opt/bitnami/apache/htdocs/BOOKSCAN/",
    "mysql_root_password": "your_mysql_password_here"
}
```

### 2. `deploy.sh`
자동 배포 스크립트입니다.

## 🚀 배포 방법

### 방법 1: 자동 배포 스크립트 사용 (권장)

```bash
./deploy.sh
```

이 스크립트는:
- ✅ `server_config.json`에서 서버 정보를 자동으로 읽음
- ✅ 배포할 파일 목록을 확인
- ✅ 사용자에게 확인 요청
- ✅ 모든 파일을 서버에 업로드
- ✅ 진행 상황을 시각적으로 표시

### 방법 2: 수동 배포 (개별 파일)

특정 파일만 업데이트하려면:

```bash
scp -i ~/.ssh/jvibeschool_org.pem index.html bitnami@15.164.161.165:/opt/bitnami/apache/htdocs/BOOKSCAN/
```

### 방법 3: 수동 배포 (모든 파일)

```bash
scp -i ~/.ssh/jvibeschool_org.pem *.php *.html bitnami@15.164.161.165:/opt/bitnami/apache/htdocs/BOOKSCAN/
```

## 🔧 서버 접속

### SSH 접속
```bash
ssh -i ~/.ssh/jvibeschool_org.pem bitnami@15.164.161.165
```

### 서버에서 파일 확인
```bash
ssh -i ~/.ssh/jvibeschool_org.pem bitnami@15.164.161.165 "ls -la /opt/bitnami/apache/htdocs/BOOKSCAN/"
```

### 로그 확인
```bash
ssh -i ~/.ssh/jvibeschool_org.pem bitnami@15.164.161.165 "tail -f /opt/bitnami/apache/htdocs/BOOKSCAN/debug_log.txt"
```

## 📊 데이터베이스 접속

### MySQL 접속
```bash
ssh -i ~/.ssh/jvibeschool_org.pem bitnami@15.164.161.165
mysql -u root -p
# 비밀번호: XvHxGox84PU/
```

### 데이터베이스 초기화
```bash
scp -i ~/.ssh/jvibeschool_org.pem init_db.sql bitnami@15.164.161.165:~/
ssh -i ~/.ssh/jvibeschool_org.pem bitnami@15.164.161.165
mysql -u root -p < init_db.sql
```

## 🌐 배포 확인

배포 후 다음 URL에서 확인:
- **메인 페이지**: https://jvibeschool.org/BOOKSCAN/
- **API 테스트**: https://jvibeschool.org/BOOKSCAN/test_api.php

## 📝 배포 파일 목록

- `index.html` - 메인 페이지
- `api_books.php` - 책 목록 API
- `api_reset.php` - 데이터 초기화 API
- `api_retry_enrich.php` - 재시도 API
- `api_vision.php` - Vision API
- `db_connect.php` - 데이터베이스 연결
- `test_api.php` - API 테스트
- `test_books_api.php` - 책 API 테스트

## 🛠️ 문제 해결

### jq가 설치되어 있지 않은 경우
```bash
brew install jq
```

### SSH 키 권한 오류
```bash
chmod 600 ~/.ssh/jvibeschool_org.pem
```

### 배포 스크립트 실행 권한 오류
```bash
chmod +x deploy.sh
```

## 💡 팁

1. **빠른 배포**: 파일 수정 후 `./deploy.sh` 한 번으로 모든 파일 배포
2. **선택적 배포**: 특정 파일만 수정한 경우 `scp` 명령어로 개별 업로드
3. **로그 모니터링**: 문제 발생 시 `debug_log.txt` 확인
4. **백업**: 중요한 변경 전 서버 파일 백업 권장

## 🔒 보안 체크리스트

- [ ] `.gitignore`에 `server_config.json` 추가됨
- [ ] SSH 키 파일 권한이 600으로 설정됨
- [ ] 데이터베이스 비밀번호가 코드에 하드코딩되지 않음
- [ ] API 키가 환경 변수로 관리됨
