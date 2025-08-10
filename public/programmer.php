<?php
require_once __DIR__ . '/../src/auth.php';
requireRole('programmer');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Programmer – Tools</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-light bg-white shadow-sm">
  <div class="container">
    <span class="navbar-brand h5 mb-0">Programmer Tools</span>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="/dsir/public/admin_dashboard.php">Admin</a>
      <a class="btn btn-outline-danger btn-sm" href="/dsir/public/logout.php">Logout</a>
    </div>
  </div>
</nav>

<div class="container my-3">
  <ul class="nav nav-tabs" id="progTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#patches" type="button">Patches</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings" type="button">Settings</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#backups" type="button">Backups</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#logs" type="button">Audit Logs</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#diag" type="button">Diagnostics</button></li>
  </ul>
  <div class="tab-content border border-top-0 rounded-bottom bg-white p-3">
    <div class="tab-pane fade show active" id="patches">
      <div class="row g-3">
        <div class="col-lg-6">
          <h6>Record Patch</h6>
          <form id="patchForm" class="row g-2" enctype="multipart/form-data">
            <div class="col-md-4"><input class="form-control" id="p_version" placeholder="1.2.0" required></div>
            <div class="col-md-8"><input class="form-control" id="p_title" placeholder="Title" required></div>
            <div class="col-12"><textarea class="form-control" id="p_note" rows="3" placeholder="Notes"></textarea></div>
            <div class="col-md-8"><input type="file" class="form-control" id="p_file" accept=".sql,.php,.js,.zip,.txt"></div>
            <div class="col-md-4 d-grid"><button class="btn btn-primary" id="savePatch">Record Patch</button></div>
          </form>
        </div>
        <div class="col-lg-6">
          <h6>History</h6>
          <div class="table-responsive">
            <table class="table table-sm" id="patchTable">
              <thead><tr><th>ID</th><th>Version</th><th>Title</th><th>File</th><th>Applied</th><th>By</th></tr></thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="settings">
      <div class="row g-3">
        <div class="col-lg-6">
          <h6>Feature Flags</h6>
          <div class="form-check form-switch">
            <input class="form-check-input s-toggle" type="checkbox" data-key="feature.offline_mode" id="s_offline">
            <label class="form-check-label" for="s_offline">Offline mode</label>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input s-toggle" type="checkbox" data-key="feature.debug_mode" id="s_debug">
            <label class="form-check-label" for="s_debug">Debug mode</label>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input s-toggle" type="checkbox" data-key="feature.maintenance" id="s_maint">
            <label class="form-check-label" for="s_maint">Maintenance mode</label>
          </div>
        </div>
        <div class="col-lg-6">
          <h6>Custom</h6>
          <div class="d-flex gap-2">
            <input class="form-control" id="s_key" placeholder="custom.key">
            <input class="form-control" id="s_val" placeholder="value">
            <button class="btn btn-outline-primary" id="addSetting" type="button">Set</button>
          </div>
          <div class="table-responsive mt-3">
            <table class="table table-sm" id="settingsTable">
              <thead><tr><th>Key</th><th>Value</th><th>Updated</th></tr></thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="backups">
      <div class="row g-3">
        <div class="col-lg-5">
          <h6>Create Backup</h6>
          <button class="btn btn-primary" id="btnBackup">Generate Full SQL Backup</button>
          <div id="backupMsg" class="text-muted mt-2"></div>
          <hr>
          <h6>Restore Backup</h6>
          <form id="restoreForm" class="d-flex gap-2" enctype="multipart/form-data">
            <input type="file" class="form-control" id="restoreFile" accept=".sql" required>
            <button class="btn btn-danger">Restore</button>
          </form>
          <small class="text-muted">⚠️ Restores are destructive. Use with care.</small>
        </div>
        <div class="col-lg-7">
          <h6>Backup History</h6>
          <div class="table-responsive">
            <table class="table table-sm" id="backupTable">
              <thead><tr><th>When</th><th>File</th><th>Size</th><th>By</th></tr></thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="logs">
      <div class="d-flex gap-2 align-items-end">
        <div>
          <label class="form-label">Action</label>
          <input class="form-control" id="logAction" placeholder="e.g. LOGIN_SUCCESS">
        </div>
        <div>
          <label class="form-label">From</label>
          <input type="date" class="form-control" id="logFrom">
        </div>
        <div>
          <label class="form-label">To</label>
          <input type="date" class="form-control" id="logTo">
        </div>
        <button class="btn btn-outline-secondary" id="loadLogs">Filter</button>
      </div>
      <div class="table-responsive mt-3">
        <table class="table table-sm" id="logsTable">
          <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Entity</th><th>Entity ID</th><th>IP</th><th>Details</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>

    <div class="tab-pane fade" id="diag">
      <div id="diagBox" class="text-muted">Loading…</div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
