<?php
session_start();
// if (!isset($_SESSION["user_id"])) {
//     header("Location: login.php");
//     exit;
// }
require_once "db.php"; //로그인 세션 가져오고 DB 연결하기
//불러온 후로 pdo 사용가능 
$keyword = trim($_GET["keyword"] ?? "");// keyword 받아오고 없으면 ""+공백제거
$searchType = $_GET["search_type"] ?? "title";

$allowedTypes = ["title", "username"]; //검색 옵션 두개만 허용
//만약 배열에 검색옵션이 없다면 기본값이 title.
//없어도 되지만 url에 이상한 값 들어오는거 방지하기 위해서
if (!in_array($searchType, $allowedTypes, true)) {
    $searchType = "title";
}


if ($keyword !== "") { //get으로 검색어 받아오기 
    if ($searchType === "title") { // 제목 검색일 경우
        $sql = "
            SELECT posts.id, posts.title, posts.created_at, users.username
            FROM posts
            JOIN users ON posts.user_id = users.id
            WHERE posts.title LIKE ?
            ORDER BY posts.id DESC
        ";
    } else { // 작성자 검색일 경우
        $sql = "
            SELECT posts.id, posts.title, posts.created_at, users.username
            FROM posts
            JOIN users ON posts.user_id = users.id
            WHERE users.username LIKE ?
            ORDER BY posts.id DESC
        ";
    }

    $stmt = $pdo->prepare($sql);
    $search = "%" . $keyword . "%";
    $stmt->execute([$search]);
} else {
    $sql = "
        SELECT posts.id, posts.title, posts.created_at, users.username
        FROM posts
        JOIN users ON posts.user_id = users.id
        ORDER BY posts.id DESC
    ";

    $stmt = $pdo->query($sql);
}
//posts는 게시글 정보와 작성자 이름을 담은 결과셋, 게시글이 여러개일 수 있으니 fetchAll로 다 가져오기
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC); // 실행결과 다 가져오기 , 결과를 연관 배열로?
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>게시판</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="page-wrap">
        <div class="container">

            <header class="board-header">
                <h1>게시판</h1>
                <p class="board-subtitle">반갑습니다! 아무 글이나 올려주세요. 욕설은 안됨.</p>
            </header>

            <section class="user-panel">
                <div class="user-info">
                    <?php if (isset($_SESSION["user_id"])): ?>
                        <p>
                            <strong><?= htmlspecialchars($_SESSION["username"]) ?></strong>님 로그인 중입니다.
                        </p>
                    <?php else: ?>
                        <p>로그인하지 않은 상태입니다.</p>
                    <?php endif; ?>
                </div>

                <div class="nav-links">
                    <?php if (isset($_SESSION["user_id"])): ?>
                        <a class="btn btn-primary" href="write.php">글쓰기</a>
                        <a class="btn btn-outline" href="logout.php">로그아웃</a>
                    <?php else: ?>
                        <a class="btn btn-primary" href="login.php">로그인</a>
                        <a class="btn btn-outline" href="register.php">회원가입</a>
                    <?php endif; ?>
                </div>
            </section>

            <section class="search-section">
                <form method="get" action="index.php" class="search-form">
                    <select name="search_type">
                        <option value="title" <?= $searchType === "title" ? "selected" : "" ?>>
                            제목
                        </option>
                        <option value="username" <?= $searchType === "username" ? "selected" : "" ?>>
                            작성자
                        </option>
                    </select>

                    <input
                        type="text"
                        name="keyword"
                        placeholder="검색어를 입력하세요"
                        value="<?= htmlspecialchars($keyword) ?>"
                    >

                    <button type="submit" class="btn btn-primary">검색</button>

                    <?php if ($keyword !== ""): ?>
                        <a class="btn btn-outline" href="index.php">전체 목록</a>
                    <?php endif; ?>
                </form>
            </section>

            <section class="board-card">
                <table class="board-table">
                    <thead>
                        <tr>
                            <th class="col-no">번호</th>
                            <th>제목</th>
                            <th class="col-writer">작성자</th>
                            <th class="col-date">작성일</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($posts) === 0): ?>
                            <tr>
                                <td colspan="4" class="empty-message">
                                    게시글이 없습니다.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($posts as $index => $post): ?>
                                <tr>
                                    <td class="col-no"><?= $index + 1 ?></td>
                                    <td class="title-cell">
                                        <a href="view.php?id=<?= htmlspecialchars($post["id"]) ?>">
                                            <?= htmlspecialchars($post["title"]) ?>
                                        </a>
                                    </td>
                                    <td class="col-writer">
                                        <?= htmlspecialchars($post["username"]) ?>
                                    </td>
                                    <td class="col-date">
                                        <?= htmlspecialchars($post["created_at"]) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

        </div>
    </div>
</body>
</html>