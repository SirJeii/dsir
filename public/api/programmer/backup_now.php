<?php
require_once __DIR__ . '/../../../src/auth.php';
requireRole('programmer');
require_once __DIR__ . '/../../../src/db.php';
require_once __DIR__ . '/../../../src/audit.php';

$pdo = getDB();
$tables = [];
foreach ($pdo->query("SHOW FULL TABLES WHERE Table_type='BASE TABLE'") as $row) { $tables[] = $row[0]; }

$dump  = "-- MBIS backup\n-- Generated: ".date('Y-m-d H:i:s')."\n\nSET FOREIGN_KEY_CHECKS=0;\n";
foreach ($tables as $t) {
  $create = $pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_ASSOC)['Create Table'] ?? '';
  $dump .= "DROP TABLE IF EXISTS `$t`;\n$create;\n\n";
  $rows = $pdo->query("SELECT * FROM `$t`");
  while ($r = $rows->fetch(PDO::FETCH_ASSOC)) {
    $cols = array_map(fn($c)=>"`$c`", array_keys($r));
    $vals = array_map(fn($v)=> is_null($v)?'NULL':$pdo->quote($v), array_values($r));
    $dump .= "INSERT INTO `$t` (".implode(',',$cols).") VALUES (".implode(',',$vals).");\n";
  }
  $dump .= "\n";
}
$dump .= "SET FOREIGN_KEY_CHECKS=1;\n";

$dir = __DIR__ . '/../../uploads/backups';
if (!is_dir($dir)) mkdir($dir, 0775, true);
$fname = 'backup_'.date('Ymd_His').'.sql';
$path  = $dir.'/'.$fname;
file_put_contents($path, $dump);

$size = filesize($path);
$pdo->prepare("INSERT INTO backups (file_path,file_size,created_by) VALUES (?,?,?)")
    ->execute(['/uploads/backups/'.$fname,$size,$_SESSION['user']['id']]);

audit_log('BACKUP_CREATE', 'backup', null, ['file'=>$fname,'size'=>$size]);

header('Content-Type: application/json');
echo json_encode(['ok'=>true,'file'=>'/uploads/backups/'.$fname,'bytes'=>$size]);
