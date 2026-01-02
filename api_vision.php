<?php
/**
 * 이미지 업로드 및 AI 분석 API
 * 책장 사진을 업로드하면 Gemini API로 책 정보를 추출합니다.
 */

header('Content-Type: application/json');
require_once 'common.php';
require_once 'db_connect.php';

// API 키 확인
if (empty(GEMINI_API_KEY)) {
    sendErrorResponse('API 키가 설정되지 않았습니다.', 500);
}

/**
 * Gemini API 호출
 */
function callGeminiAPI($base64Image, $apiKey) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $apiKey;
    
    $data = [
        "contents" => [
            [
                "parts" => [
                    [
                        "text" => "You are a book spine recognition expert. Analyze the image and extract book information. Return ONLY a valid JSON array of objects. Each object must have keys: 'title', 'author', 'publisher'. If info is missing, use empty string. Do NOT use markdown code blocks (```json), just return the raw JSON string."
                    ],
                    [
                        "inline_data" => [
                            "mime_type" => "image/jpeg",
                            "data" => $base64Image
                        ]
                    ]
                ]
            ]
        ],
        "generationConfig" => [
            "response_mime_type" => "application/json"
        ]
    ];

    logMessage('DEBUG', "Gemini API 요청 시작", ['url' => $url]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        logMessage('ERROR', "Gemini API cURL 오류", ['error' => $error]);
        throw new Exception('API 요청 실패: ' . $error);
    }
    
    curl_close($ch);
    
    logMessage('DEBUG', "Gemini API 응답 수신", ['http_code' => $httpCode]);
    
    return json_decode($response, true);
}

