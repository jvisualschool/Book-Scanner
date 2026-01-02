<?php
/**
 * 도서 목록 조회 API
 */

header('Content-Type: application/json');
require_once 'common.php';
require_once 'db_connect.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->query("SELECT * FROM inventory ORDER BY created_at DESC");
        $books = $stmt->fetchAll();
        
        logMessage('INFO', "도서 목록 조회", ['count' => count($books)]);
        
        echo json_encode($books);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!isset($_GET['id'])) {
            sendErrorResponse('책 ID가 필요합니다.', 400);
        }
        
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if ($id === false) {
            sendErrorResponse('유효하지 않은 책 ID입니다.', 400);
        }
        
        $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            logMessage('INFO', "도서 삭제", ['id' => $id]);
            sendSuccessResponse(['message' => '도서가 삭제되었습니다.']);
        } else {
            sendErrorResponse('해당 도서를 찾을 수 없습니다.', 404);
        }
        
    } else {
        sendErrorResponse('GET 또는 DELETE 메서드만 허용됩니다.', 405);
    }
    
} catch (Exception $e) {
    logMessage('ERROR', "도서 목록 조회 실패", ['error' => $e->getMessage()]);
    sendErrorResponse($e->getMessage(), 500);
}
?>
