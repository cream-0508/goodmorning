<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "db.php";

$id = $_GET["id"] ?? 0;

if (!ctype_digit((string)$id)) {
    die("잘못된 접근입니다.");
}
//db연결, 세션 확인, url접근 막음

// 댓글 조회
$sql = "
    SELECT id, post_id, user_id, content
    FROM comments
    WHERE id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$comment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$comment) {
    die("존재하지 않는 댓글입니다.");
}

// 작성자 본인 확인
if ($comment["user_id"] != $_SESSION["user_id"]) {
    die("댓글 수정 권한이 없습니다.");
}

$error = "";
//post 요청으로 받았을 시에 내용 변경 넘겨받고 sql로 업데이트
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $content = trim($_POST["content"] ?? "");

    if ($content === "") {
        $error = "댓글 내용을 입력하세요.";
    } else {
        $sql = "
            UPDATE comments
            SET content = ?
            WHERE id = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$content, $id]);
    // 다 변경하면 view의 게시글로 이동
        header("Location: view.php?id=" . urlencode($comment["post_id"]));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>댓글 수정</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="page-wrap">
        <div class="container">

            <header class="board-header">
                <h1>댓글 수정</h1>
                <p class="board-subtitle">작성한 댓글 내용을 수정합니다.</p>
            </header>

            <?php if ($error !== ""): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="comment_edit.php?id=<?= htmlspecialchars($comment["id"]) ?>">
                <div class="form-group">
                    <label>댓글 내용</label>
                    <textarea
                        name="content"
                        rows="6"
                        placeholder="댓글을 입력하세요"
                    ><?= htmlspecialchars($_POST["content"] ?? $comment["content"]) ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">수정 완료</button>
                    <a class="btn btn-outline" href="view.php?id=<?= htmlspecialchars($comment["post_id"]) ?>">취소</a>
                </div>
            </form>

        </div>
    </div>
</body>
</html>