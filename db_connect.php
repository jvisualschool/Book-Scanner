<?php
/**
 * 데이터베이스 연결 설정
 * config.php에서 환경 변수를 읽어옵니다.
 */

require_once 'config.php';

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    logMessage('ERROR', '데이터베이스 연결 실패', ['error' => $e->getMessage()]);
    throw new \PDOException('데이터베이스 연결에 실패했습니다.', (int)$e->getCode());
}
?>
