<?php
// Global config/bootstrap (safe to include anywhere)

declare(strict_types=1);

// Timezone
date_default_timezone_set('Asia/Manila');

// Error reporting (tune for prod)
if (!defined('APP_DEBUG')) {
  define('APP_DEBUG', true);
}
if (APP_DEBUG) {
  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL);
} else {
  ini_set('display_errors', '0');
  error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
}

/**
 * Start session only once (legacy-safe).
 * Also exposes start_session_once() to match older includes.
 */
function start_session_once(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) {
    // Set cookie attributes BEFORE session_start (won't warn if not yet active)
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    // PHP 7.3+ supports "Lax" here; older versions just ignore
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
  }
}

// If some scripts include config.php very early, it’s safe to start session here:
start_session_once();

// (Optional) App paths
if (!defined('APP_ROOT')) define('APP_ROOT', dirname(__DIR__));   // e.g., C:/xampp/htdocs/dsir
if (!defined('APP_PUBLIC')) define('APP_PUBLIC', APP_ROOT . '/public');

// DB config is handled in src/db.php. Do not put secrets here if repo is public.
