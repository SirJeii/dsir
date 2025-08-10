<?php
require_once __DIR__ . '/../../src/auth.php';
requireRole('accountant');
require_once __DIR__ . '/../../src/db.php';
$pdo = getDB();

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);
$received = (int)($input['received'] ?? 0);

if (!$id) { http_response_code(400); echo json_encode(['error'=>'Missing id']); exit; }

$stmt = $pdo->prepare("UPDATE ewallet_tx SET received = ? WHERE id = ?");
$stmt->execute([$received ? 1 : 0, $id]);
echo json_encode(['ok'=>true]);
