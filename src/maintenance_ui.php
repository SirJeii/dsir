<?php
// UI-only maintenance helper. Include this on HTML pages AFTER requireAuth()/requireRole().
// Example:
//   require_once __DIR__ . '/auth.php';
//   requireRole('admin');
//   require_once __DIR__ . '/maintenance_ui.php';
//   renderMaintenanceOverlay();

require_once __DIR__ . '/db.php';

function renderMaintenanceOverlay(): void {
  $user = $_SESSION['user'] ?? null;
  $role = $user['role'] ?? '';
  // Programmers bypass overlay
  if ($role === 'programmer') return;

  try {
    $pdo = getDB();
    $flag = $pdo->query("SELECT v FROM settings WHERE k='feature.maintenance'")->fetchColumn();
  } catch (Throwable $e) {
    $flag = '0';
  }

  if ($flag !== '1') return;

  // Output banner + overlay + client-side read-only guard
  ?>
  <style>
    .maint-banner {
      position: sticky; top: 0; z-index: 2000;
      background: #fff3cd; color: #664d03; border-bottom: 1px solid #ffec9f;
      padding: .6rem 1rem; text-align: center; font-size: .95rem;
    }
    .maint-overlay {
      position: fixed; inset: 0; z-index: 1500;
      background: rgba(255,255,255,.6);
      pointer-events: auto;
    }
    .maint-card {
      position: fixed; left: 50%; top: 70px; transform: translateX(-50%);
      z-index: 2001; background: #fff; border: 1px solid #eee; border-radius: .5rem;
      padding: .75rem 1rem; box-shadow: 0 10px 30px rgba(0,0,0,.08);
      font-size: .9rem;
    }
    .maint-muted { color: #6c757d; font-size: .85rem; }
  </style>

  <div class="maint-banner">
    <strong>Maintenance mode</strong> is ON — changes are temporarily disabled. You can still browse data.
  </div>
  <div class="maint-overlay" aria-hidden="true"></div>
  <div class="maint-card">
    <div><strong>Read‑only</strong>: buttons and inputs are disabled until maintenance ends.</div>
    <div class="maint-muted">If you think this is a mistake, contact the programmer.</div>
  </div>

  <script>
    // Soft-disable interactive controls (client-side UX)
    (function(){
      const block = (el) => {
        try {
          el.setAttribute('disabled','disabled');
          el.classList.add('disabled');
          if (el.tagName === 'A') { el.addEventListener('click', e => e.preventDefault(), true); }
          if (el.tagName === 'FORM') { el.addEventListener('submit', e => e.preventDefault(), true); }
        } catch(_) {}
      };
      const sel = [
        'button', 'input', 'select', 'textarea',
        'a.btn', 'a.link-danger', 'a.link-warning', 'a.link-primary', 'form'
      ];
      document.querySelectorAll(sel.join(',')).forEach(block);

      // Re-enable obvious "navigation" anchors that aren’t actions (have href and no .btn)
      document.querySelectorAll('a[href]:not(.btn)').forEach(a => {
        a.classList.remove('disabled');
        a.removeAttribute('disabled');
        a.onclick = null;
      });

      // Mark tables as read-only
      document.querySelectorAll('table').forEach(t => t.classList.add('table-secondary'));
    })();
  </script>
  <?php
}