async function loadPatches(){
  const r=await fetch('/dsir/public/api/programmer/patches.php'); const d=await r.json();
  document.querySelector('#patchTable tbody').innerHTML=d.items.map(x=>`
    <tr><td>${x.id}</td><td>${x.version}</td><td>${x.title}</td>
    <td>${x.file_path?`<a href="${x.file_path}" target="_blank">download</a>`:''}</td>
    <td>${x.applied_at??''}</td><td>${x.applied_by_name??''}</td></tr>`).join('');
}
document.getElementById('savePatch').addEventListener('click', async e=>{
  e.preventDefault();
  const fd=new FormData();
  fd.append('version', document.getElementById('p_version').value);
  fd.append('title', document.getElementById('p_title').value);
  fd.append('note', document.getElementById('p_note').value);
  const f=document.getElementById('p_file').files[0]; if (f) fd.append('file', f);
  const r=await fetch('/api/programmer/patches.php',{method:'POST',body:fd}); const o=await r.json();
  if(!r.ok){ alert(o.error||'Save failed'); return; }
  document.getElementById('patchForm').reset(); loadPatches();
});

async function loadSettings(){
  const r=await fetch('/dsir/public/api/programmer/settings.php'); const d=await r.json();
  const map=new Map(d.items.map(i=>[i.k,i.v]));
  document.querySelectorAll('.s-toggle').forEach(el=>{ el.checked = map.get(el.dataset.key)==='1'; });
  document.querySelector('#settingsTable tbody').innerHTML=d.items.map(i=>`<tr><td>${i.k}</td><td>${i.v}</td><td>${i.updated_at??''}</td></tr>`).join('');
}
document.querySelectorAll('.s-toggle').forEach(el=>{
  el.addEventListener('change', async ()=> {
    await fetch('/dsir/public/api/programmer/settings.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({k:el.dataset.key,v:el.checked?'1':'0'})});
    loadSettings();
  });
});
document.getElementById('addSetting').addEventListener('click', async ()=>{
  const k=document.getElementById('s_key').value.trim(), v=document.getElementById('s_val').value;
  if(!k) return alert('Key required'); await fetch('/dsir/public/api/programmer/settings.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({k,v})});
  document.getElementById('s_key').value=''; document.getElementById('s_val').value=''; loadSettings();
});

document.getElementById('btnBackup').addEventListener('click', async ()=>{
  const r=await fetch('/dsir/public/api/programmer/backup_now.php',{method:'POST'}); const o=await r.json();
  if(!r.ok){ alert(o.error||'Backup failed'); return; }
  document.getElementById('backupMsg').innerText=`Backup created: ${o.file} (${o.bytes} bytes)`;
  loadBackups();
});
async function loadBackups(){
  const r=await fetch('/dsir/public/api/programmer/list_backups.php'); const d=await r.json();
  document.querySelector('#backupTable tbody').innerHTML=d.items.map(b=>`
    <tr><td>${b.created_at}</td><td><a href="${b.file_path}" target="_blank">${b.file_path.split('/').pop()}</a></td><td>${b.file_size}</td><td>${b.created_by_name??''}</td></tr>
  `).join('');
}
document.getElementById('restoreForm').addEventListener('submit', async e=>{
  e.preventDefault();
  if(!confirm('This will overwrite the database. Continue?')) return;
  const fd=new FormData(); fd.append('file', document.getElementById('restoreFile').files[0]);
  const r=await fetch('/dsir/public/api/programmer/restore_backup.php',{method:'POST',body:fd}); const o=await r.json();
  if(!r.ok){ alert(o.error||'Restore failed'); return; }
  alert('Restore completed.');
});

document.getElementById('loadLogs').addEventListener('click', async ()=>{
  const a = document.getElementById('logAction').value.trim();
  const f = document.getElementById('logFrom').value || '';
  const t = document.getElementById('logTo').value || '';
  const q = new URLSearchParams({action:a, from:f, to:t});
  const r = await fetch('/dsir/public/api/programmer/audit_logs.php?'+q.toString());
  const d = await r.json();
  document.querySelector('#logsTable tbody').innerHTML = d.items.map(x=>`
    <tr><td>${x.created_at}</td><td>${x.user_name??''}</td><td>${x.action}</td>
        <td>${x.entity??''}</td><td>${x.entity_id??''}</td><td>${x.ip??''}</td>
        <td><pre class="m-0" style="white-space:pre-wrap;font-size:.75rem">${x.details??''}</pre></td></tr>`).join('');
});

async function loadDiag(){
  const r=await fetch('/dsir/public/api/programmer/diagnostics.php'); const d=await r.json();
  document.getElementById('diagBox').innerHTML = `
    <div>PHP ${d.php.version} • Upload max ${d.php.upload_max} • Post max ${d.php.post_max}</div>
    <div>Storage free: ${d.storage.free_gb} GB</div>
    <div>Counts — Biz: ${d.db.counts.businesses}, Branches: ${d.db.counts.branches}, Products: ${d.db.counts.products}, Reports: ${d.db.counts.reports}</div>`;
}

(async function(){ await loadPatches(); await loadSettings(); await loadBackups(); await loadDiag(); })();
</script>
</body>
</html>

