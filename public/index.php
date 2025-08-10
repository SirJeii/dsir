<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/url.php';

start_session_once();

$user = $_SESSION['user'] ?? null;
if (!$user) {
  redirect_to('login.php');
}

switch ($user['role'] ?? '') {
  case 'programmer':
    redirect_to('programmer.php');
    break;
  case 'admin':
    redirect_to('admin_dashboard.php');
    break;
  case 'auditor':
    redirect_to('auditor_dsir.php');
    break;
  case 'accountant':
    redirect_to('accountant_queue.php');
    break;
  default:
    redirect_to('logout.php');
}
