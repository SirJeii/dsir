<?php
require_once __DIR__ . '/../../src/auth.php';
requireRole('auditor');
require_once __DIR__ . '/../../src/db.php';

$pdo = getDB();
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { http_response_code(400); echo json_encode(['error'=>'Invalid JSON']); exit; }

$uid = (int)$_SESSION['user']['id'];
$branchId   = (int)($_SESSION['user']['branch_id'] ?? 0);
$reportDate = $input['report_date'] ?? null;
$shift      = $input['shift'] ?? null;
if (!$branchId || !$reportDate || !$shift) { http_response_code(400); echo json_encode(['error'=>'Missing branch/date/shift']); exit; }

/* Allowed products */
$allowed = $pdo->prepare("
  SELECT p.id FROM products p
  JOIN branches b ON b.business_id = p.business_id
  WHERE b.id = ?
  AND (EXISTS (SELECT 1 FROM user_product_access upa WHERE upa.user_id = ? AND upa.product_id = p.id)
       OR NOT EXISTS (SELECT 1 FROM user_product_access upa2 WHERE upa2.user_id = ?))
");
$allowed->execute([$branchId,$uid,$uid]);
$allowedSet = array_flip(array_map(fn($r)=>(int)$r['id'],$allowed->fetchAll()));

$pdo->beginTransaction();
try {
  $stmt = $pdo->prepare("SELECT id,status FROM dsir_reports WHERE branch_id=? AND report_date=? AND shift=? FOR UPDATE");
  $stmt->execute([$branchId, $reportDate, $shift]);
  $rep = $stmt->fetch(); $reportId = null; $wasNew = false;

  if (!$rep) {
    $pdo->prepare("INSERT INTO dsir_reports (branch_id,report_date,shift,auditor_id,status) VALUES (?,?,?,?, 'Draft')")
        ->execute([$branchId,$reportDate,$shift,$uid]);
    $reportId = (int)$pdo->lastInsertId(); $wasNew = true;
  } else {
    if ($rep['status'] === 'Completed') throw new Exception('Report already completed.');
    $reportId = (int)$rep['id'];
  }

  if ($wasNew) {
    $dt=new DateTime($reportDate); $prevShift=($shift==='AM')?'PM':'AM'; if ($shift==='AM') $dt->modify('-1 day');
    $prevDate=$dt->format('Y-m-d');
    $prev=$pdo->prepare("SELECT id FROM dsir_reports WHERE branch_id=? AND report_date=? AND shift=?");
    $prev->execute([$branchId,$prevDate,$prevShift]);
    if ($prevId=(int)$prev->fetchColumn()) {
      $pdo->prepare("UPDATE sales_discrepancy SET carried_to_report_id=? WHERE report_id=? AND resolved=0 AND carried_to_report_id IS NULL")
          ->execute([$reportId,$prevId]);
    }
  }

  $pdo->prepare("DELETE FROM dsir_lines   WHERE report_id=?")->execute([$reportId]);
  $pdo->prepare("DELETE FROM expenses     WHERE report_id=?")->execute([$reportId]);
  $pdo->prepare("DELETE FROM ewallet_tx   WHERE report_id=?")->execute([$reportId]);
  $pdo->prepare("DELETE FROM discount_tx  WHERE report_id=?")->execute([$reportId]);

  $getSRP = $pdo->prepare("SELECT srp_cents FROM products WHERE id=?");
  $insLine= $pdo->prepare("INSERT INTO dsir_lines (report_id,product_id,beginning,in_wh,in_transfer,out_transfer,ending,sales_cents) VALUES (?,?,?,?,?,?,?,?)");

  $totalSalesCents = 0;
  foreach ($input['lines'] as $ln) {
    $pid = (int)$ln['product_id']; if (!isset($allowedSet[$pid])) continue;
    $beg=(int)$ln['beginning']; $inwh=(int)$ln['in_wh']; $intr=(int)$ln['in_transfer']; $out=(int)$ln['out_transfer']; $end=(int)$ln['ending'];
    $getSRP->execute([$pid]); $srp=(int)($getSRP->fetch()['srp_cents'] ?? 0);
    $usage = max(0, $beg + $inwh + $intr - $out - $end);
    $sales = $usage * $srp; $totalSalesCents += $sales;
    $insLine->execute([$reportId,$pid,$beg,$inwh,$intr,$out,$end,$sales]);
  }

  $insExp = $pdo->prepare("INSERT INTO expenses (report_id,label,amount_cents) VALUES (?,?,?)");
  $totalExpCents = 0;
  foreach ($input['expenses'] as $e) {
    $label=trim($e['label']??''); $amt=(int)($e['amount_cents']??0);
    if ($label!=='' && $amt>0){ $insExp->execute([$reportId,$label,$amt]); $totalExpCents += $amt; }
  }

  $insEW = $pdo->prepare("INSERT INTO ewallet_tx (report_id,platform,reference,amount_cents,note) VALUES (?,?,?,?,?)");
  $totalEwCents = 0;
  foreach ($input['ewallet'] as $w) {
    $plat=$w['platform']??'Other'; $ref=trim($w['reference']??''); $amt=(int)($w['amount_cents']??0); $note=$w['note']??null;
    if ($ref!=='' && $amt>0) { $insEW->execute([$reportId,$plat,$ref,$amt,$note]); $totalEwCents += $amt; }
  }

  $insDisc = $pdo->prepare("INSERT INTO discount_tx (report_id,customer_name,id_number,product_id,qty,id_image_path,discount_cents) VALUES (?,?,?,?,?,?,?)");
  $totalDiscCents = 0;
  foreach ($input['discounts'] as $d) {
    $name=trim($d['customer_name']??''); $idno=trim($d['id_number']??''); $pid=(int)($d['product_id']??0); $qty=(int)($d['qty']??0);
    if (!isset($allowedSet[$pid])) continue;
    $getSRP->execute([$pid]); $srp=(int)($getSRP->fetch()['srp_cents'] ?? 0);
    $disc=(int)round($srp*0.20) * max(1,$qty);
    if ($name!=='' && $idno!=='' && $pid>0 && $qty>0){ $insDisc->execute([$reportId,$name,$idno,$pid,$qty,null,$disc]); $totalDiscCents += $disc; }
  }

  $cashOnHand = (int)($input['cash_on_hand_cents'] ?? 0);
  $partialRem = (int)($input['partial_remit_cents'] ?? 0);

  $expectedCash = $totalSalesCents - $totalDiscCents - $totalExpCents - $totalEwCents;
  $actualCash   = $cashOnHand + $partialRem;
  $discrepancy  = $actualCash - $expectedCash;

  $pdo->prepare("DELETE FROM sales_discrepancy WHERE report_id=?")->execute([$reportId]);
  if ($discrepancy !== 0) {
    $pdo->prepare("INSERT INTO sales_discrepancy (report_id,amount_cents,reason) VALUES (?,?,?)")
        ->execute([$reportId,$discrepancy,'Auto-detected on submit']);
  }

  $pdo->prepare("UPDATE dsir_reports SET status='Submitted', auditor_id=?, cash_on_hand_cents=?, partial_remit_cents=?, notes=COALESCE(?,notes) WHERE id=?")
      ->execute([$uid,$cashOnHand,$partialRem,$input['notes'] ?? null,$reportId]);

  $pdo->commit();
  echo json_encode(['ok'=>true,'report_id'=>$reportId,'totals'=>[
    'sales_cents'=>$totalSalesCents,'expenses_cents'=>$totalExpCents,'ewallet_cents'=>$totalEwCents,'discounts_cents'=>$totalDiscCents,
    'expected_cash_cents'=>$expectedCash,'actual_cash_cents'=>$actualCash,'discrepancy_cents'=>$discrepancy
  ]]);
} catch (Exception $e) { $pdo->rollBack(); http_response_code(400); echo json_encode(['error'=>$e->getMessage()]); }
