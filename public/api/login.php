<?php
declare(strict_types=1);

// ---- Strict JSON API headers (and no accidental HTML) ----
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

// Turn off implicit output to avoid BOM/whitespace messing up JSON
// (only if no output buffering has started)
if (!headers_sent()) {
  while (ob_get_level() > 0) { ob_end_clean(); } // clear any previous buffers
}

require_once __DIR__ . '/../../src/db.php';

// Minimal, safe audit (optional)
$haveAudit = is_file(__DIR__ . '/../../src/audit.php');
if ($haveAudit) require_once __DIR__ . '/../../src/audit.php';

function respond(int $code, array $payload): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  respond(405, ['error' => 'POST only']);
}

// Parse JSON body safely
$raw = file_get_contents('php://input');
$in  = json_decode($raw ?? '', true);
if (!is_array($in)) {
  respond(400, ['error' => 'Invalid JSON body']);
}

$email = strtolower(trim((string)($in['email'] ?? '')));
$pass  = (string)($in['password'] ?? '');
$ip    = $_SERVER['REMOTE_ADDR'] ?? '';

if ($email === '' || $pass === '') {
  respond(400, ['error' => 'Missing credentials']);
}

try {
  $pdo = getDB();

  // Rate limit: 5 fails in last 10 min for same email OR IP
  $q = $pdo->prepare("SELECT COUNT(*) FROM login_attempts
                      WHERE success=0
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                        AND (email=? OR ip=?)");
  $q->execute([$email, $ip]);
  if ((int)$q->fetchColumn() >= 5) {
    respond(429, ['error' => 'Too many attempts. Try again in a few minutes.']);
  }

  // Lookup user
  $stmt = $pdo->prepare("SELECT id,name,email,role,branch_id,password_hash FROM users WHERE email=? LIMIT 1");
  $stmt->execute([$email]);
  $u = $stmt->fetch(PDO::FETCH_ASSOC);

  $ok = $u && isset($u['password_hash']) && password_verify($pass, $u['password_hash']);

  // Record attempt
  $pdo->prepare("INSERT INTO login_attempts (email, ip, success) VALUES (?,?,?)")
      ->execute([$email, $ip, $ok ? 1 : 0]);

  if (!$ok) {
    respond(401, ['error' => 'Invalid email or password']);
  }

  // Start session and set user
  if (session_status() !== PHP_SESSION_ACTIVE) {
    // secure session options BEFORE start
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
  }
  session_regenerate_id(true);
  $_SESSION['user'] = [
    'id'        => (int)$u['id'],
    'name'      => (string)$u['name'],
    'email'     => (string)$u['email'],
    'role'      => (string)$u['role'],
    'branch_id' => $u['branch_id'] !== null ? (int)$u['branch_id'] : null,
  ];

  if ($haveAudit) {
    audit_log('LOGIN_SUCCESS','user',(int)$u['id'],['email'=>$email]);
  }

  respond(200, ['ok' => true, 'user' => [
    'id'=>(int)$u['id'],'name'=>$u['name'],'role'=>$u['role'],'branch_id'=>$_SESSION['user']['branch_id']
  ]]);

} catch (Throwable $e) {
  // Don’t leak internal errors to the client in production
  respond(500, ['error' => 'Server error during login']);
}
