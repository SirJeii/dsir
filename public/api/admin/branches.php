<?php
require_once __DIR__ . '/../../../src/auth.php';
requireRole('admin');
require_once __DIR__ . '/../../../src/db.php';
$pdo = getDB();

/* GET */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $rows = $pdo->query("
    SELECT b.id,b.business_id,b.name,b.code,b.address, biz.name AS business_name
    FROM branches b JOIN businesses biz ON biz.id = b.business_id
    ORDER BY biz.name, b.name
  ")->fetchAll();
  echo json_encode(['branches'=>$rows]); exit;
}

/* POST (create/update) */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $input = json_decode(file_get_contents('php://input'), true);
  $id = $input['id'] ?? null;
  $business_id = (int)($input['business_id'] ?? 0);
  $name = trim($input['name'] ?? '');
  $code = trim($input['code'] ?? '');
  $address = trim($input['address'] ?? '');

  if (!$business_id || !$name || !$code) { http_response_code(400); echo json_encode(['error'=>'Missing fields']); exit; }

  if ($id) {
    $stmt = $pdo->prepare("UPDATE branches SET business_id=?, name=?, code=?, address=? WHERE id=?");
    $stmt->execute([$business_id,$name,$code,$address,$id]);
  } else {
    $stmt = $pdo->prepare("INSERT INTO branches (business_id,name,code,address) VALUES (?,?,?,?)");
    $stmt->execute([$business_id,$name,$code,$address]);
  }
  echo json_encode(['ok'=>true]); exit;
}

/* DELETE */
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
  $input = json_decode(file_get_contents('php://input'), true);
  $id = (int)($input['id'] ?? 0);
  if (!$id) { http_response_code(400); echo json_encode(['error'=>'Missing id']); exit; }
  $pdo->prepare("DELETE FROM branches WHERE id=?")->execute([$id]);
  echo json_encode(['ok'=>true]); exit;
}

http_response_code(405);
