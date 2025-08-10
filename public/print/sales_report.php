<?php
require_once __DIR__ . '/../../src/auth.php';
requireRole(['admin','accountant']);
require_once __DIR__ . '/../../src/db.php';
$pdo = getDB();
$rows = $pdo->query("
SELECT dr.id, biz.name AS business, b.name AS branch, dr.report_date, dr.shift,
       IFNULL((SELECT SUM(sales_cents) FROM dsir_lines dl WHERE dl.report_id=dr.id),0) AS sales,
       IFNULL((SELECT SUM(amount_cents) FROM expenses e WHERE e.report_id=dr.id),0) AS expenses,
       IFNULL((SELECT SUM(amount_cents) FROM ewallet_tx w WHERE w.report_id=dr.id),0) AS ewallet,
       IFNULL((SELECT SUM(discount_cents) FROM discount_tx d WHERE d.report_id=dr.id),0) AS discounts,
       dr.cash_on_hand_cents, dr.partial_remit_cents
FROM dsir_reports dr
JOIN branches b ON b.id=dr.branch_id
JOIN businesses biz ON biz.id=b.business_id
ORDER BY dr.report_date DESC, dr.shift DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Sales Report</title>
<style>
body{font-family:Arial,Helvetica,sans-serif}
table{border-collapse:collapse;width:100%}
th,td{border:1px solid #aaa;padding:6px;font-size:12px}
@media print {.no-print{display:none}}
</style></head>
<body>
<div class="no-print" style="text-align:right;margin-bottom:8px;">
  <button onclick="window.print()">Print / Save as PDF</button>
</div>
<h3>Sales Summary</h3>
<table>
  <thead><tr>
    <th>ID</th><th>Business</th><th>Branch</th><th>Date</th><th>Shift</th>
    <th>Sales</th><th>Expenses</th><th>EWallet</th><th>Discounts</th><th>Expected</th><th>Actual</th><th>Discrepancy</th>
  </tr></thead>
  <tbody>
  <?php foreach ($rows as $r):
    $expected = $r['sales'] - $r['discounts'] - $r['expenses'] - $r['ewallet'];
    $actual = $r['cash_on_hand_cents'] + $r['partial_remit_cents'];
    $disc = $actual - $expected; ?>
    <tr>
      <td><?= $r['id'] ?></td>
      <td><?= htmlspecialchars($r['business']) ?></td>
      <td><?= htmlspecialchars($r['branch']) ?></td>
      <td><?= $r['report_date'] ?></td>
      <td><?= $r['shift'] ?></td>
      <td><?= number_format($r['sales']/100,2) ?></td>
      <td><?= number_format($r['expenses']/100,2) ?></td>
      <td><?= number_format($r['ewallet']/100,2) ?></td>
      <td><?= number_format($r['discounts']/100,2) ?></td>
      <td><?= number_format($expected/100,2) ?></td>
      <td><?= number_format($actual/100,2) ?></td>
      <td><?= number_format($disc/100,2) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</body></html>
