<?php
/**
 * Database bootstrap – single shared PDO + helpers
 * Usage:
 *   require_once __DIR__ . '/db.php';
 *   $pdo = getDB();
 */

declare(strict_types=1);

/* ---------------------------------------------------
 | 1) Configure your DB credentials here
 |    (XAMPP default: root with no password)
 * --------------------------------------------------- */
if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', '3306');
if (!defined('DB_NAME')) define('DB_NAME', 'mbis');        // <-- your database name
if (!defined('DB_USER')) define('DB_USER', 'root');        // XAMPP default user
if (!defined('DB_PASS')) define('DB_PASS', '');            // XAMPP default: empty
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

/* Optional: toggle to true to see exceptions while developing */
if (!defined('APP_DEBUG')) define('APP_DEBUG', true);

/* ---------------------------------------------------
 | 2) PDO singleton
 * --------------------------------------------------- */
static $__PDO = null;

/**
 * Return the singleton PDO connection (creates on first call).
 */
function getDB(): PDO {
  global $__PDO;

  if ($__PDO instanceof PDO) {
    return $__PDO;
  }

  $dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
  );

  $options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
  ];

  try {
    $__PDO = new PDO($dsn, DB_USER, DB_PASS, $options);
    // Ensure strict SQL mode for safer math (optional, comment if your host disallows)
    $__PDO->exec("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
  } catch (Throwable $e) {
    // In production you might want to log instead of echo
    if (APP_DEBUG) {
      http_response_code(500);
      header('Content-Type: text/plain; charset=utf-8');
      echo "DB connection failed:\n" . $e->getMessage();
    }
    exit; // hard exit so downstream code doesn't run with null $pdo
  }

  return $__PDO;
}

/* ---------------------------------------------------
 | 3) Tiny helpers (optional)
 * --------------------------------------------------- */

/**
 * Run a SELECT and return all rows.
 */
function db_all(string $sql, array $params = []): array {
  $pdo = getDB();
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  return $stmt->fetchAll();
}

/**
 * Run a SELECT and return the first row or null.
 */
function db_one(string $sql, array $params = []): ?array {
  $pdo = getDB();
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $row = $stmt->fetch();
  return $row === false ? null : $row;
}

/**
 * Run a scalar query (first column of the first row) or null.
 */
function db_scalar(string $sql, array $params = []) {
  $pdo = getDB();
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $v = $stmt->fetchColumn();
  return $v === false ? null : $v;
}
