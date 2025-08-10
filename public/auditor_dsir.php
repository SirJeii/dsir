<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/maintenance_ui.php';
requireRole('auditor');
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>DSIR - Auditor</title>
  <link rel="manifest" href="/dsir/public/manifest.json">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/dsir/public/css/style.css">
</head>
<body>
<?php renderMaintenanceOverlay(); ?>
<nav class="navbar navbar-light bg-white shadow-sm">
  <div class="container">
    <span class="navbar-brand mb-0 h5">Daily Sales & Inventory Report</span>
    <div class="d-flex align-items-center gap-3">
      <span class="text-muted small">Branch ID: <?= htmlspecialchars($user['branch_id'] ?? 'N/A') ?></span>
      <a class="btn btn-outline-danger btn-sm" href="/logout.php">Logout</a>
    </div>
  </div>
</nav>

<div class="container my-3">
  <form id="dsirForm">
    <!-- Header -->
    <div class="row g-3 mb-2">
      <div class="col-md-4">
        <label class="form-label">Date</label>
        <input type="date" class="form-control" name="report_date" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">Shift</label>
        <select class="form-control" name="shift" required>
          <option value="AM">AM</option>
          <option value="PM">PM</option>
        </select>
      </div>
    </div>

    <!-- Inventory -->
    <h6 class="mt-3">Product Inventory</h6>
    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle" id="productTable">
        <thead class="table-light">
          <tr>
            <th>Code</th>
            <th>Product</th>
            <th>Beg</th>
            <th>In (WH)</th>
            <th>In (Transfer)</th>
            <th>Out</th>
            <th>Ending</th>
            <th>Usage</th>
            <th>SRP</th>
            <th>Sales</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>

    <!-- Expenses -->
    <h6 class="mt-4">Expenses</h6>
    <div class="table-responsive">
      <table class="table table-sm table-bordered" id="expensesTable">
        <thead class="table-light">
          <tr>
            <th>Label</th>
            <th>Amount</th>
            <th><button type="button" class="btn btn-sm btn-success" id="addExpense">+</button></th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>

    <!-- E-Wallet -->
    <h6 class="mt-4">E-Wallet Transactions</h6>
    <div class="table-responsive">
      <table class="table table-sm table-bordered" id="ewalletTable">
        <thead class="table-light">
          <tr>
            <th>Platform</th>
            <th>Reference</th>
            <th>Amount</th>
            <th>Note</th>
            <th><button type="button" class="btn btn-sm btn-success" id="addEwallet">+</button></th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>

    <!-- Discounts -->
    <h6 class="mt-4">Discounted Transactions</h6>
    <div class="table-responsive">
      <table class="table table-sm table-bordered" id="discountTable">
        <thead class="table-light">
          <tr>
            <th>Name</th>
            <th>ID No</th>
            <th>Item</th>
            <th>Qty</th>
            <th>ID Image</th>
            <th><button type="button" class="btn btn-sm btn-success" id="addDiscount">+</button></th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>

    <!-- Cash Breakdown -->
    <h6 class="mt-4">Cash Breakdown</h6>
    <div class="table-responsive">
      <table class="table table-sm table-bordered" id="cashTable">
        <thead class="table-light">
          <tr>
            <th>Denomination</th>
            <th>PCS</th>
            <th>Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php $denoms = [1000, 500, 200, 100, 50, 20]; foreach ($denoms as $d): ?>
          <tr>
            <td>₱<?= $d ?></td>
            <td><input type="number" class="form-control pcs" data-value="<?= $d ?>" value="0"></td>
            <td class="amount">0.00</td>
          </tr>
          <?php endforeach; ?>
          <tr>
            <td>Coins</td>
            <td colspan="2"><input type="number" class="form-control" id="coins" value="0"></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label">Total Cash on Hand</label>
        <input type="text" class="form-control" id="cash_on_hand" readonly>
      </div>
      <div class="col-md-4">
        <label class="form-label">Partial Remittance</label>
        <input type="number" class="form-control" id="partial_remit" value="0">
      </div>
    </div>

    <div class="d-flex gap-2 mt-3">
      <button type="submit" class="btn btn-primary">Save Draft</button>
      <button type="button" class="btn btn-success" id="submitBtn">Submit & Lock</button>
    </div>
  </form>
</div>

<script src="/dsir/public/js/dsir.js"></script>
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/dsir/public/service-worker.js').catch(console.error);
}
</script>
</body>
</html>

