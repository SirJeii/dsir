<?php
require_once __DIR__ . '/../src/auth.php';
requireRole('admin');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin – Users</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm">
  <div class="container">
    <a class="navbar-brand h5 mb-0" href="/admin_dashboard.php">← Admin</a>
    <a class="btn btn-outline-danger btn-sm" href="/logout.php">Logout</a>
  </div>
</nav>

<div class="container my-3">
  <div class="card mb-3">
    <div class="card-header">Add / Edit User</div>
    <div class="card-body">
      <form id="userForm" class="row g-2">
        <input type="hidden" id="u_id">
        <div class="col-md-3">
          <label class="form-label">Name</label>
          <input class="form-control" id="u_name" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" id="u_email" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Role</label>
          <select class="form-select" id="u_role">
            <option>programmer</option>
            <option>admin</option>
            <option>auditor</option>
            <option>accountant</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Branch ID (optional)</label>
          <input type="number" class="form-control" id="u_branch">
        </div>
        <div class="col-md-2">
          <label class="form-label">Password</label>
          <input type="password" class="form-control" id="u_pass" placeholder="(leave blank to keep)">
        </div>
        <div class="col-12">
          <button class="btn btn-primary" id="saveUser">Save</button>
          <button class="btn btn-secondary" id="resetForm" type="button">Reset</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>Users</span>
      <small class="text-muted">Click a user to edit, or manage product access</small>
    </div>
    <div class="card-body table-responsive">
      <table class="table table-sm table-striped align-middle">
        <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Branch</th><th>Access</th><th>Delete</th></tr></thead>
        <tbody id="usersBody"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Access modal -->
<div class="modal fade" id="accessModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Product Access for <span id="accUserName"></span></h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <div id="impactAlert"></div>
      <div class="table-responsive">
        <table class="table table-sm">
          <thead><tr><th>Product</th><th>SRP</th><th>Has Access?</th></tr></thead>
          <tbody id="accessBody"></tbody>
        </table>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      <button class="btn btn-primary" id="saveAccess">Save Access</button>
    </div>
  </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const peso = c => `₱${(c/100).toFixed(2)}`;
let accessUserId = null;
let productsCache = [];

async function loadUsers() {
  const res = await fetch('/api/admin/users.php');
  const data = await res.json();
  const tbody = document.getElementById('usersBody');
  tbody.innerHTML = data.users.map(u => `
    <tr>
      <td>${u.id}</td>
      <td><a href="#" class="editUser" data-id="${u.id}">${u.name}</a></td>
      <td>${u.email}</td>
      <td>${u.role}</td>
      <td>${u.branch_id ?? ''}</td>
      <td><button class="btn btn-sm btn-outline-primary manageAccess" data-id="${u.id}" data-name="${u.name}">Manage</button></td>
      <td><button class="btn btn-sm btn-outline-danger delUser" data-id="${u.id}">Delete</button></td>
    </tr>
  `).join('');

  document.querySelectorAll('.editUser').forEach(a => a.addEventListener('click', async e => {
    e.preventDefault();
    const id = +a.dataset.id;
    const u = data.users.find(x => x.id === id);
    document.getElementById('u_id').value = u.id;
    document.getElementById('u_name').value = u.name;
    document.getElementById('u_email').value = u.email;
    document.getElementById('u_role').value = u.role;
    document.getElementById('u_branch').value = u.branch_id ?? '';
    document.getElementById('u_pass').value = '';
  }));

  document.querySelectorAll('.delUser').forEach(b => b.addEventListener('click', async () => {
    if (!confirm('Delete this user?')) return;
    await fetch('/api/admin/users.php', { method:'DELETE', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id:+b.dataset.id })});
    await loadUsers();
  }));

  document.querySelectorAll('.manageAccess').forEach(b => b.addEventListener('click', async () => {
    accessUserId = +b.dataset.id;
    document.getElementById('accUserName').innerText = b.dataset.name;

    const prodRes = await fetch('/api/admin/products.php'); const prod = await prodRes.json();
    productsCache = prod.products;
    const accRes = await fetch('/api/admin/users.php?access_user='+accessUserId); const acc = await accRes.json();

    const body = document.getElementById('accessBody');
    const set = new Set(acc.access.map(a=>a.product_id));
    body.innerHTML = productsCache.map(p => `
      <tr>
        <td>${p.name}</td>
        <td>${peso(p.srp_cents)}</td>
        <td><input type="checkbox" class="accChk" data-id="${p.id}" ${set.has(p.id)?'checked':''}></td>
      </tr>
    `).join('');
    document.getElementById('impactAlert').innerHTML = '';

    const modal = new bootstrap.Modal(document.getElementById('accessModal')); modal.show();
  }));
}

document.getElementById('saveUser').addEventListener('click', async e => {
  e.preventDefault();
  const payload = {
    id: document.getElementById('u_id').value || null,
    name: document.getElementById('u_name').value,
    email: document.getElementById('u_email').value,
    role: document.getElementById('u_role').value,
    branch_id: document.getElementById('u_branch').value || null,
    password: document.getElementById('u_pass').value || null
  };
  await fetch('/api/admin/users.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
  document.getElementById('userForm').reset();
  await loadUsers();
});

document.getElementById('resetForm').addEventListener('click', () => document.getElementById('userForm').reset());

document.getElementById('saveAccess').addEventListener('click', async () => {
  const checked = [...document.querySelectorAll('.accChk')].filter(c=>c.checked).map(c=>+c.dataset.id);

  // Preflight: ask server which Draft DSIRs would be impacted by revoking access
  const impactRes = await fetch('/api/admin/access_impact.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ user_id: accessUserId, new_product_ids: checked })
  });
  const impact = await impactRes.json();

  if (impact.impacted && impact.impacted.length) {
    const html = `
      <div class="alert alert-warning">
        <strong>Heads up:</strong> This change will remove access to product(s) used in <strong>${impact.impacted.length}</strong> Draft DSIR(s):
        <ul>${impact.impacted.map(i=>`<li>#${i.report_id} – ${i.branch_name} – ${i.date} ${i.shift} <br><small>Products: ${i.product_names.join(', ')}</small></li>`).join('')}</ul>
        If you continue, those products will be hidden from the user in those drafts. They can still submit, but lines with removed products will not be saved.
      </div>`;
    document.getElementById('impactAlert').innerHTML = html;
    if (!confirm('Continue saving access despite impact?')) return;
  }

  await fetch('/api/admin/users.php?set_access=1', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ user_id: accessUserId, product_ids: checked })
  });
  bootstrap.Modal.getInstance(document.getElementById('accessModal')).hide();
});

loadUsers();
</script>
</body>
</html>
