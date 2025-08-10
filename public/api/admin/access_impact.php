<?php
require_once __DIR__ . '/../../../src/auth.php';
requireRole('admin');
require_once __DIR__ . '/../../../src/db.php';
$pdo = getDB();

/*
Input: { user_id: number, new_product_ids: number[] }
Return: { impacted: [ {report_id,date,shift,branch_name,product_names[]} ] }
Only considers DSIR with status = 'Draft' created by that user (auditor_id = user_id).
*/
$in = json_decode(file_get_contents('php://input'), true);
$uid = (int)($in['user_id'] ?? 0);
$newIds = $in['new_product_ids'] ?? [];
if (!$uid) { http_response_code(400); echo json_encode(['error'=>'Missing user_id']); exit; }
$newSet = array_fill_keys(array_map('intval', $newIds), true);

/* Find user's draft reports */
$drafts = $pdo->prepare("
  SELECT dr.id, dr.report_date, dr.shift, b.name AS branch_name
  FROM dsir_reports dr
  JOIN branches b ON b.id = dr.branch_id
  WHERE dr.auditor_id = ? AND dr.status = 'Draft'
");
$drafts->execute([$uid]);
$impacted = [];

$getLines = $pdo->prepare("
  SELECT dl.product_id, p.name
  FROM dsir_lines dl
  JOIN products p ON p.id = dl.product_id
  WHERE dl.report_id = ?
");

foreach ($drafts->fetchAll() as $rep) {
  $getLines->execute([$rep['id']]);
  $bad = [];
  foreach ($getLines->fetchAll() as $ln) {
    $pid = (int)$ln['product_id'];
    if (!isset($newSet[$pid])) $bad[] = $ln['name'];
  }
  if ($bad) {
    $impacted[] = [
      'report_id' => (int)$rep['id'],
      'date' => $rep['report_date'],
      'shift' => $rep['shift'],
      'branch_name' => $rep['branch_name'],
      'product_names' => $bad
    ];
  }
}

echo json_encode(['impacted' => $impacted]);
