<?php
require_once __DIR__ . '/../../../src/auth.php';
requireRole('programmer');
require_once __DIR__ . '/../../../src/db.php';

$action = trim($_GET['action'] ?? '');
$from   = $_GET['from'] ?? null;
$to     = $_GET['to'] ?? null;

$sql = "SELECT a.*, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id WHERE 1=1";
$params = [];
if ($action !== '') { $sql .= " AND a.action = ?"; $params[] = $action; }
if ($from) { $sql .= " AND a.created_at >= ?"; $params[] = $from.' 00:00:00'; }
if ($to)   { $sql .= " AND a.created_at <= ?"; $params[] = $to.' 23:59:59'; }
$sql .= " ORDER BY a.created_at DESC LIMIT 500";

$pdo = getDB();
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$rows = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode(['items'=>$rows]);
