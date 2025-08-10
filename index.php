<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$public = ($base ? $base : '') . '/public/index.php';
header('Location: ' . $public, true, 302);
exit;
