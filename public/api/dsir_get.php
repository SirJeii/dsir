<?php
require_once __DIR__ . '/../../src/auth.php';
requireRole(['accountant','admin']); // allow admin too
require_once __DIR__ . '/../../src/db.php';
$pdo = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { http_response_code(400); echo json_encode(['error'=>'Missing id']); exit; }

$header = $pdo->prepare("
SELECT dr.*, b.name AS branch_name, biz.name AS business_name
FROM dsir_reports dr
JOIN branches b ON b.id = dr.branch_id
JOIN businesses biz ON biz.id = b.business_id
WHERE dr.id = ?
");
$header->execute([$id]);
$h = $header->fetch();
if (!$h) { http_response_code(404); echo json_encode(['error'=>'Not found']); exit; }

$lines = $pdo->prepare("
SELECT dl.*, p.name AS product_name, p.srp_cents
FROM dsir_lines dl JOIN products p ON p.id = dl.product_id
WHERE dl.report_id = ?
");
$lines->execute([$id]);
$L = $lines->fetchAll();

$expenses = $pdo->prepare("SELECT id,label,amount_cents FROM expenses WHERE report_id = ?");
$expenses->execute([$id]);

$ew = $pdo->prepare("SELECT id,platform,reference,amount_cents,note,received FROM ewallet_tx WHERE report_id = ?");
$ew->execute([$id]);

$disc = $pdo->prepare("
SELECT d.id, d.customer_name, d.id_number, d.qty, d.discount_cents, p.name AS product_name
FROM discount_tx d JOIN products p ON p.id = d.product_id
WHERE d.report_id = ?
");
$disc->execute([$id]);

// Totals
$totalSales = 0;
foreach ($L as $r) { $totalSales += (int)$r['sales_cents']; }
$totalExp = array_sum(array_column($expenses->fetchAll(PDO::FETCH_ASSOC), 'amount_cents'));
$ewalletRows = $ew->fetchAll(PDO::FETCH_ASSOC);
$totalEW = array_sum(array_column($ewalletRows, 'amount_cents'));
$discRows = $disc->fetchAll(PDO::FETCH_ASSOC);
$totalDisc = array_sum(array_column($discRows, 'discount_cents'));

$expected = $totalSales - $totalDisc - $totalExp - $totalEW;
$actual   = (int)$h['cash_on_hand_cents'] + (int)$h['partial_remit_cents'];
$discrep  = $actual - $expected;

echo json_encode([
  'header' => [
    'id' => $h['id'],
    'business_name' => $h['business_name'],
    'branch_name' => $h['branch_name'],
    'report_date' => $h['report_date'],
    'shift' => $h['shift'],
    'status' => $h['status']
  ],
  'totals' => [
    'sales_cents' => $totalSales,
    'expenses_cents' => $totalExp,
    'ewallet_cents' => $totalEW,
    'discounts_cents' => $totalDisc,
    'expected_cash_cents' => $expected,
    'actual_cash_cents' => $actual,
    'discrepancy_cents' => $discrep
  ],
  'lines' => array_map(function($r){
    return [
      'product_name'=>$r['product_name'],
      'beginning'=>$r['beginning'],
      'in_wh'=>$r['in_wh'],
      'in_transfer'=>$r['in_transfer'],
      'out_transfer'=>$r['out_transfer'],
      'ending'=>$r['ending'],
      'usage_qty'=>$r['usage_qty'],
      'srp_cents'=>$r['srp_cents'],
      'sales_cents'=>$r['sales_cents']
    ];
  }, $L),
  'expenses' => $pdo->query("SELECT label,amount_cents FROM expenses WHERE report_id = $id")->fetchAll(),
  'ewallet'  => $ewalletRows,
  'discounts'=> $discRows
]);
