<?php
require_once __DIR__ . '/../src/auth.php';
requireRole('accountant');
$rid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Report #<?= htmlspecialchars($rid) ?> – Accountant</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/dsir/public/css/style.css">
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm">
  <div class="container">
    <a class="navbar-brand h5 mb-0" href="/dsir/public/accountant_queue.php">← Accountant</a>
    <a class="btn btn-outline-danger btn-sm" href="/dsir/public/logout.php">Logout</a>
  </div>
</nav>

<div class="container my-3" id="reportRoot" data-report-id="<?= $rid ?>">
  <div id="alertBox"></div>
  <div class="d-flex justify-content-between align-items-center">
    <h4 class="mb-0">Report #<?= htmlspecialchars($rid) ?></h4>
    <div>
      <button class="btn btn-secondary btn-sm" id="btnCarry">Carry Discrepancy → Next Shift</button>
      <button class="btn btn-success btn-sm" id="btnFinalize">Finalize</button>
    </div>
  </div>
  <hr>

  <div id="headerInfo" class="mb-3"></div>

  <h6>Totals</h6>
  <div id="totals" class="mb-3"></div>

  <h6>Lines</h6>
  <div class="table-responsive">
    <table class="table table-sm table-bordered" id="linesTable">
      <thead class="table-light">
        <tr>
          <th>Product</th><th>Beg</th><th>In WH</th><th>In Tr</th><th>Out</th><th>Ending</th><th>Usage</th><th>SRP</th><th>Sales</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

  <div class="row">
    <div class="col-md-6">
      <h6>E-Wallet Transactions</h6>
      <table class="table table-sm table-bordered" id="ewTable">
        <thead class="table-light">
          <tr><th>Platform</th><th>Reference</th><th>Amount</th><th>Received?</th><th>Action</th></tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
    <div class="col-md-6">
      <h6>Expenses</h6>
      <table class="table table-sm table-bordered" id="expTable">
        <thead class="table-light">
          <tr><th>Label</th><th>Amount</th></tr>
        </thead>
        <tbody></tbody>
      </table>
      <h6 class="mt-3">Discounts</h6>
      <table class="table table-sm table-bordered" id="discTable">
        <thead class="table-light">
          <tr><th>Name</th><th>ID</th><th>Item</th><th>Qty</th><th>Discount</th></tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<script src="/dsir/public/js/accountant.js"></script>
</body>
</html>

