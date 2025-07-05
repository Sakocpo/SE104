<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'kitchen') {
  http_response_code(403);
  echo json_encode(['error'=>'Forbidden']);
  exit;
}

$optLabels = [];
$res = $connection->query("SELECT id,label FROM options");
while ($row = $res->fetch_assoc()) {
    $optLabels[(int)$row['id']] = $row['label'];
}

$sql = "
  SELECT
    o.id           AS order_id,
    t.table_name   AS table_name,
    o.created_at   AS created_at,
    oi.product_id,
    p.name         AS product_name,
    oi.quantity    AS quantity,
    oi.options     AS options,
    oi.note        AS note
  FROM order_items oi
  JOIN orders o    ON oi.order_id = o.id
  JOIN products p  ON oi.product_id = p.id
  JOIN tables t    ON o.table_id = t.id
  WHERE oi.served = 0
    AND o.status  = 'pending'
    AND o.status  != 'deleted'
    AND o.created_at BETWEEN CONCAT(CURDATE(),' 00:00:00') AND CONCAT(CURDATE(),' 23:59:59')
  ORDER BY o.created_at ASC, oi.id ASC
";
$result = $connection->query($sql);

$orders = [];
while ($r = $result->fetch_assoc()) {
  $oid = (int)$r['order_id'];
  if (!isset($orders[$oid])) {
    $orders[$oid] = [
      'order_id'   => $oid,
      'table_name' => $r['table_name'],
      'created_at' => $r['created_at'],
      'items'      => []
    ];
  }

  $labels = [];
  $optsRaw = json_decode($r['options'], true);
  if (json_last_error() === JSON_ERROR_NONE && is_array($optsRaw)) {
      $ids = $optsRaw;
  } else {
      $ids = explode(',', $r['options']);
  }
  foreach ($ids as $optId) {
      $i = intval($optId);
      if (isset($optLabels[$i])) {
          $labels[] = $optLabels[$i];
      }
  }

  $orders[$oid]['items'][] = [
    'product_name' => $r['product_name'],
    'quantity'     => (int)$r['quantity'],
    'options'      => $labels,
    'note'         => $r['note'] ?? ''
  ];
}

header('Content-Type: application/json');
echo json_encode(array_values($orders), JSON_UNESCAPED_UNICODE);
