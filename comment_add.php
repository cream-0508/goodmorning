<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
require_once "db.php";
// 세션 확인하고 db연결
//post 요청만 받기 view.php에서 post로 보내는 버튼있음
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("잘못된 요청 방식입니다.");
}

verify_csrf_token();

$post_id = $_POST["post_id"] ?? 0;
$content = trim($_POST["content"] ?? "");

if (!ctype_digit((string)$post_id)) {
    die("잘못된 접근입니다.");
}

if ($content === "") {
    die("댓글 내용을 입력하세요.");
}

// 게시글이 실제로 존재하는지 확인
$sql = "
    SELECT id
    FROM posts
    WHERE id = ?
";
//post로 가져와서 담기
$stmt = $pdo->prepare($sql);
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    die("존재하지 않는 글입니다.");
}

// 댓글 저장
$sql = "
    INSERT INTO comments (post_id, user_id, content)
    VALUES (?, ?, ?)
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $post_id,
    $_SESSION["user_id"], //이건 세션에 저장된 user_id 가져오기
    $content
]);
//끝나면 view로 이동
header("Location: view.php?id=" . urlencode($post_id));
exit;
?>