<?php
require_once __DIR__ . '/../../../src/auth.php';
requireRole('programmer');
require_once __DIR__ . '/../../../src/db.php';
require_once __DIR__ . '/../../../src/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
if (empty($_FILES['file'])) { http_response_code(400); echo json_encode(['error'=>'No file']); exit; }
$f = $_FILES['file'];
$ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
if ($ext !== 'sql') { http_response_code(400); echo json_encode(['error'=>'Only .sql allowed']); exit; }
$sql = file_get_contents($f['tmp_name']);
if (!$sql) { http_response_code(400); echo json_encode(['error'=>'Empty file']); exit; }

$pdo = getDB();
$pdo->beginTransaction();
try {
  $stmts = preg_split('/;\s*[\r\n]+/m', $sql);
  foreach ($stmts as $s) {
    $s = trim($s);
    if ($s === '' || str_starts_with($s,'--') || str_starts_with($s,'/*')) continue;
    $pdo->exec($s);
  }
  $pdo->commit();
  audit_log('BACKUP_RESTORE','backup',null,['filename'=>$f['name'],'bytes'=>filesize($f['tmp_name'])]);
  header('Content-Type: application/json'); echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  $pdo->rollBack();
  http_response_code(400);
  echo json_encode(['error'=>'Restore failed: '.$e->getMessage()]);
}
