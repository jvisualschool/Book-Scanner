<?php
/**
 * 데이터 초기화 API
 * 모든 도서 데이터와 업로드된 이미지를 삭제합니다.
 */

header('Content-Type: application/json');

// 에러 발생 시 JSON으로 응답하도록 설정
set_error_handler(function($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
});

try {
    require_once 'config.php';
    require_once 'common.php';
    require_once 'db_connect.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '서버 설정 오류: ' . $e->getMessage()]);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendErrorResponse('POST 메서드만 허용됩니다.', 405);
    }

    // UPLOAD_DIR 상수 확인
    if (!defined('UPLOAD_DIR')) {
        if (function_exists('logMessage')) {
            logMessage('ERROR', "UPLOAD_DIR 상수가 정의되지 않았습니다.");
        }
        sendErrorResponse('서버 설정 오류가 발생했습니다.', 500);
    }

    // 1. DB 초기화 (DELETE FROM 사용 - TRUNCATE는 DDL이라 트랜잭션 불가)
    $stmt = $pdo->prepare("DELETE FROM inventory");
    $stmt->execute();
    
    // AUTO_INCREMENT 리셋
    $pdo->exec("ALTER TABLE inventory AUTO_INCREMENT = 1");
    
    logMessage('INFO', "데이터베이스 초기화 완료");

    // 2. 업로드된 이미지 파일 삭제
    $uploadDir = UPLOAD_DIR;
    $deletedCount = 0;
    
    if (!is_dir($uploadDir)) {
        logMessage('WARNING', "업로드 디렉토리가 존재하지 않습니다", ['dir' => $uploadDir]);
    } else {
        $files = glob($uploadDir . '/*');
        if ($files === false) {
            logMessage('WARNING', "파일 목록을 가져올 수 없습니다", ['dir' => $uploadDir]);
        } else {
            foreach ($files as $file) {
                if (is_file($file)) {
                    if (@unlink($file)) {
                        $deletedCount++;
                    } else {
                        logMessage('WARNING', "파일 삭제 실패", ['file' => $file, 'error' => error_get_last()]);
                    }
                }
            }
            
            logMessage('INFO', "업로드 파일 삭제 완료", ['count' => $deletedCount]);
        }
    }
    
    sendSuccessResponse([
        'message' => '모든 데이터가 초기화되었습니다.',
        'deleted_files' => $deletedCount ?? 0
    ]);

} catch (Exception $e) {
    // 상세 에러 로깅
    $errorDetails = [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    logMessage('ERROR', "데이터 초기화 실패", $errorDetails);
    
    // 클라이언트에 상세 에러 반환
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_type' => get_class($e),
        'error_code' => $e->getCode(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
// PHP 닫는 태그 생략
