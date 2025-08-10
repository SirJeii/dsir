<?php
require_once __DIR__ . '/../../src/auth.php';
requireRole(['admin','accountant']);
require_once __DIR__ . '/../../src/db.php';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id){die('Missing id');}
$header = $pdo->prepare("
SELECT dr.*, b.name AS branch_name, biz.name AS business_name
FROM dsir_reports dr
JOIN branches b ON b.id=dr.branch_id
JOIN businesses biz ON biz.id=b.business_id
WHERE dr.id=?");
$header->execute([$id]); $h=$header->fetch(); if(!$h){die('Not found');}

$lines = $pdo->prepare("SELECT dl.*, p.name AS product_name, p.srp_cents FROM dsir_lines dl JOIN products p ON p.id=dl.product_id WHERE report_id=?");
$lines->execute([$id]); $L=$lines->fetchAll();
$exp = $pdo->prepare("SELECT * FROM expenses WHERE report_id=?"); $exp->execute([$id]); $E=$exp->fetchAll();
$ew  = $pdo->prepare("SELECT * FROM ewallet_tx WHERE report_id=?"); $ew->execute([$id]); $W=$ew->fetchAll();
$disc= $pdo->prepare("SELECT d.*, p.name AS product_name FROM discount_tx d JOIN products p ON p.id=d.product_id WHERE report_id=?"); $disc->execute([$id]); $D=$disc->fetchAll();

$totSales=array_sum(array_column($L,'sales_cents'));
$totExp=array_sum(array_column($E,'amount_cents'));
$totEW =array_sum(array_column($W,'amount_cents'));
$totDisc=array_sum(array_column($D,'discount_cents'));
$expected=$totSales-$totDisc-$totExp-$totEW; $actual=$h['cash_on_hand_cents']+$h['partial_remit_cents']; $discAmt=$actual-$expected;
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>DSIR #<?= $id ?></title>
<style>
body{font-family:Arial,Helvetica,sans-serif}
h3{margin:0}
small{color:#666}
table{border-collapse:collapse;width:100%;margin-top:8px}
th,td{border:1px solid #aaa;padding:6px;font-size:12px}
.section{margin-top:16px}
@media print {.no-print{display:none}}
</style>
</head><body>
<div class="no-print" style="text-align:right;margin-bottom:8px;">
  <button onclick="window.print()">Print / Save as PDF</button>
</div>
<h3>Daily Sales & Inventory Report</h3>
<small><?= htmlspecialchars($h['business_name']) ?> — <?= htmlspecialchars($h['branch_name']) ?> — <?= $h['report_date'] ?> <?= $h['shift'] ?> — Status: <?= $h['status'] ?></small>

<div class="section">
  <h4>Totals</h4>
  <table><tbody>
    <tr><td>Total Sales</td><td><?= number_format($totSales/100,2) ?></td></tr>
    <tr><td>Expenses</td><td><?= number_format($totExp/100,2) ?></td></tr>
    <tr><td>E-Wallet</td><td><?= number_format($totEW/100,2) ?></td></tr>
    <tr><td>Discounts</td><td><?= number_format($totDisc/100,2) ?></td></tr>
    <tr><td>Expected Cash</td><td><?= number_format($expected/100,2) ?></td></tr>
    <tr><td>Actual Cash</td><td><?= number_format($actual/100,2) ?></td></tr>
    <tr><td>Discrepancy</td><td><?= number_format($discAmt/100,2) ?></td></tr>
  </tbody></table>
</div>

<div class="section">
  <h4>Lines</h4>
  <table><thead><tr><th>Product</th><th>Beg</th><th>In WH</th><th>In Tr</th><th>Out</th><th>Ending</th><th>Usage</th><th>SRP</th><th>Sales</th></tr></thead>
  <tbody>
    <?php foreach($L as $r): ?>
    <tr><td><?= htmlspecialchars($r['product_name']) ?></td><td><?= $r['beginning'] ?></td><td><?= $r['in_wh'] ?></td>
      <td><?= $r['in_transfer'] ?></td><td><?= $r['out_transfer'] ?></td><td><?= $r['ending'] ?></td>
      <td><?= $r['usage_qty'] ?></td><td><?= number_format($r['srp_cents']/100,2) ?></td><td><?= number_format($r['sales_cents']/100,2) ?></td></tr>
    <?php endforeach; ?>
  </tbody></table>
</div>

<div class="section">
  <div style="display:flex; gap:16px;">
    <div style="flex:1">
      <h4>Expenses</h4>
      <table><thead><tr><th>Label</th><th>Amount</th></tr></thead><tbody>
        <?php foreach($E as $e): ?><tr><td><?= htmlspecialchars($e['label']) ?></td><td><?= number_format($e['amount_cents']/100,2) ?></td></tr><?php endforeach; ?>
      </tbody></table>
    </div>
    <div style="flex:1">
      <h4>E‑Wallet</h4>
      <table><thead><tr><th>Platform</th><th>Ref</th><th>Amount</th><th>Received</th></tr></thead><tbody>
        <?php foreach($W as $w): ?><tr><td><?= htmlspecialchars($w['platform']) ?></td><td><?= htmlspecialchars($w['reference']) ?></td><td><?= number_format($w['amount_cents']/100,2) ?></td><td><?= $w['received']?'Yes':'No' ?></td></tr><?php endforeach; ?>
      </tbody></table>
    </div>
  </div>
</div>

<div class="section">
  <h4>Discounts</h4>
  <table><thead><tr><th>Name</th><th>ID No</th><th>Item</th><th>Qty</th><th>Discount</th></tr></thead>
  <tbody><?php foreach($D as $d): ?><tr><td><?= htmlspecialchars($d['customer_name']) ?></td><td><?= htmlspecialchars($d['id_number']) ?></td>
    <td><?= htmlspecialchars($d['product_name']) ?></td><td><?= (int)$d['qty'] ?></td><td><?= number_format($d['discount_cents']/100,2) ?></td></tr><?php endforeach; ?></tbody></table>
</div>
</body></html>
