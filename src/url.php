<?php
declare(strict_types=1);

/**
 * Returns the app base path (e.g. "", "/dsir/public").
 * No trailing slash; empty string if root.
 */
function app_base(): string {
  $dir = str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
  if ($dir === '/' || $dir === '\\' || $dir === '.') $dir = '';
  // ensure leading slash if not empty
  if ($dir !== '' && $dir[0] !== '/') $dir = '/'.$dir;
  // remove trailing slash
  return rtrim($dir, '/');
}

/**
 * Build an href under the app base (handles slashes).
 * Example: app_href('login.php') => "/dsir/public/login.php"
 */
function app_href(string $rel): string {
  $rel = ltrim($rel, '/');
  $base = app_base();
  return ($base === '' ? '' : $base) . '/' . $rel;
}

/** Redirect to a relative path under the app base and exit. */
function redirect_to(string $rel): void {
  header('Location: '.app_href($rel));
  exit;
}
