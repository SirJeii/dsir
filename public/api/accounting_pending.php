<?php
require_once __DIR__ . '/../../src/auth.php';
requireRole('accountant');
require_once __DIR__ . '/../../src/db.php';
$pdo = getDB();

$businessId = $_GET['business_id'] ?? null;
$branchId   = $_GET['branch_id'] ?? null;
$from       = $_GET['from'] ?? null;
$to         = $_GET['to'] ?? null;

$sql = "
SELECT dr.id AS report_id, dr.report_date, dr.shift, dr.status,
       b.id AS branch_id, b.name AS branch_name, biz.id AS business_id, biz.name AS business_name,
       IFNULL(SUM(dl.sales_cents),0) AS total_sales_cents,
       IFNULL((SELECT SUM(amount_cents) FROM expenses e WHERE e.report_id = dr.id),0) AS total_expenses_cents,
       IFNULL((SELECT SUM(amount_cents) FROM ewallet_tx w WHERE w.report_id = dr.id),0) AS total_ewallet_cents,
       EXISTS(SELECT 1 FROM sales_discrepancy sd WHERE sd.report_id = dr.id AND sd.resolved = 0) AS has_discrepancy
FROM dsir_reports dr
JOIN branches b ON b.id = dr.branch_id
JOIN businesses biz ON biz.id = b.business_id
LEFT JOIN dsir_lines dl ON dl.report_id = dr.id
WHERE dr.status IN ('Submitted','Reviewed')
";
$params = [];

if ($businessId) { $sql .= " AND biz.id = ?"; $params[] = $businessId; }
if ($branchId)   { $sql .= " AND b.id = ?";    $params[] = $branchId; }
if ($from)       { $sql .= " AND dr.report_date >= ?"; $params[] = $from; }
if ($to)         { $sql .= " AND dr.report_date <= ?"; $params[] = $to; }

$sql .= " GROUP BY dr.id ORDER BY dr.report_date DESC, dr.shift DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
echo json_encode(['items' => $stmt->fetchAll()]);
