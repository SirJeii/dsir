<?php
require_once __DIR__ . '/../src/auth.php';
requireRole('admin');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin – Products</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-light bg-white shadow-sm">
  <div class="container">
    <a class="navbar-brand h5 mb-0" href="/dsir/public/admin_dashboard.php">← Admin</a>
    <a class="btn btn-outline-danger btn-sm" href="/dsir/public/logout.php">Logout</a>
  </div>
</nav>

<div class="container my-3">
  <div class="card mb-3">
    <div class="card-header">Add / Edit Product</div>
    <div class="card-body">
      <form id="pForm" class="row g-2">
        <input type="hidden" id="p_id">
        <div class="col-md-3">
          <label class="form-label">Business</label>
          <select class="form-select" id="p_business"></select>
        </div>
        <div class="col-md-2">
          <label class="form-label">SKU</label>
          <input class="form-control" id="p_sku" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Name</label>
          <input class="form-control" id="p_name" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Category</label>
          <input class="form-control" id="p_cat">
        </div>
        <div class="col-md-2">
          <label class="form-label">SRP (₱)</label>
          <input type="number" step="0.01" class="form-control" id="p_srp" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Reorder level</label>
          <input type="number" class="form-control" id="p_reorder" value="0">
        </div>
        <div class="col-12">
          <button class="btn btn-primary" id="saveProd">Save</button>
          <button class="btn btn-secondary" id="resetForm" type="button">Reset</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">Products</div>
    <div class="card-body table-responsive">
      <table class="table table-sm table-striped" id="pTable">
        <thead><tr><th>ID</th><th>Business</th><th>SKU</th><th>Name</th><th>Category</th><th>SRP</th><th>Reorder</th><th>Edit</th><th>Delete</th></tr></thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<script>
const peso = v => `₱${(+v).toFixed(2)}`;

async function loadBusinesses() {
  const res = await fetch('/dsir/public/api/admin/summary.php');
  const data = await res.json();
  const sel = document.getElementById('p_business');
  sel.innerHTML = data.businesses.map(b => `<option value="${b.id}">${b.name}</option>`).join('');
}

async function loadProducts() {
  const res = await fetch('/dsir/public/api/admin/products.php');
  const data = await res.json();
  const tb = document.querySelector('#pTable tbody');
  tb.innerHTML = data.products.map(p => `
    <tr>
      <td>${p.id}</td><td>${p.business_name}</td><td>${p.sku}</td><td>${p.name}</td>
      <td>${p.category ?? ''}</td><td>${peso(p.srp_cents/100)}</td><td>${p.reorder_level}</td>
      <td><button class="btn btn-sm btn-outline-primary edit" data-id="${p.id}">Edit</button></td>
      <td><button class="btn btn-sm btn-outline-danger del" data-id="${p.id}">Delete</button></td>
    </tr>
  `).join('');
  document.querySelectorAll('.edit').forEach(btn => btn.addEventListener('click', () => {
    const p = data.products.find(x => x.id == btn.dataset.id);
    document.getElementById('p_id').value = p.id;
    document.getElementById('p_business').value = p.business_id;
    document.getElementById('p_sku').value = p.sku;
    document.getElementById('p_name').value = p.name;
    document.getElementById('p_cat').value = p.category ?? '';
    document.getElementById('p_srp').value = (p.srp_cents/100).toFixed(2);
    document.getElementById('p_reorder').value = p.reorder_level;
  }));
  document.querySelectorAll('.del').forEach(btn => btn.addEventListener('click', async () => {
    if (!confirm('Delete product?')) return;
    await fetch('/dsir/public/api/admin/products.php', { method:'DELETE', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id:+btn.dataset.id })});
    await loadProducts();
  }));
}

document.getElementById('saveProd').addEventListener('click', async e => {
  e.preventDefault();
  const payload = {
    id: document.getElementById('p_id').value || null,
    business_id: +document.getElementById('p_business').value,
    sku: document.getElementById('p_sku').value,
    name: document.getElementById('p_name').value,
    category: document.getElementById('p_cat').value,
    srp_cents: Math.round((+document.getElementById('p_srp').value || 0)*100),
    reorder_level: +document.getElementById('p_reorder').value || 0
  };
  await fetch('/dsir/public/api/admin/products.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
  document.getElementById('pForm').reset();
  await loadProducts();
});
document.getElementById('resetForm').addEventListener('click', () => document.getElementById('pForm').reset());

(async function(){ await loadBusinesses(); await loadProducts(); })();
</script>
</body>
</html>

