<?php
require_once __DIR__ . '/../../../src/auth.php';
requireRole('programmer');
require_once __DIR__ . '/../../../src/db.php';
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD']==='GET') {
  $rows = $pdo->query("SELECT p.*, u.name AS applied_by_name FROM patches p LEFT JOIN users u ON u.id = p.applied_by ORDER BY COALESCE(p.applied_at, '1970-01-01') DESC, id DESC")->fetchAll();
  echo json_encode(['items'=>$rows]); exit;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
  // Accept multipart/form-data (for file) or JSON body (no file)
  $version = $_POST['version'] ?? null;
  $title   = $_POST['title'] ?? null;
  $note    = $_POST['note'] ?? null;

  if (!$version || !$title) {
    // Try JSON
    $in = json_decode(file_get_contents('php://input'), true);
    $version = $version ?? trim($in['version'] ?? '');
    $title   = $title ?? trim($in['title'] ?? '');
    $note    = $note ?? trim($in['note'] ?? '');
  }
  if (!$version || !$title) { http_response_code(400); echo json_encode(['error'=>'version/title required']); exit; }

  $file_path = null; $file_sha256 = null; $file_size = null;

  if (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $f = $_FILES['file'];
    $allowed = ['sql','php','js','zip','txt'];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) { http_response_code(400); echo json_encode(['error'=>'Invalid file type']); exit; }

    $dir = __DIR__ . '/../../uploads/patches';
    if (!is_dir($dir)) mkdir($dir, 0775, true);

    $fname = 'patch_' . preg_replace('/[^a-zA-Z0-9_.-]/','_', $version . '_' . $f['name']);
    $dest = $dir . '/' . $fname;
    if (!move_uploaded_file($f['tmp_name'], $dest)) { http_response_code(500); echo json_encode(['error'=>'Upload move failed']); exit; }

    $file_path = '/uploads/patches/' . $fname;
    $file_size = filesize($dest);
    $file_sha256 = hash_file('sha256', $dest);
  }

  $stmt=$pdo->prepare("INSERT INTO patches (version,title,note,file_path,file_sha256,file_size,applied_by,applied_at) VALUES (?,?,?,?,?,?,?,NOW())");
  $stmt->execute([$version,$title,$note,$file_path,$file_sha256,$file_size,$_SESSION['user']['id']]);

  echo json_encode(['ok'=>true, 'id'=>$pdo->lastInsertId(), 'file_path'=>$file_path]); exit;
}

http_response_code(405);
