<?php
require_once __DIR__ . '/../../src/auth.php';
requireRole('admin');
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/xlsx.php';
$pdo = getDB();

$biz  = $_GET['business_id'] ?? null;
$br   = $_GET['branch_id'] ?? null;

$rows = [];
$rows[] = ['Business','Branch','Product','Qty','Reorder Level'];

$sql = "
SELECT biz.name AS business_name, b.name AS branch_name, p.name AS product_name, i.qty, p.reorder_level
FROM inventory i
JOIN branches b ON b.id = i.branch_id
JOIN businesses biz ON biz.id = b.business_id
JOIN products p ON p.id = i.product_id
WHERE 1=1
";
$params = [];
if ($biz)  { $sql .= " AND biz.id = ?"; $params[] = $biz; }
if ($br)   { $sql .= " AND b.id = ?";   $params[] = $br; }
$sql .= " ORDER BY biz.name, b.name, p.name";

$stmt = $pdo->prepare($sql); $stmt->execute($params);
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $rows[] = [$r['business_name'],$r['branch_name'],$r['product_name'],$r['qty'],$r['reorder_level']];
}
xlsx_send('stock_on_hand.xlsx', $rows);
