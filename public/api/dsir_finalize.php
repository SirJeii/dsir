<?php
require_once __DIR__ . '/../../src/auth.php';
requireRole('accountant');
require_once __DIR__ . '/../../src/db.php';
$pdo = getDB();

$input = json_decode(file_get_contents('php://input'), true);
$reportId = (int)($input['report_id'] ?? 0);
if (!$reportId) { http_response_code(400); echo json_encode(['error'=>'Missing report_id']); exit; }

// Basic validation: allow finalize from Submitted/Reviewed
$st = $pdo->prepare("SELECT status FROM dsir_reports WHERE id=?");
$st->execute([$reportId]);
$cur = $st->fetchColumn();
if (!$cur) { http_response_code(404); echo json_encode(['error'=>'Report not found']); exit; }
if (!in_array($cur, ['Submitted','Reviewed'])) { http_response_code(400); echo json_encode(['error'=>'Report is not ready to finalize']); exit; }

// If discrepancy exists and not resolved, it will carry (we leave unresolved; carry-over endpoint can be used)
$pdo->prepare("UPDATE dsir_reports SET status='Completed', accountant_id=? WHERE id=?")
    ->execute([$_SESSION['user']['id'], $reportId]);

echo json_encode(['ok'=>true]);
