<?php
// Auth + role checks + maintenance hard-lock + auto overlay + clickable version footer (base-path aware)

if (session_status() === PHP_SESSION_NONE) {
  ini_set('session.use_strict_mode', '1');
  ini_set('session.cookie_httponly', '1');
  ini_set('session.cookie_samesite', 'Lax');
  session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/url.php';

function wantsJson(): bool {
  $h = $_SERVER['HTTP_ACCEPT'] ?? '';
  $uri = $_SERVER['REQUEST_URI'] ?? '';
  return (stripos($h, 'application/json') !== false) || (strpos($uri, app_base().'/api/') === 0);
}

function fail($code, $msg) {
  http_response_code($code);
  if (wantsJson()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $msg]);
  } else {
    echo "<!doctype html><meta charset='utf-8'><title>Error</title>
      <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'>
      <div class='container py-5'><div class='alert alert-danger'>$msg</div></div>";
  }
  exit;
}

function currentUser() { return $_SESSION['user'] ?? null; }

function requireAuth() {
  if (!currentUser()) {
    if (wantsJson()) { fail(401, 'Not authenticated'); }
    redirect_to('login.php');
  }
  enforceMaintenanceGuard();
  maybeSetupUiInjection();
}

function requireRole($roles) {
  requireAuth();
  $u = currentUser();
  $ok = is_array($roles) ? in_array($u['role'], $roles, true) : ($u['role'] === $roles);
  if (!$ok) fail(403, 'Insufficient role');
}

function enforceMaintenanceGuard() {
  static $checked = false; if ($checked) return; $checked = true;
  $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
  if (in_array($method, ['GET','HEAD','OPTIONS'], true)) return;
  $user = currentUser();
  if ($user && $user['role'] === 'programmer') return;
  try {
    $pdo = getDB();
    $val = $pdo->query("SELECT v FROM settings WHERE k='feature.maintenance'")->fetchColumn();
  } catch (Throwable $e) { return; }
  if ($val === '1') fail(503, 'Maintenance mode is ON. Writes are temporarily disabled.');
}

function maybeSetupUiInjection() {
  if (wantsJson()) return;
  static $started = false; if ($started) return; $started = true;

  $pdo = getDB();
  $u = currentUser(); $role = $u['role'] ?? '';

  $flag = '0';
  try { $flag = (string)$pdo->query("SELECT v FROM settings WHERE k='feature.maintenance'")->fetchColumn() ?: '0'; } catch(Throwable $e) {}
  $maintenanceHtml = ($flag === '1' && $role !== 'programmer') ? maintenance_overlay_html() : '';

  $last = $pdo->query("SELECT id, version, title, note, file_path, applied_at FROM patches ORDER BY applied_at DESC, id DESC LIMIT 1")->fetch();
  $version   = $last['version']    ?? 'v0.0.0';
  $appliedAt = $last['applied_at'] ?? '';
  $title     = $last['title']      ?? '';
  $note      = $last['note']       ?? '';
  $filePath  = $last['file_path']  ?? null;

  $footerHtml = version_footer_with_modal_html($version, $appliedAt, $title, $note, $filePath);

  ob_start(function ($html) use ($maintenanceHtml, $footerHtml) {
    if ($maintenanceHtml) {
      if (preg_match('/<body[^>]*>/i', $html, $m, PREG_OFFSET_CAPTURE)) {
        $pos = $m[0][1] + strlen($m[0][0]);
        $html = substr($html, 0, $pos) . $maintenanceHtml . substr($html, $pos);
      } else {
        $html = $maintenanceHtml . $html;
      }
    }
    if (stripos($html, '</body>') !== false) {
      $html = str_ireplace('</body>', $footerHtml . '</body>', $html);
    } else {
      $html .= $footerHtml;
    }
    return $html;
  });
}

