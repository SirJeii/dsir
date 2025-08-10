<?php
require_once __DIR__ . '/../../src/db.php';
$pdo = getDB();
echo "OK: connected to ".DB_NAME;
