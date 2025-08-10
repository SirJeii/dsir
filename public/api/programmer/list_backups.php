<?php
require_once __DIR__ . '/../../../src/auth.php';
requireRole('programmer');
require_once __DIR__ . '/../../../src/db.php';
$pdo = getDB();
$rows = $pdo->query("SELECT b.*, u.name AS created_by_name FROM backups b LEFT JOIN users u ON u.id=b.created_by ORDER BY b.created_at DESC")->fetchAll();
header('Content-Type: application/json');
echo json_encode(['items'=>$rows]);