function maintenance_overlay_html(): string {
  return <<<HTML
  <style>
    .maint-banner{position:sticky;top:0;z-index:2000;background:#fff3cd;color:#664d03;border-bottom:1px solid #ffec9f;padding:.6rem 1rem;text-align:center;font-size:.95rem}
    .maint-overlay{position:fixed;inset:0;z-index:1500;background:rgba(255,255,255,.6);pointer-events:auto}
    .maint-card{position:fixed;left:50%;top:70px;transform:translateX(-50%);z-index:2001;background:#fff;border:1px solid #eee;border-radius:.5rem;padding:.75rem 1rem;box-shadow:0 10px 30px rgba(0,0,0,.08);font-size:.9rem}
    .maint-muted{color:#6c757d;font-size:.85rem}
  </style>
  <div class="maint-banner"><strong>Maintenance mode</strong> is ON — changes are temporarily disabled. You can still browse data.</div>
  <div class="maint-overlay" aria-hidden="true"></div>
  <div class="maint-card">
    <div><strong>Read‑only</strong>: buttons and inputs are disabled until maintenance ends.</div>
    <div class="maint-muted">If you think this is a mistake, contact the programmer.</div>
  </div>
  <script>
    (function(){
      const blockSubmit = e => e.preventDefault();
      const disable = el => { try{ el.setAttribute('disabled','disabled'); el.classList.add('disabled'); }catch(_){} };
      document.querySelectorAll('form').forEach(f => f.addEventListener('submit', blockSubmit, true));
      document.querySelectorAll('button,input,select,textarea').forEach(disable);
      document.querySelectorAll('a.btn, a[class*="btn-"]').forEach(a => {
        a.addEventListener('click', e => e.preventDefault(), true);
        a.classList.add('disabled'); a.setAttribute('aria-disabled','true');
      });
    })();
  </script>
  HTML;
}

function version_footer_with_modal_html(string $version, string $appliedAt, string $title, string $note, ?string $filePath): string {
  $date = $appliedAt ? date('Y-m-d H:i', strtotime($appliedAt)) : '';
  $safeTitle = htmlspecialchars($title ?: 'Build Details', ENT_QUOTES, 'UTF-8');
  $safeVersion = htmlspecialchars($version, ENT_QUOTES, 'UTF-8');
  $safeDate = htmlspecialchars($date, ENT_QUOTES, 'UTF-8');
  $safeNote = nl2br(htmlspecialchars($note ?: 'No notes provided.', ENT_QUOTES, 'UTF-8'));
  $fileLink = $filePath ? '<a href="'.htmlspecialchars($filePath, ENT_QUOTES, 'UTF-8').'" target="_blank" rel="noopener">Download attached file</a>' : '';

  return <<<HTML
  <style>
    .build-footer{position:fixed;bottom:0;left:0;right:0;z-index:2000;background:#f8f9fa;border-top:1px solid #ddd;padding:.25rem .75rem;font-size:.8rem;text-align:right;color:#555}
    .build-footer a{color:#0d6efd;text-decoration:none}
    .build-footer a:hover{text-decoration:underline}
    .patch-modal-backdrop{position:fixed;inset:0;z-index:3000;background:rgba(0,0,0,.35);display:none}
    .patch-modal{position:fixed;left:50%;top:50%;transform:translate(-50%,-50%);z-index:3001;background:#fff;min-width:320px;max-width:640px;width:90%;
      border-radius:.5rem;box-shadow:0 20px 50px rgba(0,0,0,.2);display:none}
    .patch-modal header{display:flex;justify-content:space-between;align-items:center;padding:.75rem 1rem;border-bottom:1px solid #eee}
    .patch-modal .patch-body{padding:1rem;max-height:60vh;overflow:auto;font-size:.9rem}
    .patch-modal .meta{color:#6c757d;font-size:.8rem}
    .patch-close{border:none;background:transparent;font-size:1.2rem;line-height:1;cursor:pointer}
    .patch-note{white-space:pre-wrap}
  </style>
  <div class="build-footer">
    Build: <a href="#" id="patchOpen" title="View patch notes">{$safeVersion}</a> <span style="color:#888">{$safeDate}</span>
  </div>
  <div class="patch-modal-backdrop" id="patchBackdrop"></div>
  <div class="patch-modal" id="patchModal" role="dialog" aria-modal="true" aria-labelledby="patchTitle">
    <header>
      <div>
        <div id="patchTitle"><strong>{$safeTitle}</strong></div>
        <div class="meta">Version: {$safeVersion} • {$safeDate}</div>
      </div>
      <button class="patch-close" id="patchClose" aria-label="Close">×</button>
    </header>
    <div class="patch-body">
      <div class="patch-note">{$safeNote}</div>
      <div style="margin-top:.75rem">{$fileLink}</div>
    </div>
  </div>
  <script>
    (function(){
      const open = document.getElementById('patchOpen');
      const modal = document.getElementById('patchModal');
      const back  = document.getElementById('patchBackdrop');
      const close = document.getElementById('patchClose');
      if (!open || !modal || !back || !close) return;
      const show = () => { modal.style.display='block'; back.style.display='block'; document.body.style.overflow='hidden'; };
      const hide = () => { modal.style.display='none'; back.style.display='none'; document.body.style.overflow=''; };
      open.addEventListener('click', e => { e.preventDefault(); show(); });
      close.addEventListener('click', hide);
      back.addEventListener('click', hide);
      document.addEventListener('keydown', e => { if (e.key === 'Escape') hide(); });
    })();
  </script>
  HTML;
}
