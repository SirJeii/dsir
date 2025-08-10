<?php
require_once __DIR__ . '/../../../src/auth.php';
requireRole('admin');
require_once __DIR__ . '/../../../src/db.php';
$pdo = getDB();

/* Businesses list */
$biz = $pdo->query("SELECT id,name FROM businesses ORDER BY name")->fetchAll();

/* Sales last 14 days by branch */
$labels = [];
for ($i=13; $i>=0; $i--) { $labels[] = date('Y-m-d', strtotime("-$i day")); }

$branches = $pdo->query("
  SELECT b.id, b.name, biz.name AS business_name
  FROM branches b JOIN businesses biz ON biz.id = b.business_id
  ORDER BY biz.name, b.name
")->fetchAll();

$dailyMap = [];
foreach ($branches as $b) {
  $dailyMap[$b['id']] = array_fill_keys($labels, 0);
}

$rows = $pdo->query("
  SELECT dr.branch_id, dr.report_date, SUM(dl.sales_cents) AS sales
  FROM dsir_reports dr
  JOIN dsir_lines dl ON dl.report_id = dr.id
  WHERE dr.report_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 13 DAY) AND CURDATE()
  GROUP BY dr.branch_id, dr.report_date
")->fetchAll();

foreach ($rows as $r) {
  if (isset($dailyMap[$r['branch_id']][$r['report_date']])) {
    $dailyMap[$r['branch_id']][$r['report_date']] = (int)$r['sales'];
  }
}

$sales = [
  'labels' => $labels,
  'branches' => array_map(function($b) use ($dailyMap){
    return [
      'branch_id' => $b['id'],
      'branch_name' => $b['name'],
      'daily_sales' => array_values($dailyMap[$b['id']])
    ];
  }, $branches)
];

/* Low stock (top 10) – naive sample using inventory + reorder level */
$low = $pdo->query("
  SELECT b.name AS branch_name, p.name AS product_name, i.qty
  FROM inventory i
  JOIN branches b ON b.id = i.branch_id
  JOIN products p ON p.id = i.product_id
  WHERE i.qty <= p.reorder_level
  ORDER BY i.qty ASC
  LIMIT 10
")->fetchAll();

echo json_encode([
  'businesses' => $biz,
  'sales' => $sales,
  'low_stock' => $low
]);
