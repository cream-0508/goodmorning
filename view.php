<?php
session_start();
require_once "db.php";
// pdo 사용가능
$id = $_GET["id"] ?? 0; //url에서 id 받고 없으면 0

if (!ctype_digit((string)$id)) { //id가 숫자 아니면 문자열로 바꾸고 처리
    die("잘못된 접근입니다.");
}
//select에 있는거 가져오는 sql문, join으로 작성자 이름도 같이 가져오기
$sql = "
    SELECT posts.id, posts.user_id, posts.title, posts.content,
           posts.created_at, posts.updated_at, users.username
    FROM posts
    JOIN users ON posts.user_id = users.id
    WHERE posts.id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    die("존재하지 않는 글입니다.");
}
//파일도 가져오기
$fileSql = "
    SELECT id, original_filename, stored_filename, file_size, uploaded_at
    FROM files
    WHERE post_id = ?
    ORDER BY id ASC
";
// 파일 정보 가져오는 sql문, post_id 기준으로 가져오기
$fileStmt = $pdo->prepare($fileSql);
$fileStmt->execute([$id]);
$files = $fileStmt->fetchAll(PDO::FETCH_ASSOC);
// 댓글 정보 가져오는 sql문, post_id 기준으로 가져오기, 작성자 이름도 같이 가져오기
$commentSql = "
    SELECT comments.id, comments.user_id, comments.content,
           comments.created_at, comments.updated_at, users.username
    FROM comments
    JOIN users ON comments.user_id = users.id
    WHERE comments.post_id = ?
    ORDER BY comments.id ASC
";
//결과 가져온거 comments에 담기
$commentStmt = $pdo->prepare($commentSql);
$commentStmt->execute([$id]);
$comments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);

//현재 세션이 작성자인지 확인해 아래 if문으로 조건부 수정 삭제가능
$isAuthor = isset($_SESSION["user_id"]) && $_SESSION["user_id"] == $post["user_id"];
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($post["title"]) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="page-wrap">
        <div class="container">

            <header class="board-header">
                <h1><?= htmlspecialchars($post["title"]) ?></h1>
                <p class="board-subtitle">게시글 상세 보기</p>
            </header>

            <section class="user-panel">
                <div class="user-info">
                    <p>
                        작성자:
                        <strong><?= htmlspecialchars($post["username"]) ?></strong>
                    </p>
                    <p class="post-meta">
                        작성일: <?= htmlspecialchars($post["created_at"]) ?>
                    </p>

                    <?php if ($post["updated_at"] !== $post["created_at"]): ?>
                        <p class="post-meta">
                            수정일: <?= htmlspecialchars($post["updated_at"]) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="nav-links">
                    <a class="btn btn-outline" href="index.php">목록으로</a>

                    <?php if ($isAuthor): ?>
                        <a class="btn btn-primary" href="edit.php?id=<?= htmlspecialchars($post["id"]) ?>">수정</a>

                        <form method="post" action="delete.php" style="display:inline;">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($post["id"]) ?>">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('정말 삭제하시겠습니까?')">
                                삭제
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </section>

            <section class="post-content">
                <?= nl2br(htmlspecialchars($post["content"])) ?>
            </section>

            <hr>

            <section>
                <h3>첨부파일</h3>

                <?php if (count($files) === 0): ?>
                    <p>첨부파일이 없습니다.</p>
                <?php else: ?>
                    <ul class="file-list">
                        <?php foreach ($files as $file): ?>
                            <li>
                                <a href="download.php?id=<?= htmlspecialchars($file["id"]) ?>">
                                    <?= htmlspecialchars($file["original_filename"]) ?>
                                </a>
                                <span class="file-size">
                                    (<?= htmlspecialchars($file["file_size"]) ?> bytes)
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <hr>

            <section>
                <h3>댓글</h3>

                <?php if (count($comments) === 0): ?>
                    <p>댓글이 없습니다.</p>
                <?php else: ?>
                    <div class="comment-list">
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment-item">
                                <div class="comment-meta">
                                    <strong><?= htmlspecialchars($comment["username"]) ?></strong>
                                    / <?= htmlspecialchars($comment["created_at"]) ?>

                                    <?php if ($comment["updated_at"] !== $comment["created_at"]): ?>
                                        / 수정됨
                                    <?php endif; ?>
                                </div>

                                <div class="comment-content">
                                    <?= nl2br(htmlspecialchars($comment["content"])) ?>
                                </div>

                                <?php if (isset($_SESSION["user_id"]) && $_SESSION["user_id"] == $comment["user_id"]): ?>
                                    <div class="comment-actions">
                                        <a class="btn btn-outline" href="comment_edit.php?id=<?= htmlspecialchars($comment["id"]) ?>">
                                            댓글 수정
                                        </a>

                                        <form method="post" action="comment_delete.php" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($comment["id"]) ?>">
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('댓글을 삭제하시겠습니까?')">
                                                댓글 삭제
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION["user_id"])): ?>
                    <div class="comment-write-box">
                        <h4>댓글 작성</h4>

                        <form method="post" action="comment_add.php">
                            <input type="hidden" name="post_id" value="<?= htmlspecialchars($post["id"]) ?>">

                            <div class="form-group">
                                <textarea
                                    name="content"
                                    rows="4"
                                    placeholder="댓글을 입력하세요"
                                ></textarea>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">댓글 작성</button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <p>
                        댓글을 작성하려면 <a href="login.php">로그인</a>하세요.
                    </p>
                <?php endif; ?>
            </section>

        </div>
    </div>
</body>
</html>