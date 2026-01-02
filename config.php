<?php
/**
 * 설정 파일
 * 환경 변수 또는 기본값을 사용하여 설정을 로드합니다.
 */

// .env 파일 로드 (존재하는 경우)
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // 주석 건너뛰기
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// 환경 변수에서 읽거나 기본값 사용
function getEnvVar($key, $default = '') {
    $value = getenv($key);
    if ($value === false && isset($_ENV[$key])) {
        $value = $_ENV[$key];
    }
    return $value !== false ? $value : $default;
}

// 데이터베이스 설정
define('DB_HOST', getEnvVar('DB_HOST', 'localhost'));
define('DB_NAME', getEnvVar('DB_NAME', 'book_scanner'));
define('DB_USER', getEnvVar('DB_USER', 'root'));
define('DB_PASS', getEnvVar('DB_PASSWORD', ''));
define('DB_CHARSET', getEnvVar('DB_CHARSET', 'utf8mb4'));

// API 키 설정
define('GEMINI_API_KEY', getEnvVar('GEMINI_API_KEY', ''));

// 네이버 API 설정 (https://developers.naver.com 에서 발급)
define('NAVER_CLIENT_ID', getEnvVar('NAVER_CLIENT_ID', ''));
define('NAVER_CLIENT_SECRET', getEnvVar('NAVER_CLIENT_SECRET', ''));

// 파일 업로드 설정
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_ALLOWED_TYPES', ['image/jpeg', 'image/jpg', 'image/png']);
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('UPLOAD_DIR_PERMISSIONS', 0755);

// 로깅 설정
define('LOG_ENABLED', getEnvVar('LOG_ENABLED', 'true') === 'true');
define('LOG_FILE', __DIR__ . '/app.log');
define('DEBUG_LOG_FILE', __DIR__ . '/debug_log.txt');
define('LOG_LEVEL', getEnvVar('LOG_LEVEL', 'INFO')); // DEBUG, INFO, WARNING, ERROR

// 환경 확인
if (empty(GEMINI_API_KEY)) {
    error_log('경고: GEMINI_API_KEY가 설정되지 않았습니다.');
}

if (empty(DB_PASS)) {
    error_log('경고: DB_PASSWORD가 설정되지 않았습니다.');
}
// PHP 닫는 태그 생략 (권장사항: 불필요한 출력 방지)