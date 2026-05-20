<?php
require_once "db.php";

$id = $_GET["id"] ?? 0;

if (!ctype_digit((string)$id)) {
    die("잘못된 접근입니다.");
}
// db불러오고 직접 접근 막기
//id 기준으로 파일 정보 가져오기
$sql = "
    SELECT id, post_id, original_filename, stored_filename, file_size
    FROM files
    WHERE id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    die("파일 정보가 존재하지 않습니다.");
}
// 실제 저장된 파일 위치를 경로로 만들기
$filePath = __DIR__ . "/uploads/" . $file["stored_filename"];

if (!is_file($filePath)) {
    die("서버에 파일이 존재하지 않습니다.");
}
//다운로드용 바이너리 데이터로 응답
header("Content-Type: application/octet-stream");
//화면에 열지말고 다운로드, 원래 이름으로(basename으로 경로 없앰)
header("Content-Disposition: attachment; filename=\"" . basename($file["original_filename"]) . "\"");
//파일 크기 알려주기
header("Content-Length: " . filesize($filePath));
//캐시 하지말고 받기
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
//지정된 파일 경로에서 파일 읽어서 브라우저로 출력하기 헤더 때문에 다운로드로 처리됨
readfile($filePath);
exit;
?>