<?php
/**
 * 누락된 도서 정보 재검색 API
 * ISBN이나 표지 이미지가 없는 책들을 다시 검색하여 정보를 보완합니다.
 */

header('Content-Type: application/json');
require_once 'common.php';
require_once 'db_connect.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendErrorResponse('GET 또는 POST 메서드만 허용됩니다.', 405);
    }

    // 누락된 정보가 있는 책들 조회 (중복 방지를 위해 기존 값도 함께 조회)
    $stmt = $pdo->query("SELECT id, title, author, isbn, official_cover_url, description, published_date FROM inventory WHERE isbn = '' OR official_cover_url = '' OR isbn IS NULL OR official_cover_url IS NULL");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($books)) {
        sendSuccessResponse(['updated_count' => 0, 'message' => '보완할 책이 없습니다.']);
    }

    logMessage('INFO', "도서 정보 재검색 시작", ['count' => count($books)]);
    
    $updatedCount = 0;
    $pdo->beginTransaction();

    try {
        foreach ($books as $book) {
            $id = $book['id'];
            $originalTitle = $book['title'];
            $cleanTitle = $originalTitle;

            // 제목 정제: 괄호, 대괄호, 부제 제거
            $cleanTitle = preg_replace('/\(.*?\)|\[.*?\]/', '', $cleanTitle);
            $cleanTitle = preg_replace('/:.*$/', '', $cleanTitle);
            $cleanTitle = trim($cleanTitle);
            
            // 제목이 비어있으면 건너뛰기
            if (empty($cleanTitle)) {
                logMessage('WARNING', "제목이 비어있어 건너뜀", ['id' => $id]);
                continue;
            }

            // Strategy 1: 정제된 제목으로 검색 (한국어 우선)
            $url = "https://www.googleapis.com/books/v1/volumes?q=intitle:" . urlencode($cleanTitle) . "&maxResults=1&langRestrict=ko";
            $data = makeBookRequest($url);
            
            // Strategy 2: 일반 키워드 검색
            if (!isset($data['items'])) {
                $url = "https://www.googleapis.com/books/v1/volumes?q=" . urlencode($cleanTitle) . "&maxResults=1";
                $data = makeBookRequest($url);
            }

            if (isset($data['items'][0]['volumeInfo'])) {
                $info = $data['items'][0]['volumeInfo'];
                
                // ISBN 추출
                $isbn = '';
                if (isset($info['industryIdentifiers'])) {
                    foreach ($info['industryIdentifiers'] as $idEnt) {
                        if ($idEnt['type'] === 'ISBN_13') {
                            $isbn = $idEnt['identifier'];
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
                
                $description = $info['description'] ?? '';
                $publishedDate = $info['publishedDate'] ?? '';

                // 유용한 정보가 있으면 업데이트 (중복 방지: 기존 값이 있으면 덮어쓰지 않음)
                $updateFields = [];
                $updateValues = [];
                
                // ISBN이 없을 때만 업데이트
                $existingIsbn = $book['isbn'] ?? '';
                if ($isbn && empty($existingIsbn)) {
                    $updateFields[] = 'isbn = ?';
                    $updateValues[] = $isbn;
                }
                
                // 표지 URL이 없을 때만 업데이트
                $existingCover = $book['official_cover_url'] ?? '';
                if ($coverUrl && empty($existingCover)) {
                    $updateFields[] = 'official_cover_url = ?';
                    $updateValues[] = $coverUrl;
                }
                
                // 설명이 없을 때만 업데이트
                $existingDesc = $book['description'] ?? '';
                if ($description && empty($existingDesc)) {
                    $updateFields[] = 'description = ?';
                    $updateValues[] = $description;
                }
                
                // 출판일이 없을 때만 업데이트
                $existingDate = $book['published_date'] ?? '';
                if ($publishedDate && empty($existingDate)) {
                    $updateFields[] = 'published_date = ?';
                    $updateValues[] = $publishedDate;
                }
                
                // 업데이트할 필드가 있으면 실행
                if (!empty($updateFields)) {
                    $updateValues[] = $id; // WHERE 조건용
                    $updateStmt = $pdo->prepare("UPDATE inventory SET " . implode(', ', $updateFields) . " WHERE id = ?");
                    $updateStmt->execute($updateValues);
                    $updatedCount++;
                    
                    logMessage('INFO', "도서 정보 업데이트 성공", [
                        'id' => $id,
                        'title' => $originalTitle,
                        'updated_fields' => implode(', ', $updateFields),
                        'isbn' => ($isbn && empty($existingIsbn)) ? '추가됨' : '유지',
                        'cover' => ($coverUrl && empty($existingCover)) ? '추가됨' : '유지'
                    ]);
                }
            } else {
                logMessage('DEBUG', "도서 정보를 찾지 못함", [
                    'id' => $id,
                    'title' => $cleanTitle
                ]);
            }
        }

        $pdo->commit();
        
        logMessage('INFO', "도서 정보 재검색 완료", ['updated_count' => $updatedCount]);
        
        sendSuccessResponse([
            'updated_count' => $updatedCount,
            'total_checked' => count($books)
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    logMessage('ERROR', "도서 정보 재검색 실패", ['error' => $e->getMessage()]);
    sendErrorResponse($e->getMessage(), 500);
}
?>
