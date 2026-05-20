<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "db.php";
// 이건 이제 그냥 알겠다 생략
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("잘못된 요청 방식입니다.");
}

$id = $_POST["id"] ?? 0;

if (!ctype_digit((string)$id)) {
    die("잘못된 접근입니다.");
}

// 댓글 조회
$sql = "
    SELECT id, post_id, user_id
    FROM comments
    WHERE id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$comment = $stmt->fetch(PDO::FETCH_ASSOC);
//여기까지 댓글, 게시글, 작성자 정보 다 가져오기 없는거 확인
if (!$comment) {
    die("존재하지 않는 댓글입니다.");
}

// 작성자 본인 확인
if ($comment["user_id"] != $_SESSION["user_id"]) {
    die("댓글 삭제 권한이 없습니다.");
}

// 댓글 삭제
$sql = "
    DELETE FROM comments
    WHERE id = ?
";
//첨부파일 없어서 그냥 comments 테이블에서 id 기준으로 삭제하기만 하면됨
// 삭제 후에 다시 해당 게시글로 돌아가기
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

header("Location: view.php?id=" . urlencode($comment["post_id"]));
exit;
?>