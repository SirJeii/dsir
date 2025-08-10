<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/url.php';
start_session_once();

if (!empty($_SESSION['user'])) {
    redirect_to('index.php');
}
$apiLogin = app_href('api/login.php');
$after    = app_href('index.php');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login – MBIS</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    body { background:#f8f9fa; }
    .login-card { max-width: 400px; margin: 5rem auto; }
    pre#raw { display:none; max-height:160px; overflow:auto; background:#f6f8fa; padding:.5rem; border:1px solid #e1e4e8; }
  </style>
</head>
<body>
<div class="login-card card shadow-sm">
  <div class="card-body">
    <h4 class="card-title mb-3">Sign in</h4>
    <form id="loginForm" class="vstack gap-2">
      <input type="email" id="email" class="form-control" placeholder="Email" required autocomplete="username">
      <input type="password" id="password" class="form-control" placeholder="Password" required autocomplete="current-password">
      <button class="btn btn-primary w-100" type="submit" id="btnSignIn">Sign in</button>
    </form>
    <div id="msg" class="mt-2 small"></div>
    <pre id="raw" class="mt-2 small"></pre>
  </div>
</div>

<script>
(function(){
  const API  = <?= json_encode($apiLogin) ?>;
  const AFTER= <?= json_encode($after) ?>;

  const form = document.getElementById('loginForm');
  const btn  = document.getElementById('btnSignIn');
  const msg  = document.getElementById('msg');
  const raw  = document.getElementById('raw');

  const setMsg = (text, ok=false) => {
    msg.textContent = text || '';
    msg.className = 'mt-2 small ' + (ok ? 'text-success' : 'text-danger');
  };

  form.addEventListener('submit', async (e)=>{
    e.preventDefault();
    setMsg(''); raw.style.display='none'; raw.textContent='';
    btn.disabled = true; btn.textContent = 'Signing in...';

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

    try {
      const r = await fetch(API, {
        method: 'POST',
        headers: {'Content-Type':'application/json','Accept':'application/json'},
        body: JSON.stringify({email, password})
      });
      const text = await r.text(); let data=null; try{ data=JSON.parse(text);}catch{}
      if (!r.ok || !data || data.ok !== true) {
        setMsg(`Login failed (HTTP ${r.status}).`); raw.textContent = text || '(empty response)'; raw.style.display='block';
        btn.disabled = false; btn.textContent = 'Sign in'; return;
      }
      setMsg('Logged in. Redirecting…', true);
      location.href = AFTER;
    } catch (err) {
      console.error(err); setMsg('Network error.'); btn.disabled=false; btn.textContent='Sign in';
    }
  });
})();
</script>
</body>
</html>
