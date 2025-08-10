<?php
require_once __DIR__ . '/../../../src/auth.php';
requireRole('admin');
require_once __DIR__ . '/../../../src/db.php';
$pdo = getDB();

/* GET */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $rows = $pdo->query("
    SELECT p.*, biz.name AS business_name
    FROM products p JOIN businesses biz ON biz.id = p.business_id
    ORDER BY biz.name, p.name
  ")->fetchAll();
  echo json_encode(['products'=>$rows]); exit;
}

/* POST (create/update) */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $input = json_decode(file_get_contents('php://input'), true);
  $id = $input['id'] ?? null;
  $business_id = (int)($input['business_id'] ?? 0);
  $sku = trim($input['sku'] ?? '');
  $name = trim($input['name'] ?? '');
  $cat = trim($input['category'] ?? '');
  $srp_cents = (int)($input['srp_cents'] ?? 0);
  $reorder = (int)($input['reorder_level'] ?? 0);

  if (!$business_id || !$sku || !$name) { http_response_code(400); echo json_encode(['error'=>'Missing fields']); exit; }

  if ($id) {
    $stmt = $pdo->prepare("UPDATE products SET business_id=?, sku=?, name=?, category=?, srp_cents=?, reorder_level=? WHERE id=?");
    $stmt->execute([$business_id,$sku,$name,$cat,$srp_cents,$reorder,$id]);
  } else {
    $stmt = $pdo->prepare("INSERT INTO products (business_id,sku,name,category,srp_cents,reorder_level) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$business_id,$sku,$name,$cat,$srp_cents,$reorder]);
  }
  echo json_encode(['ok'=>true]); exit;
}

/* DELETE with safe check */
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
  $input = json_decode(file_get_contents('php://input'), true);
  $id = (int)($input['id'] ?? 0);
  if (!$id) { http_response_code(400); echo json_encode(['error'=>'Missing id']); exit; }

  // Check references
  $refs = [
    'dsir_lines'  => $pdo->prepare("SELECT COUNT(*) FROM dsir_lines WHERE product_id=?"),
    'discount_tx' => $pdo->prepare("SELECT COUNT(*) FROM discount_tx WHERE product_id=?"),
    'inventory'   => $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE product_id=?"),
    'user_access' => $pdo->prepare("SELECT COUNT(*) FROM user_product_access WHERE product_id=?")
  ];
  $counts = [];
  foreach ($refs as $k=>$stmt) { $stmt->execute([$id]); $counts[$k] = (int)$stmt->fetchColumn(); }
  $totalRefs = array_sum($counts);

  if ($totalRefs > 0) {
    http_response_code(409);
    echo json_encode(['error'=>'Product in use and cannot be deleted','refs'=>$counts]);
    exit;
  }

  $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
  echo json_encode(['ok'=>true]); exit;
}

http_response_code(405);
