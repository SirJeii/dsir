<?php
require_once __DIR__ . '/../src/auth.php';
requireRole('accountant');
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Accountant Queue</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/dsir/public/css/style.css">
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm">
  <div class="container">
    <span class="navbar-brand mb-0 h5">Accountant – Pending Reports</span>
    <a class="btn btn-outline-danger btn-sm" href="/dsir/public/logout.php">Logout</a>
  </div>
</nav>

<div class="container my-3">
  <div class="card">
    <div class="card-body">
      <form id="filterForm" class="row g-2 align-items-end">
        <div class="col-sm-3">
          <label class="form-label">Business</label>
          <select class="form-select" id="f_business">
            <option value="">All</option>
          </select>
        </div>
        <div class="col-sm-3">
          <label class="form-label">Branch</label>
          <select class="form-select" id="f_branch">
            <option value="">All</option>
          </select>
        </div>
        <div class="col-sm-3">
          <label class="form-label">From</label>
          <input type="date" class="form-control" id="f_from">
        </div>
        <div class="col-sm-3">
          <label class="form-label">To</label>
          <input type="date" class="form-control" id="f_to">
        </div>
        <div class="col-12 mt-2">
          <button class="btn btn-primary" id="btnFilter">Apply Filters</button>
        </div>
      </form>
    </div>
  </div>

  <div class="mt-3" id="queueContainer">
    <!-- Filled by JS -->
  </div>
</div>

<script src="/dsir/public/js/accountant.js"></script>
</body>
</html>

