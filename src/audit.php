<?php
require_once __DIR__ . '/db.php';
function audit_log(string $action, ?string $entity=null, $entity_id=null, $details=null) {
  try {
    $pdo = getDB();
    $uid = $_SESSION['user']['id'] ?? null;
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua  = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200);
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id,action,entity,entity_id,details,ip,ua) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$uid,$action,$entity,$entity_id, $details? json_encode($details): null, $ip,$ua]);
  } catch (Throwable $e) { /* best effort */ }
}
