<?php
require_once __DIR__ . '/../../src/auth.php';
requireRole('admin');
require_once __DIR__ . '/../../src/db.php';
$pdo = getDB();

$biz  = $_GET['business_id'] ?? null;
$br   = $_GET['branch_id'] ?? null;

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=stock_on_hand.csv');

$out = fopen('php://output', 'w');
fputcsv($out, ['Business','Branch','Product','Qty','Reorder Level']);

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

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
  fputcsv($out, [$r['business_name'],$r['branch_name'],$r['product_name'],$r['qty'],$r['reorder_level']]);
}
fclose($out);
