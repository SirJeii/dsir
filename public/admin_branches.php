<?php
require_once __DIR__ . '/../src/auth.php';
requireRole('admin');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin – Branches</title>
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
    <div class="card-header">Add / Edit Branch</div>
    <div class="card-body">
      <form id="bForm" class="row g-2">
        <input type="hidden" id="b_id">
        <div class="col-md-3">
          <label class="form-label">Business</label>
          <select class="form-select" id="b_business"></select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Name</label>
          <input class="form-control" id="b_name" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Code</label>
          <input class="form-control" id="b_code" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Address</label>
          <input class="form-control" id="b_addr">
        </div>
        <div class="col-12">
          <button class="btn btn-primary" id="saveBranch">Save</button>
          <button class="btn btn-secondary" id="resetForm" type="button">Reset</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">Branches</div>
    <div class="card-body table-responsive">
      <table class="table table-sm table-striped" id="bTable">
        <thead><tr><th>ID</th><th>Business</th><th>Name</th><th>Code</th><th>Address</th><th>Edit</th><th>Delete</th></tr></thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<script>
async function loadBusinesses() {
  const res = await fetch('/dsir/public/api/admin/summary.php');
  const data = await res.json();
  const sel = document.getElementById('b_business');
  sel.innerHTML = data.businesses.map(b => `<option value="${b.id}">${b.name}</option>`).join('');
}

async function loadBranches() {
  const res = await fetch('/dsir/public/api/admin/branches.php');
  const data = await res.json();
  const tb = document.querySelector('#bTable tbody');
  tb.innerHTML = data.branches.map(b => `
    <tr>
      <td>${b.id}</td><td>${b.business_name}</td><td>${b.name}</td><td>${b.code}</td><td>${b.address ?? ''}</td>
      <td><button class="btn btn-sm btn-outline-primary edit" data-id="${b.id}">Edit</button></td>
      <td><button class="btn btn-sm btn-outline-danger del" data-id="${b.id}">Delete</button></td>
    </tr>
  `).join('');
  document.querySelectorAll('.edit').forEach(btn => btn.addEventListener('click', () => {
    const br = data.branches.find(x => x.id == btn.dataset.id);
    document.getElementById('b_id').value = br.id;
    document.getElementById('b_business').value = br.business_id;
    document.getElementById('b_name').value = br.name;
    document.getElementById('b_code').value = br.code;
    document.getElementById('b_addr').value = br.address ?? '';
  }));
  document.querySelectorAll('.del').forEach(btn => btn.addEventListener('click', async () => {
    if (!confirm('Delete branch?')) return;
    await fetch('/dsir/public/api/admin/branches.php', { method:'DELETE', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id:+btn.dataset.id })});
    await loadBranches();
  }));
}

document.getElementById('saveBranch').addEventListener('click', async e => {
  e.preventDefault();
  const payload = {
    id: document.getElementById('b_id').value || null,
    business_id: +document.getElementById('b_business').value,
    name: document.getElementById('b_name').value,
    code: document.getElementById('b_code').value,
    address: document.getElementById('b_addr').value
  };
  await fetch('/dsir/public/api/admin/branches.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
  document.getElementById('bForm').reset();
  await loadBranches();
});

document.getElementById('resetForm').addEventListener('click', () => document.getElementById('bForm').reset());

(async function(){ await loadBusinesses(); await loadBranches(); })();
</script>
</body>
</html>

