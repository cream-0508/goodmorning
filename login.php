<?php
session_start();
require_once "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") { // post 방식으로 받으면?
    $username = trim($_POST["username"] ?? ""); 
    $password = $_POST["password"] ?? "";
    // 포스트로 받은거 username, pw 넣고 비면 에러
    if ($username === "" || $password === "") {
        $error = "아이디와 비밀번호를 모두 입력하세요.";
    } else {
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username]);
    // 아이디 비번 둘다 있으면 입력한 아이디 있으면 가져오기
    //그리고 비번도 해서 해쉬값 바꿔 비교
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    //세션에 유저아이디랑 유저네임 저장
        if ($user && password_verify($password, $user["password"])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];

            header("Location: index.php");
            exit;
        } else {
            $error = "아이디 또는 비밀번호가 올바르지 않습니다.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>로그인</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="page-wrap">
        <div class="container">

            <header class="board-header">
                <h1>로그인</h1>
                <p class="board-subtitle">게시판을 이용하려면 로그인하세요.</p>
            </header>

            <?php if ($error !== ""): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="login.php">
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
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">로그인</button>
                    <a class="btn btn-outline" href="register.php">회원가입</a>
                    <a class="btn btn-outline" href="index.php">목록으로</a>
                </div>
            </form>

        </div>
    </div>
</body>
</html>