<?php
// RUN ONCE, THEN DELETE THIS FILE.
// URL: http://localhost/dsir/public/tools/seed_users.php?token=SEED-ALLOW
declare(strict_types=1);

if (($_GET['token'] ?? '') !== 'SEED-ALLOW') {
  http_response_code(403);
  exit('Forbidden');
}

// Load DB config + getDB() helper
require_once __DIR__ . '/../../src/db.php';

try {
  $pdo = getDB();
  $pdo->beginTransaction();

  // Ensure a sample business + branch for the auditor
  $pdo->exec("INSERT IGNORE INTO businesses (id,name) VALUES (1,'Minute Burger')");
  $pdo->exec("INSERT IGNORE INTO branches (id,business_id,name) VALUES (1,1,'MB - Branch 1')");

  // Credentials you can use to log in:
  $users = [
    ['Programmer User','prog@example.com','Prog@1234','programmer', null],
    ['Admin User','admin@example.com','Admin@1234','admin', null],
    ['Auditor User','auditor@example.com','Auditor@1234','auditor', 1],
    ['Accountant User','acct@example.com','Acct@1234','accountant', null],
  ];

  foreach ($users as [$name,$email,$plain,$role,$branchId]) {
    // If user already exists, skip
    $check = $pdo->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    $check->execute([$email]);
    if ($check->fetchColumn()) continue;

    $hash = password_hash($plain, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name,email,password_hash,role,branch_id) VALUES (?,?,?,?,?)");
    $stmt->execute([$name,$email,$hash,$role,$branchId]);
  }

  $pdo->commit();

  header('Content-Type: application/json; charset=utf-8');
  echo json_encode([
    'ok'=>true,
    'message'=>'Seeded users (if not existing). DELETE this file now.',
    'login_accounts'=>[
      ['role'=>'programmer','email'=>'prog@example.com','password'=>'Prog@1234'],
      ['role'=>'admin','email'=>'admin@example.com','password'=>'Admin@1234'],
      ['role'=>'auditor','email'=>'auditor@example.com','password'=>'Auditor@1234'],
      ['role'=>'accountant','email'=>'acct@example.com','password'=>'Acct@1234'],
    ]
  ]);
} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Seeder failed: " . $e->getMessage();
}
