<?php
require_once __DIR__ . '/../src/auth.php';
requireRole('admin');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="container my-3">
  <nav class="navbar navbar-light bg-white shadow-sm mb-3">
    <div class="container-fluid">
      <span class="navbar-brand h5 mb-0">Admin – Dashboard</span>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="/admin_users.php">Users</a>
        <a class="btn btn-outline-secondary btn-sm" href="/admin_branches.php">Branches</a>
        <a class="btn btn-outline-secondary btn-sm" href="/admin_products.php">Products</a>
        <a class="btn btn-outline-secondary btn-sm" href="/admin_warehouse.php">Warehouse</a>
        <a class="btn btn-outline-danger btn-sm" href="/logout.php">Logout</a>
      </div>
    </div>
  </nav>

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header">Sales by Branch (Last 14 Days)</div>
        <div class="card-body"><canvas id="salesChart" height="120"></canvas></div>
      </div>
      <div class="card mt-3">
        <div class="card-header">Top Products (30 days)</div>
        <div class="card-body"><canvas id="topProducts" height="120"></canvas></div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Low Stock (Top 10)</span>
          <div>
            <a class="btn btn-sm btn-outline-secondary" id="btnStockCSV" href="/api/export_stock_csv.php">CSV</a>
            <a class="btn btn-sm btn-outline-secondary" id="btnStockXLSX" href="/api/export_stock_xlsx.php">XLSX</a>
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm">
              <thead><tr><th>Branch</th><th>Product</th><th>Qty</th></tr></thead>
              <tbody id="lowStockBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Exports with filters -->
      <div class="card mb-3">
        <div class="card-header">Exports</div>
        <div class="card-body">
          <form id="expForm" class="row g-2 align-items-end">
            <div class="col-md-6">
              <label class="form-label">From</label>
              <input type="date" class="form-control" id="expFrom">
            </div>
            <div class="col-md-6">
              <label class="form-label">To</label>
              <input type="date" class="form-control" id="expTo">
            </div>
            <div class="col-md-6">
              <label class="form-label">Business ID</label>
              <input type="number" class="form-control" id="expBiz" placeholder="optional">
            </div>
            <div class="col-md-6">
              <label class="form-label">Branch ID</label>
              <input type="number" class="form-control" id="expBr" placeholder="optional">
            </div>
            <div class="col-12 d-flex gap-2">
              <a class="btn btn-outline-primary btn-sm" id="btnSalesCSV" href="#">Sales CSV</a>
              <a class="btn btn-outline-primary btn-sm" id="btnSalesXLSX" href="#">Sales XLSX</a>
              <a class="btn btn-outline-secondary btn-sm" href="/print/sales_report.php" target="_blank">Sales PDF (Print)</a>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header">Sales by Business (30 days)</div>
        <div class="card-body"><canvas id="bizChart" height="120"></canvas></div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const setExportLinks = () => {
    const from = document.getElementById('expFrom').value;
    const to   = document.getElementById('expTo').value;
    const biz  = document.getElementById('expBiz').value;
    const br   = document.getElementById('expBr').value;
    const p = new URLSearchParams();
    if (from) p.set('from', from);
    if (to)   p.set('to', to);
    if (biz)  p.set('business_id', biz);
    if (br)   p.set('branch_id', br);
    const q = p.toString(); const qs = q ? ('?'+q) : '';
    document.getElementById('btnSalesCSV').href  = '/api/export_sales_csv.php'  + qs;
    document.getElementById('btnSalesXLSX').href = '/api/export_sales_xlsx.php' + qs;
    document.getElementById('btnStockCSV').href  = '/api/export_stock_csv.php'  + qs;
    document.getElementById('btnStockXLSX').href = '/api/export_stock_xlsx.php' + qs;
  };
  ['expFrom','expTo','expBiz','expBr'].forEach(id => document.getElementById(id).addEventListener('input', setExportLinks));
  setExportLinks();
})();
</script>

<script>
(async function(){
  const sumRes = await fetch('/api/admin/summary.php'); const sum = await sumRes.json();

  const ctx = document.getElementById('salesChart');
  const labels = sum.sales.labels;
  const datasets = sum.sales.branches.map(b=>({label:b.branch_name, data:b.daily_sales.map(v=>v/100), borderWidth:2, tension:.2}));
  new Chart(ctx,{type:'line',data:{labels,datasets},options:{plugins:{legend:{display:true}},scales:{y:{title:{display:true,text:'₱'}}}}});

  document.getElementById('lowStockBody').innerHTML = sum.low_stock.map(r=>`<tr><td>${r.branch_name}</td><td>${r.product_name}</td><td>${r.qty}</td></tr>`).join('');

  const anRes = await fetch('/api/admin/analytics.php'); const an = await anRes.json();

  const biz = an.business_sales;
  new Chart(document.getElementById('bizChart'), { type:'pie', data:{labels:biz.map(x=>x.business), datasets:[{data:biz.map(x=>(+x.sales)/100)}]} });

  const top = an.top_products;
  new Chart(document.getElementById('topProducts'), {
    type:'bar', data:{labels:top.map(x=>x.product), datasets:[{label:'Sales (₱)', data:top.map(x=>(+x.sales)/100)}]}, options:{indexAxis:'y'}
  });
})();
</script>
</body>
</html>
