<?php
require_once __DIR__ . '/../../src/auth.php';
requireRole(['admin','accountant']);
require_once __DIR__ . '/../../src/db.php';
$pdo = getDB();

$from = $_GET['from'] ?? null;
$to   = $_GET['to'] ?? null;
$biz  = $_GET['business_id'] ?? null;
$br   = $_GET['branch_id'] ?? null;

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=sales_summary.csv');

$out = fopen('php://output', 'w');
fputcsv($out, ['Report ID','Business','Branch','Date','Shift','Total Sales','Expenses','EWallet','Discounts','Expected Cash','Actual Cash','Discrepancy']);

$sql = "
SELECT dr.id, biz.name AS business_name, b.name AS branch_name, dr.report_date, dr.shift,
       IFNULL((SELECT SUM(sales_cents) FROM dsir_lines dl WHERE dl.report_id=dr.id),0) AS sales,
       IFNULL((SELECT SUM(amount_cents) FROM expenses e WHERE e.report_id=dr.id),0) AS expenses,
       IFNULL((SELECT SUM(amount_cents) FROM ewallet_tx w WHERE w.report_id=dr.id),0) AS ewallet,
       IFNULL((SELECT SUM(discount_cents) FROM discount_tx d WHERE d.report_id=dr.id),0) AS discounts,
       dr.cash_on_hand_cents, dr.partial_remit_cents
FROM dsir_reports dr
JOIN branches b ON b.id = dr.branch_id
JOIN businesses biz ON biz.id = b.business_id
WHERE 1=1
";
$params = [];
if ($from) { $sql .= " AND dr.report_date >= ?"; $params[] = $from; }
if ($to)   { $sql .= " AND dr.report_date <= ?"; $params[] = $to; }
if ($biz)  { $sql .= " AND biz.id = ?"; $params[] = $biz; }
if ($br)   { $sql .= " AND b.id = ?"; $params[] = $br; }
$sql .= " ORDER BY dr.report_date DESC, dr.shift DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $expected = $r['sales'] - $r['discounts'] - $r['expenses'] - $r['ewallet'];
  $actual   = $r['cash_on_hand_cents'] + $r['partial_remit_cents'];
  $disc     = $actual - $expected;
  fputcsv($out, [
    $r['id'], $r['business_name'], $r['branch_name'], $r['report_date'], $r['shift'],
    number_format($r['sales']/100,2,'.',''),
    number_format($r['expenses']/100,2,'.',''),
    number_format($r['ewallet']/100,2,'.',''),
    number_format($r['discounts']/100,2,'.',''),
    number_format($expected/100,2,'.',''),
    number_format($actual/100,2,'.',''),
    number_format($disc/100,2,'.','')
  ]);
}
fclose($out);
