<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'waiter') {
    echo json_encode([]);
    exit;
}

$since = $_GET['since'] ?? '';
if (!$since) {
    $since = date('Y-m-d H:i:s', strtotime('-1 minute'));
}

$stmt = $connection->prepare("
  SELECT
    o.id AS order_id,
    t.table_name,
    p.name AS product_name,
    oi.served
  FROM order_items oi
  JOIN orders o ON oi.order_id = o.id
  JOIN tables t ON o.table_id = t.id
  JOIN products p ON oi.product_id = p.id
  WHERE oi.served >= ?
  ORDER BY oi.served DESC
");
$stmt->bind_param("s", $since);
$stmt->execute();
$recent = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$grouped = [];
foreach ($recent as $item) {
    $oid = $item['order_id'];
    $grouped[$oid]['items'][] = $item;
    $grouped[$oid]['table'] = $item['table_name'];
}

$results = [];
foreach ($grouped as $oid => $group) {
    $res = $connection->prepare("SELECT COUNT(*) AS total, SUM(served > 0) AS done FROM order_items WHERE order_id = ?");
    $res->bind_param("i", $oid);
    $res->execute();
    $status = $res->get_result()->fetch_assoc();
    $res->close();

    if ($status['done'] == $status['total']) {
        $results[] = ['type' => 'order', 'table' => $group['table']];
    } else {
        foreach ($group['items'] as $i) {
            $results[] = ['type' => 'item', 'table' => $i['table_name'], 'product' => $i['product_name']];
        }
    }
}

echo json_encode($results);
