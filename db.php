<?php
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
} // 직접 경로 접근 방지
$host = "localhost";
$dbname = "board_db";
$user = "board_user";
$pass = "board_pass";

try{
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass
    );
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // 예외 모드 설정
}catch(PDOException $e){
    die("DB 연결 실패:" . $e->getMessage());
}
?>