<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
require_once "db.php";
//세션 시작하고 확인하고 db연결
//post 요청만 받기 view.php에서 post로 보내는 버튼있음
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("잘못된 요청 방식입니다.");
}

$id = $_POST["id"] ?? 0;
//직접 url 접근 방지
if (!ctype_digit((string)$id)) {
    die("잘못된 접근입니다.");
}

// 1. 삭제할 글 조회
$sql = "
    SELECT id, user_id
    FROM posts
    WHERE id = ?
";
// id 기준으로 게시글 가져오기, 삭제 권한 확인 위해 user_id도 같이 가져오기
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    die("존재하지 않는 글입니다.");
}

// 2. 작성자 본인인지 확인
if ($post["user_id"] != $_SESSION["user_id"]) {
    die("삭제 권한이 없습니다.");
}

try {
    //임시 삭제하고 확정이 아님
    $pdo->beginTransaction();

    // 3. 실제 파일 삭제를 위해 파일 목록 먼저 가져오기
    $fileSql = "
        SELECT stored_filename
        FROM files
        WHERE post_id = ?
    ";

    $fileStmt = $pdo->prepare($fileSql);
    $fileStmt->execute([$id]);
    $files = $fileStmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. posts 삭제
    // comments, files 테이블의 DB 데이터는 ON DELETE CASCADE로 같이 삭제됨
    $deleteSql = "
        DELETE FROM posts
        WHERE id = ?
    ";

    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute([$id]);
    //게시글만 삭제 확정
    $pdo->commit();

    // 5. DB 삭제 성공 후 실제 파일 삭제
    foreach ($files as $file) {
        $filePath = __DIR__ . "/uploads/" . $file["stored_filename"];
    //만약 파일이 있으면 삭제
        if (is_file($filePath)) {
            unlink($filePath);
        }
    }

    header("Location: index.php");
    exit;

} catch (Exception $e) {
    //실패시 롤백    
    $pdo->rollBack();
    die("삭제 실패: " . $e->getMessage());
}
?>