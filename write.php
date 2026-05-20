<?php
session_start();
//로그인 세션 없으면 로그인 페이지로 이동
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {//post 요청일 경우,
    verify_csrf_token();

    $title = trim($_POST["title"] ?? ""); //값이 없으면 빈 문자열
    $content = trim($_POST["content"] ?? "");
    $user_id = $_SESSION["user_id"];

    if ($title === "" || $content === "") {
        $error = "제목과 내용을 모두 입력하세요.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. 게시글 먼저 저장
            $sql = "
                INSERT INTO posts (user_id, title, content)
                VALUES (?, ?, ?)
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $title, $content]);

            // 2. 방금 생성된 게시글 id 가져오기
            $post_id = $pdo->lastInsertId();

            // 3. 파일이 업로드되었으면 처리
            if (isset($_FILES["upload_file"]) && $_FILES["upload_file"]["error"] === UPLOAD_ERR_OK) {
                  if ($_FILES["upload_file"]["error"] !== UPLOAD_ERR_OK) {
                       throw new Exception("파일 업로드 중 오류가 발생했습니다.");
                      }
                $originalName = $_FILES["upload_file"]["name"];
                $tmpName = $_FILES["upload_file"]["tmp_name"];
                $fileSize = $_FILES["upload_file"]["size"];

                // 1. 파일 크기 제한: 5MB
                 $maxFileSize = 5 * 1024 * 1024;

                 if ($fileSize > $maxFileSize) {
                    throw new Exception("파일 크기는 5MB 이하만 업로드할 수 있습니다.");
                  }
                  // 2. 확장자 검사
                 $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                 $allowedExtensions = ["jpg", "jpeg", "png", "gif", "pdf", "txt", "zip"];

                 if (!in_array($ext, $allowedExtensions, true)) {
                        throw new Exception("허용되지 않는 파일 확장자입니다.");
                  }

                 // 3. MIME 타입 검사
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->file($tmpName);

                    $allowedMimeTypes = [
                        "jpg"  => ["image/jpeg"],
                        "jpeg" => ["image/jpeg"],
                        "png"  => ["image/png"],
                        "gif"  => ["image/gif"],
                        "pdf"  => ["application/pdf"],
                        "txt"  => ["text/plain"],
                        "zip"  => ["application/zip", "application/x-zip-compressed"]
                    ];

                  if (!isset($allowedMimeTypes[$ext]) || !in_array($mimeType, $allowedMimeTypes[$ext], true)) {
                     throw new Exception("파일 확장자와 실제 파일 형식이 일치하지 않습니다.");
                  }

                if ($ext !== "") {
                    $storedName = time() . "_" . bin2hex(random_bytes(8)) . "." . $ext;
                } else {
                    $storedName = time() . "_" . bin2hex(random_bytes(8));
                } // 확장자 붙이고 이름 중복방지를 위한 랜덤명 지정

                $uploadPath = "uploads/" . $storedName;

                if (!move_uploaded_file($tmpName, $uploadPath)) {
                    throw new Exception("파일 업로드에 실패했습니다.");
                }

                // 4. files 테이블에 파일 정보 저장
                $sql = "
                    INSERT INTO files (post_id, original_filename, stored_filename, file_size)
                    VALUES (?, ?, ?, ?)
                ";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([$post_id, $originalName, $storedName, $fileSize]);
            }

            $pdo->commit();

            header("Location: index.php");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "글 작성 실패: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>글쓰기</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="page-wrap">
        <div class="container">

            <header class="board-header">
                <h1>글쓰기</h1>
                <p class="board-subtitle">새 게시글을 작성합니다.</p>
            </header>

            <section class="user-panel">
                <div class="user-info">
                    <p>
                        <strong><?= htmlspecialchars($_SESSION["username"]) ?></strong>님이 글을 작성 중입니다.
                    </p>
                </div>

                <div class="nav-links">
                    <a class="btn btn-outline" href="index.php">목록으로</a>
                    <a class="btn btn-outline" href="logout.php">로그아웃</a>
                </div>
            </section>

            <?php if ($error !== ""): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="write.php" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label>제목</label>
                    <input
                        type="text"
                        name="title"
                        value="<?= htmlspecialchars($_POST["title"] ?? "") ?>"
                        placeholder="제목을 입력하세요"
                    >
                </div>

                <div class="form-group">
                    <label>내용</label>
                    <textarea
                        name="content"
                        rows="12"
                        placeholder="내용을 입력하세요"
                    ><?= htmlspecialchars($_POST["content"] ?? "") ?></textarea>
                </div>

                <div class="form-group">
                    <label>첨부파일</label>
                    <input type="file" name="upload_file">
                    <p class="help-text">
                        파일 업로드 중 오류가 나면 파일을 다시 선택해야 합니다.
                    </p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">작성 완료</button>
                    <a class="btn btn-outline" href="index.php">취소</a>
                </div>
            </form>

        </div>
    </div>
</body>
</html>