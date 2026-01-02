<?php
/**
 * 공통 함수 모듈
 * 여러 파일에서 공유되는 함수들을 모아둡니다.
 */

require_once 'config.php';

/**
 * 로깅 함수
 */
function logMessage($level, $message, $context = []) {
    if (!LOG_ENABLED) return;
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = sprintf(
        "[%s] [%s] %s %s\n",
        $timestamp,
        strtoupper($level),
        $message,
        !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
    );
    
    // 로그 레벨 필터링
    $levels = ['DEBUG' => 0, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3];
    $currentLevel = $levels[strtoupper(LOG_LEVEL)] ?? 1;
    $messageLevel = $levels[strtoupper($level)] ?? 1;
    
    if ($messageLevel >= $currentLevel) {
        @file_put_contents(LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    // DEBUG 레벨은 별도 파일에도 기록
    if ($level === 'DEBUG') {
        @file_put_contents(DEBUG_LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Google Books API 요청 헬퍼 함수
 */
function makeBookRequest($url, $retries = 2) {
    for ($i = 0; $i < $retries; $i++) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode === 200 && $response !== false) {
            return json_decode($response, true);
        }
        
        if ($i < $retries - 1) {
            logMessage('WARNING', "Google Books API 요청 실패, 재시도 중...", [
                'url' => $url,
                'http_code' => $httpCode,
                'error' => $error,
                'attempt' => $i + 1
            ]);
            sleep(1);
        } else {
            logMessage('ERROR', "Google Books API 요청 최종 실패", [
                'url' => $url,
                'http_code' => $httpCode,
                'error' => $error
            ]);
        }
    }
    
    return null;
}

/**
 * 네이버 책 검색 API에서 도서 정보 가져오기
 */
function fetchNaverBookDetails($title, $author = '') {
    // 네이버 API 키 확인
    if (empty(NAVER_CLIENT_ID) || empty(NAVER_CLIENT_SECRET)) {
        logMessage('DEBUG', "네이버 API 키가 설정되지 않았습니다.");
        return null;
    }
    
    // 검색어 구성
    $query = $title;
    if (!empty($author)) {
        $query .= ' ' . $author;
    }
    
    $url = "https://openapi.naver.com/v1/search/book.json?query=" . urlencode($query) . "&display=1";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'X-Naver-Client-Id: ' . NAVER_CLIENT_ID,
            'X-Naver-Client-Secret: ' . NAVER_CLIENT_SECRET
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        logMessage('WARNING', "네이버 API 요청 실패", ['http_code' => $httpCode]);
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['items'][0])) {
        $item = $data['items'][0];
        
        // ISBN 추출 (네이버는 isbn 필드에 ISBN10 ISBN13 형태로 제공)
        $isbn = '';
        if (!empty($item['isbn'])) {
            $isbns = explode(' ', $item['isbn']);
            // ISBN13 우선
            foreach ($isbns as $i) {
                if (strlen($i) === 13) {
                    $isbn = $i;
                    break;
                }
            }
            if (empty($isbn) && !empty($isbns[0])) {
                $isbn = $isbns[0];
            }
        }
        
        // 출판일 형식 변환 (YYYYMMDD -> YYYY-MM-DD)
        $pubdate = $item['pubdate'] ?? '';
        if (strlen($pubdate) === 8) {
            $pubdate = substr($pubdate, 0, 4) . '-' . substr($pubdate, 4, 2) . '-' . substr($pubdate, 6, 2);
        }
        
        logMessage('DEBUG', "네이버 API에서 책 정보 찾음", ['title' => $item['title'], 'image' => !empty($item['image'])]);
        
        return [
            'description' => strip_tags($item['description'] ?? ''),
            'publishedDate' => $pubdate,
            'isbn' => $isbn,
            'imageLinks' => $item['image'] ?? ''
        ];
    }
    
    return null;
}

/**
 * 도서 상세 정보 가져오기 (네이버 우선, Google 폴백)
 * 한국 책 검색에 최적화
 */
function fetchBookDetails($title, $author, $apiKey = null) {
    $result = [
        'description' => '',
        'publishedDate' => '',
        'isbn' => '',
        'imageLinks' => ''
    ];
    
    // ========== 1단계: 네이버 API 먼저 시도 (한국 책에 강함) ==========
    $naverResult = fetchNaverBookDetails($title, $author);
    
    if ($naverResult) {
        $result = [
            'description' => $naverResult['description'] ?? '',
            'publishedDate' => $naverResult['publishedDate'] ?? '',
            'isbn' => $naverResult['isbn'] ?? '',
            'imageLinks' => $naverResult['imageLinks'] ?? ''
        ];
        
        // 네이버에서 표지를 찾았으면 바로 반환 (빠른 응답)
        if (!empty($result['imageLinks'])) {
            logMessage('INFO', "네이버 API에서 책 정보 찾음", ['title' => $title, 'has_cover' => true]);
            return $result;
        }
        
        logMessage('DEBUG', "네이버 API에서 표지 없음, Google Books 시도", ['title' => $title]);
    } else {
        logMessage('DEBUG', "네이버 API에서 결과 없음, Google Books 시도", ['title' => $title]);
    }
    
    // ========== 2단계: Google Books API로 폴백 (외국 책/추가 정보) ==========
    // Strategy 1: Specific Search (intitle + inauthor)
    $query = 'intitle:' . urlencode($title);
    if (!empty($author)) {
        $query .= '+inauthor:' . urlencode($author);
    }
    
    $url = "https://www.googleapis.com/books/v1/volumes?q=" . $query . "&maxResults=1&langRestrict=ko";
    $data = makeBookRequest($url);
    
    // Strategy 2: General Search if no items found
    if (!isset($data['items'])) {
        $q = urlencode($title . " " . $author);
        $url = "https://www.googleapis.com/books/v1/volumes?q=" . $q . "&maxResults=1";
        $data = makeBookRequest($url);
    }
    
    if (isset($data['items'][0]['volumeInfo'])) {
        $info = $data['items'][0]['volumeInfo'];
        
        // ISBN 추출
        $isbn = '';
        if (isset($info['industryIdentifiers'])) {
            foreach ($info['industryIdentifiers'] as $id) {
                if ($id['type'] === 'ISBN_13') {
                    $isbn = $id['identifier'];
                    break;
                }
            }
            if (empty($isbn) && isset($info['industryIdentifiers'][0]['identifier'])) {
                $isbn = $info['industryIdentifiers'][0]['identifier'];
            }
        }
        
        // 표지 이미지 URL (HTTPS 강제)
        $coverUrl = $info['imageLinks']['thumbnail'] ?? '';
        if ($coverUrl) {
            $coverUrl = str_replace('http://', 'https://', $coverUrl);
        }
        
        // Google 결과로 빈 필드 채우기 (네이버 결과 우선 유지)
        if (empty($result['imageLinks']) && !empty($coverUrl)) {
            $result['imageLinks'] = $coverUrl;
            logMessage('INFO', "Google Books에서 표지 찾음", ['title' => $title]);
        }
        if (empty($result['isbn']) && !empty($isbn)) {
            $result['isbn'] = $isbn;
        }
        if (empty($result['description']) && !empty($info['description'])) {
            $result['description'] = $info['description'];
        }
        if (empty($result['publishedDate']) && !empty($info['publishedDate'])) {
            $result['publishedDate'] = $info['publishedDate'];
        }
    }
    
    return $result;
}

/**
 * 파일 업로드 검증
 */
function validateUploadedFile($file) {
    $errors = [];
    
    // 파일 존재 확인
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = '파일 업로드에 실패했습니다.';
        return $errors;
    }
    
    // 파일 크기 검증
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        $errors[] = sprintf('파일 크기는 %dMB를 초과할 수 없습니다.', UPLOAD_MAX_SIZE / 1024 / 1024);
    }
    
    // MIME 타입 검증
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, UPLOAD_ALLOWED_TYPES)) {
        $errors[] = '허용되지 않은 파일 형식입니다. (JPEG, PNG만 허용)';
    }
    
    // 실제 이미지 파일인지 확인
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        $errors[] = '유효한 이미지 파일이 아닙니다.';
    }
    
    // 파일 확장자 검증
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png'];
    if (!in_array($extension, $allowedExtensions)) {
        $errors[] = '허용되지 않은 파일 확장자입니다.';
    }
    
    return $errors;
}

/**
 * 안전한 에러 응답 생성
 */
function sendErrorResponse($message, $code = 500, $logContext = []) {
    http_response_code($code);
    logMessage('ERROR', $message, $logContext);
    
    // 프로덕션 환경에서는 상세 에러 메시지 숨김
    $isProduction = getEnvVar('APP_ENV', 'development') === 'production';
    $errorMessage = $isProduction ? '서버 오류가 발생했습니다.' : $message;
    
    echo json_encode(['error' => $errorMessage, 'success' => false]);
    exit;
}

/**
 * 성공 응답 생성
 */
function sendSuccessResponse($data = []) {
    http_response_code(200);
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}
// PHP 닫는 태그 생략 (권장사항: 불필요한 출력 방지)