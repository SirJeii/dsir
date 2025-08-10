<?php
require_once __DIR__ . '/../../../src/auth.php';
requireRole('programmer');
require_once __DIR__ . '/../../../src/db.php';
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD']==='GET') {
  $rows = $pdo->query("SELECT * FROM settings ORDER BY k")->fetchAll();
  echo json_encode(['items'=>$rows]); exit;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $in = json_decode(file_get_contents('php://input'), true);
  if (!$in) { http_response_code(400); echo json_encode(['error'=>'Invalid JSON']); exit; }

  // Single {k,v} or batch [{k,v},...]
  $items = isset($in[0]) ? $in : [ $in ];
  $stmt=$pdo->prepare("INSERT INTO settings (k,v,updated_by,updated_at) VALUES (?,?,?,NOW())
                       ON DUPLICATE KEY UPDATE v=VALUES(v), updated_by=VALUES(updated_by), updated_at=VALUES(updated_at)");
  $pdo->beginTransaction();
  try {
    foreach ($items as $row) {
      $k = trim($row['k'] ?? ''); $v = (string)($row['v'] ?? '');
      if (!$k) continue;
      $stmt->execute([$k,$v,$_SESSION['user']['id']]);
    }
    $pdo->commit();
    echo json_encode(['ok'=>true]);
  } catch (Exception $e) { $pdo->rollBack(); http_response_code(400); echo json_encode(['error'=>$e->getMessage()]); }
  exit;
}

http_response_code(405);
