<?php
require_once "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf_token();

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "아이디와 비밀번호를 모두 입력하세요.";
    } elseif (strlen($password) < 8) {
        $error = "비밀번호는 최소 8자 이상이어야 합니다.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "비밀번호에는 영어 대문자가 최소 1개 포함되어야 합니다.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = "비밀번호에는 영어 소문자가 최소 1개 포함되어야 합니다.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "비밀번호에는 숫자가 최소 1개 포함되어야 합니다.";
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $error = "비밀번호에는 특수문자가 최소 1개 포함되어야 합니다.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);

        try {
            $stmt->execute([$username, $hashedPassword]);

            header("Location: login.php");
            exit;
        } catch (PDOException $e) {
            $error = "회원가입 실패: 이미 존재하는 아이디일 수 있습니다.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>회원가입</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="page-wrap">
        <div class="container">

            <header class="board-header">
                <h1>회원가입</h1>
                <p class="board-subtitle">새 계정을 만들고 게시판을 이용해보세요.</p>
            </header>

            <?php if ($error !== ""): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="register.php">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label>아이디</label>
                    <input
                        type="text"
                        name="username"
                        value="<?= htmlspecialchars($_POST["username"] ?? "") ?>"
                        placeholder="아이디를 입력하세요"
                    >
                </div>

                <div class="form-group">
                    <label>비밀번호</label>
                    <input
                        type="password"
                        name="password"
                        placeholder="비밀번호를 입력하세요"
                    >

                    <p class="help-text">
                        비밀번호는 8자 이상이며, 영어 대문자/소문자/숫자/특수문자를 각각 최소 1개 이상 포함해야 합니다.
                    </p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">회원가입</button>
                    <a class="btn btn-outline" href="login.php">로그인</a>
                    <a class="btn btn-outline" href="index.php">목록으로</a>
                </div>
            </form>

        </div>
    </div>
</body>
</html>