<?php
require_once __DIR__ . '/../../src/auth.php';
requireRole('auditor');
require_once __DIR__ . '/../../src/db.php';
$pdo = getDB();

$user = $_SESSION['user'];
$branchId = (int)($user['branch_id'] ?? 0);
if (!$branchId) { http_response_code(400); echo json_encode(['error'=>'No branch assigned']); exit; }

$stmt = $pdo->prepare("
  SELECT p.id, p.sku, p.name, p.category, p.srp_cents
  FROM products p
  JOIN branches b ON b.business_id = p.business_id
  WHERE b.id = ?
  AND (
    EXISTS (SELECT 1 FROM user_product_access a WHERE a.user_id = ? AND a.product_id = p.id)
    OR NOT EXISTS (SELECT 1 FROM user_product_access a2 WHERE a2.user_id = ?)
  )
  ORDER BY p.name
");
$stmt->execute([$branchId, $user['id'], $user['id']]);
echo json_encode(['products' => $stmt->fetchAll()]);
