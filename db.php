<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") .
        '">';
}

function verify_csrf_token(): void
{
    $sessionToken = $_SESSION["csrf_token"] ?? "";
    $requestToken = $_POST["csrf_token"] ?? "";

    if (
        $sessionToken === "" ||
        $requestToken === "" ||
        !hash_equals($sessionToken, $requestToken)
    ) {
        http_response_code(400);
        exit("잘못된 요청입니다.");
    }
}

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