# 변경 이력

## [2025-01-02] - 보안 및 코드 품질 개선

### Critical 개선 사항

#### 1. 보안 강화
- ✅ **환경 변수 관리**: API 키와 데이터베이스 비밀번호를 환경 변수로 이동
  - `config.php`: 환경 변수 로드 및 설정 관리
  - `.env.example`: 환경 변수 템플릿 추가
  - 모든 민감한 정보를 코드에서 제거

- ✅ **파일 업로드 검증**: 보안 취약점 해결
  - 파일 타입 검증 (JPEG, PNG만 허용)
  - 파일 크기 제한 (10MB)
  - MIME 타입 검증
  - 실제 이미지 파일 검증 (`getimagesize()`)
  - 파일 확장자 검증

- ✅ **디렉토리 권한 수정**: 777 → 755로 변경
  - 보안 강화: 모든 사용자 쓰기 권한 제거

#### 2. 데이터베이스 스키마 업데이트
- ✅ `init_db.sql`에 누락된 컬럼 추가:
  - `description` (TEXT) - 줄거리
  - `isbn` (VARCHAR(20)) - ISBN
  - `published_date` (VARCHAR(50)) - 출판일
  - `official_cover_url` (TEXT) - 공식 표지 이미지 URL
  - 인덱스 추가: `idx_isbn`, `idx_created_at`

### High 우선순위 개선 사항

#### 3. 코드 품질 개선
- ✅ **공통 함수 모듈화**: `common.php` 생성
  - `makeBookRequest()`: Google Books API 요청 함수 통합
  - `fetchBookDetails()`: 도서 상세 정보 조회 함수 통합
  - `validateUploadedFile()`: 파일 업로드 검증 함수
  - `logMessage()`: 구조화된 로깅 시스템
  - `sendErrorResponse()` / `sendSuccessResponse()`: 일관된 API 응답

- ✅ **트랜잭션 처리**: 데이터 일관성 보장
  - `api_vision.php`: 여러 책 삽입 시 트랜잭션 사용
  - 실패 시 자동 롤백 및 이미지 파일 정리

- ✅ **에러 처리 개선**:
  - 구조화된 에러 응답
  - 프로덕션 환경에서 상세 에러 메시지 숨김
  - 적절한 HTTP 상태 코드 사용

- ✅ **로깅 시스템 구현**:
  - 로그 레벨 관리 (DEBUG, INFO, WARNING, ERROR)
  - 구조화된 로그 메시지
  - 별도 디버그 로그 파일

#### 4. 배포 개선
- ✅ **테스트 파일 제외**: `deploy.sh` 수정
  - `test_api.php`, `test_books_api.php` 프로덕션 배포 제외
  - `config.php`, `common.php`, `init_db.sql` 배포 추가

### 변경된 파일

#### 새로 생성된 파일
- `config.php` - 환경 변수 및 설정 관리
- `common.php` - 공통 함수 모듈
- `SETUP_GUIDE.md` - 설정 가이드
- `CHANGELOG.md` - 변경 이력

#### 수정된 파일
- `init_db.sql` - 데이터베이스 스키마 업데이트
- `db_connect.php` - 환경 변수 사용으로 변경
- `api_vision.php` - 파일 검증, 트랜잭션, 로깅 추가
- `api_retry_enrich.php` - 공통 함수 사용, 로깅 추가
- `api_books.php` - 공통 함수 사용, 에러 처리 개선
- `api_reset.php` - 트랜잭션, 로깅 추가
- `deploy.sh` - 테스트 파일 제외, 설정 파일 추가

### 마이그레이션 가이드

#### 서버 설정 필요 사항

1. **환경 변수 설정**:
   ```bash
   # 서버에서 .env 파일 생성
   cd /opt/bitnami/apache/htdocs/BOOKSCAN/
   nano .env
   ```
   
   다음 내용 입력:
   ```env
   DB_HOST=localhost
   DB_NAME=book_scanner
   DB_USER=root
   DB_PASSWORD=기존_비밀번호
   GEMINI_API_KEY=기존_API_키
   LOG_ENABLED=true
   LOG_LEVEL=INFO
   APP_ENV=production
   ```

2. **데이터베이스 스키마 업데이트**:
   ```bash
   # 기존 데이터베이스에 컬럼 추가
   mysql -u root -p book_scanner
   ```
   
   ```sql
   ALTER TABLE inventory 
   ADD COLUMN description TEXT,
   ADD COLUMN isbn VARCHAR(20),
   ADD COLUMN published_date VARCHAR(50),
   ADD COLUMN official_cover_url TEXT;
   
   CREATE INDEX idx_isbn ON inventory(isbn);
   CREATE INDEX idx_created_at ON inventory(created_at);
   ```

3. **디렉토리 권한 수정**:
   ```bash
   chmod 755 uploads/
   ```

4. **배포**:
   ```bash
   ./deploy.sh
   ```

### 호환성

- ✅ 기존 데이터베이스와 호환 (ALTER TABLE로 컬럼 추가)
- ✅ 기존 API 엔드포인트 유지
- ✅ 기존 프론트엔드 코드와 호환

### 주의사항

⚠️ **중요**: 배포 전 반드시 `.env` 파일을 서버에 생성해야 합니다!
- `.env` 파일 없이는 애플리케이션이 동작하지 않습니다
- 기존 하드코딩된 값들을 `.env` 파일로 이동해야 합니다

