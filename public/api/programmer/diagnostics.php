<?php
require_once __DIR__ . '/../../../src/auth.php';
requireRole('programmer');
require_once __DIR__ . '/../../../src/db.php';
$pdo = getDB();

function ini_val($k){ $v=ini_get($k); return $v===false?'':$v; }
$uploadsPath = realpath(__DIR__ . '/../../uploads') ?: (__DIR__ . '/../../uploads');
$free = @disk_free_space($uploadsPath);
$freeGB = $free !== false ? round($free/1024/1024/1024,2) : 'N/A';

$counts = [
  'businesses' => (int)$pdo->query("SELECT COUNT(*) FROM businesses")->fetchColumn(),
  'branches'   => (int)$pdo->query("SELECT COUNT(*) FROM branches")->fetchColumn(),
  'products'   => (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
  'reports'    => (int)$pdo->query("SELECT COUNT(*) FROM dsir_reports")->fetchColumn(),
  'lines'      => (int)$pdo->query("SELECT COUNT(*) FROM dsir_lines")->fetchColumn(),
];

$lastPatch = $pdo->query("SELECT id, version, title, applied_at FROM patches ORDER BY applied_at DESC, id DESC LIMIT 1")->fetch();

echo json_encode([
  'php' => [
    'version' => PHP_VERSION,
    'extensions' => get_loaded_extensions(),
    'upload_max' => ini_val('upload_max_filesize'),
    'post_max'   => ini_val('post_max_size'),
  ],
  'storage' => [
    'uploads_path' => $uploadsPath,
    'free_gb' => $freeGB
  ],
  'db' => [
    'counts' => $counts
  ],
  'last_patch' => $lastPatch ?: null
]);
