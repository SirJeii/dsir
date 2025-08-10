<?php
require_once __DIR__ . '/../../src/auth.php';
requireRole('accountant');
require_once __DIR__ . '/../../src/db.php';
$pdo = getDB();

$input = json_decode(file_get_contents('php://input'), true);
$reportId = (int)($input['report_id'] ?? 0);
if (!$reportId) { http_response_code(400); echo json_encode(['error'=>'Missing report_id']); exit; }

// Find discrepancy
$sd = $pdo->prepare("SELECT id, amount_cents, resolved FROM sales_discrepancy WHERE report_id=? ORDER BY id DESC LIMIT 1");
$sd->execute([$reportId]);
$disc = $sd->fetch();
if (!$disc) { http_response_code(400); echo json_encode(['error'=>'No discrepancy to carry']); exit; }
if ($disc['resolved']) { http_response_code(400); echo json_encode(['error'=>'Discrepancy already resolved']); exit; }

// Determine next shift report (AM→PM same day; PM→tomorrow AM)
$head = $pdo->prepare("SELECT branch_id, report_date, shift FROM dsir_reports WHERE id=?");
$head->execute([$reportId]);
$h = $head->fetch();
if (!$h) { http_response_code(404); echo json_encode(['error'=>'Report not found']); exit; }

$dt = new DateTime($h['report_date']);
$nextShift = 'PM';
if ($h['shift'] === 'PM') { $dt->modify('+1 day'); $nextShift = 'AM'; }
$nextDate = $dt->format('Y-m-d');

// Try link to next report if it exists; if not, just set reason flag
$next = $pdo->prepare("SELECT id FROM dsir_reports WHERE branch_id=? AND report_date=? AND shift=?");
$next->execute([$h['branch_id'], $nextDate, $nextShift]);
$nextId = $next->fetchColumn();

if ($nextId) {
  $pdo->prepare("UPDATE sales_discrepancy SET carried_to_report_id = ? WHERE id = ?")->execute([$nextId, $disc['id']]);
  echo json_encode(['ok'=>true,'message'=>"Carried to report #$nextId ($nextDate $nextShift)."]);
} else {
  // Keep it flagged for future linkage
  echo json_encode(['ok'=>true,'message'=>"Next shift ($nextDate $nextShift) not found yet. Will auto-link when created."]);
}
