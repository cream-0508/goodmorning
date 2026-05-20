<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "db.php";
//세션 확인하고 DB 연결하기

$id = $_GET["id"] ?? 0;

if (!ctype_digit((string)$id)) {
    die("잘못된 접근입니다.");
}

$sql = "
    SELECT id, user_id, title, content
    FROM posts
    WHERE id = ?
";
// id 기준으로 게시글 가져오고 없으면 잘못된 접근이라고 처리하기

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
//게시글 내용 불러와 post에 담기
if (!$post) {//없으면
    die("존재하지 않는 글입니다.");
}

if ($post["user_id"] != $_SESSION["user_id"]) {//세션이 다르면 수정불가
    die("수정 권한이 없습니다.");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") { //post 요청일때 제목과 내용 받아오기
    $title = trim($_POST["title"] ?? "");
    $content = trim($_POST["content"] ?? "");

    if ($title === "" || $content === "") {
        $error = "제목과 내용을 모두 입력하세요.";
    } else {
        $sql = "
            UPDATE posts
            SET title = ?, content = ?
            WHERE id = ?
        ";
// 게시글 업데이트하는 sql
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $content, $id]);

        header("Location: view.php?id=" . urlencode($id));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>글 수정</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="page-wrap">
        <div class="container">

            <header class="board-header">
                <h1>글 수정</h1>
                <p class="board-subtitle">작성한 게시글의 제목과 내용을 수정합니다.</p>
            </header>

            <?php if ($error !== ""): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="edit.php?id=<?= htmlspecialchars($post["id"]) ?>">
                <div class="form-group">
                    <label>제목</label>
                    <input
                        type="text"
                        name="title"
                        value="<?= htmlspecialchars($_POST["title"] ?? $post["title"]) ?>"
                        placeholder="제목을 입력하세요"
                    >
                </div>

                <div class="form-group">
                    <label>내용</label>
                    <textarea
                        name="content"
                        rows="12"
                        placeholder="내용을 입력하세요"
                    ><?= htmlspecialchars($_POST["content"] ?? $post["content"]) ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">수정 완료</button>
                    <a class="btn btn-outline" href="view.php?id=<?= htmlspecialchars($post["id"]) ?>">취소</a>
                    <a class="btn btn-outline" href="index.php">목록으로</a>
                </div>
            </form>

        </div>
    </div>
</body>
</html>