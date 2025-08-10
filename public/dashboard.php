<?php
require_once __DIR__ . '/../src/auth.php';
requireAuth();
$user = $_SESSION['user'];

switch ($user['role']) {
  case 'auditor':
    header('Location: /auditor_dsir.php'); break;
  case 'accountant':
    header('Location: /accountant_queue.php'); break;
  case 'admin':
    header('Location: /admin_dashboard.php'); break;
  case 'programmer':
  default:
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Dashboard</title>
      <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'></head>
      <body class='p-4'>
      <div class='container'>
        <div class='d-flex justify-content-between align-items-center mb-3'>
          <h4>Welcome, ".htmlspecialchars($user['name'])." (".htmlspecialchars($user['role']).")</h4>
          <a class='btn btn-outline-danger btn-sm' href='/logout.php'>Logout</a>
        </div>
        <div class='alert alert-info'>Admin, Accountant, and Auditor modules available. Programmer tools coming next.</div>
        <ul>
          <li><a href='/admin_dashboard.php'>Admin Dashboard</a></li>
          <li><a href='/accountant_queue.php'>Accountant Queue</a></li>
          <li><a href='/auditor_dsir.php'>Auditor DSIR</a></li>
        </ul>
      </div></body></html>";
    break;
}
exit;