try {
    // HTTP 메서드 검증
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendErrorResponse('POST 메서드만 허용됩니다.', 405);
    }

    // 파일 업로드 확인
    if (!isset($_FILES['image'])) {
        sendErrorResponse('이미지 파일이 업로드되지 않았습니다.', 400);
    }

    $file = $_FILES['image'];
    
    // 파일 검증
    $validationErrors = validateUploadedFile($file);
    if (!empty($validationErrors)) {
        sendErrorResponse(implode(' ', $validationErrors), 400, ['file' => $file['name']]);
    }

    // 이미지 데이터 읽기 및 Base64 인코딩
    $imageData = file_get_contents($file['tmp_name']);
    if ($imageData === false) {
        sendErrorResponse('이미지 파일을 읽을 수 없습니다.', 400);
    }
    
    $base64Image = base64_encode($imageData);

    // Gemini API 호출
    logMessage('INFO', "이미지 분석 시작", ['filename' => $file['name'], 'size' => $file['size']]);
    $apiResponse = callGeminiAPI($base64Image, GEMINI_API_KEY);
    
    // API 응답 검증
    if (isset($apiResponse['error'])) {
        logMessage('ERROR', "Gemini API 오류", ['error' => $apiResponse['error']]);
        sendErrorResponse('API 오류: ' . ($apiResponse['error']['message'] ?? '알 수 없는 오류'), 500);
    }

    if (!isset($apiResponse['candidates'][0]['content']['parts'][0]['text'])) {
        logMessage('ERROR', "Gemini API 응답 구조 오류", ['response' => $apiResponse]);
        sendErrorResponse('API 응답 형식이 올바르지 않습니다.', 500);
    }

    // JSON 파싱
    $rawText = $apiResponse['candidates'][0]['content']['parts'][0]['text'];
    $rawText = preg_replace('/^```json\s*|\s*```$/', '', trim($rawText));
    
    $books = json_decode($rawText, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        logMessage('ERROR', "JSON 파싱 오류", [
            'error' => json_last_error_msg(),
            'raw_text' => substr($rawText, 0, 200)
        ]);
        sendErrorResponse('책 정보 파싱에 실패했습니다.', 500);
    }
    
    // 배열 검증
    if (!is_array($books)) {
        $books = is_array($books) ? array_values($books)[0] : [];
        if (!is_array($books)) {
            logMessage('ERROR', "책 목록 추출 실패", ['raw_text' => substr($rawText, 0, 200)]);
            sendErrorResponse('책 목록을 추출할 수 없습니다.', 500);
        }
    }

    // 업로드 디렉토리 준비
    if (!file_exists(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, UPLOAD_DIR_PERMISSIONS, true)) {
            logMessage('ERROR', "업로드 디렉토리 생성 실패", ['dir' => UPLOAD_DIR]);
            sendErrorResponse('서버 설정 오류가 발생했습니다.', 500);
        }
    }

    // 이미지 파일 저장
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $imagePath = UPLOAD_DIR . '/' . uniqid() . '.' . $extension;
    
    if (!move_uploaded_file($file['tmp_name'], $imagePath)) {
        logMessage('ERROR', "이미지 파일 저장 실패", ['path' => $imagePath]);
        sendErrorResponse('이미지 파일 저장에 실패했습니다.', 500);
    }
    
    // 상대 경로로 저장 (DB에 저장할 때)
    $relativeImagePath = 'uploads/' . basename($imagePath);

    // 트랜잭션 시작
    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare("INSERT INTO inventory (title, author, publisher, image_url, description, isbn, published_date, official_cover_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $insertedWebBooks = [];
        $successCount = 0;

        foreach ($books as $book) {
            $title = trim($book['title'] ?? 'Unknown');
            $author = trim($book['author'] ?? '');
            $publisher = trim($book['publisher'] ?? '');
            
            // Google Books API로 상세 정보 조회
            $details = fetchBookDetails($title, $author);
            
            // 중복 체크: ISBN이 있으면 ISBN으로, 없으면 제목+저자로
            $existingBook = null;
            if (!empty($details['isbn'])) {
                $checkStmt = $pdo->prepare("SELECT id FROM inventory WHERE isbn = ? AND isbn != '' AND isbn IS NOT NULL LIMIT 1");
                $checkStmt->execute([$details['isbn']]);
                $existingBook = $checkStmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // ISBN으로 찾지 못했으면 제목+저자로 체크
            if (!$existingBook) {
                $checkStmt = $pdo->prepare("SELECT id FROM inventory WHERE title = ? AND author = ? LIMIT 1");
                $checkStmt->execute([$title, $author]);
                $existingBook = $checkStmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // 중복이면 건너뛰기
            if ($existingBook) {
                logMessage('DEBUG', "중복 책 건너뛰기", [
                    'title' => $title,
                    'author' => $author,
                    'isbn' => $details['isbn'],
                    'existing_id' => $existingBook['id']
                ]);
                continue; // 중복이면 저장하지 않고 다음 책으로
            }
            
            try {
                $stmt->execute([
                    $title, 
                    $author, 
                    $publisher, 
                    $relativeImagePath,
                    $details['description'],
                    $details['isbn'],
                    $details['publishedDate'],
                    $details['imageLinks']
                ]);
                
                $insertedWebBooks[] = [
                    'id' => $pdo->lastInsertId(),
                    'title' => $title,
                    'author' => $author,
                    'publisher' => $publisher,
                    'description' => $details['description'],
                    'isbn' => $details['isbn'],
                    'published_date' => $details['publishedDate'],
                    'official_cover_url' => $details['imageLinks'],
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $successCount++;
            } catch (PDOException $e) {
                logMessage('WARNING', "책 정보 저장 실패", [
                    'title' => $title,
                    'error' => $e->getMessage()
                ]);
                // 개별 책 저장 실패는 계속 진행
            }
        }

        // 트랜잭션 커밋
        $pdo->commit();
        
        logMessage('INFO', "이미지 분석 완료", [
            'total_books' => count($books),
            'success_count' => $successCount,
            'filename' => $file['name']
        ]);

        sendSuccessResponse([
            'books' => $insertedWebBooks,
            'total_found' => count($books),
            'total_inserted' => $successCount
        ]);

    } catch (Exception $e) {
        // 트랜잭션 롤백
        $pdo->rollBack();
        
        // 저장된 이미지 파일 삭제
        if (file_exists($imagePath)) {
            @unlink($imagePath);
        }
        
        logMessage('ERROR', "책 정보 저장 실패", ['error' => $e->getMessage()]);
        throw $e;
    }

} catch (Exception $e) {
    logMessage('ERROR', "예상치 못한 오류 발생", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    sendErrorResponse($e->getMessage(), 500);
}
?>
