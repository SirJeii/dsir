<?php
require_once __DIR__ . '/../../../src/auth.php';
requireRole('admin');
require_once __DIR__ . '/../../../src/db.php';
$pdo = getDB();

/* sales by business last 30 days */
$biz = $pdo->query("
SELECT biz.name AS business, SUM(dl.sales_cents) AS sales
FROM dsir_reports dr
JOIN dsir_lines dl ON dl.report_id = dr.id
JOIN branches b ON b.id = dr.branch_id
JOIN businesses biz ON biz.id = b.business_id
WHERE dr.report_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()
GROUP BY biz.name ORDER BY sales DESC
")->fetchAll();

/* top products by sales last 30 days */
$top = $pdo->query("
SELECT p.name AS product, SUM(dl.sales_cents) AS sales
FROM dsir_lines dl
JOIN dsir_reports dr ON dr.id = dl.report_id
JOIN products p ON p.id = dl.product_id
WHERE dr.report_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()
GROUP BY p.name ORDER BY sales DESC LIMIT 10
")->fetchAll();

echo json_encode(['business_sales'=>$biz,'top_products'=>$top]);
