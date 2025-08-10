<?php
require_once __DIR__ . '/../../../src/auth.php';
requireRole('admin');
require_once __DIR__ . '/../../../src/db.php';
$pdo = getDB();

/* GET (list users or list access for a user) */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  if (isset($_GET['access_user'])) {
    $uid = (int)$_GET['access_user'];
    $acc = $pdo->prepare("SELECT product_id FROM user_product_access WHERE user_id=?");
    $acc->execute([$uid]);
    echo json_encode(['access'=>$acc->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
  }
  $rows = $pdo->query("SELECT id,name,email,role,branch_id FROM users ORDER BY id")->fetchAll();
  echo json_encode(['users'=>$rows]); exit;
}

/* POST (create/update user OR set access) */
$input = json_decode(file_get_contents('php://input'), true);

if (isset($_GET['set_access'])) {
  $uid = (int)($input['user_id'] ?? 0);
  $pids = $input['product_ids'] ?? [];
  $pdo->beginTransaction();
  try {
    $pdo->prepare("DELETE FROM user_product_access WHERE user_id=?")->execute([$uid]);
    $ins = $pdo->prepare("INSERT INTO user_product_access (user_id,product_id) VALUES (?,?)");
    foreach ($pids as $pid) { $ins->execute([$uid, (int)$pid]); }
    $pdo->commit();
    echo json_encode(['ok'=>true]);
  } catch (Exception $e) { $pdo->rollBack(); http_response_code(400); echo json_encode(['error'=>$e->getMessage()]); }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $input['id'] ?? null;
  $name = trim($input['name'] ?? '');
  $email= trim($input['email'] ?? '');
  $role = $input['role'] ?? 'auditor';
  $branch= $input['branch_id'] ?? null;
  $pass = $input['password'] ?? null;

  if (!$name || !$email) { http_response_code(400); echo json_encode(['error'=>'Name/email required']); exit; }

  if ($id) {
    // Update
    if ($pass) {
      $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=?, branch_id=?, password_hash=? WHERE id=?");
      $stmt->execute([$name,$email,$role,$branch, hash('sha256',$pass), $id]);
    } else {
      $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=?, branch_id=? WHERE id=?");
      $stmt->execute([$name,$email,$role,$branch, $id]);
    }
  } else {
    // Create
    $stmt = $pdo->prepare("INSERT INTO users (name,email,password_hash,role,branch_id) VALUES (?,?,?,?,?)");
    $stmt->execute([$name,$email, hash('sha256',$pass ?: 'changeme'), $role, $branch]);
  }
  echo json_encode(['ok'=>true]); exit;
}

/* DELETE */
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
  $input = json_decode(file_get_contents('php://input'), true);
  $id = (int)($input['id'] ?? 0);
  if (!$id) { http_response_code(400); echo json_encode(['error'=>'Missing id']); exit; }
  $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
  echo json_encode(['ok'=>true]); exit;
}

http_response_code(405);
