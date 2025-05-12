<?php
session_start();
require "config.php";

if ($_SERVER['REQUEST_METHOD']!=='POST'
 || !isset($_SESSION['user'],$_SESSION['user']['role'])
 || $_SESSION['user']['role']!=='waiter') {
  http_response_code(403); exit;
}

$data = json_decode(file_get_contents('php://input'),true);
$table_id = intval($data['table_id'] ?? 0);
$items    = $data['items']    ?? [];

if (!$table_id || !$items) {
  echo json_encode(['success'=>false,'error'=>'No items']); exit;
}

$connection->begin_transaction();
try {
  // 0) see if this table already has an open order
  $stmt0 = $connection->prepare("SELECT current_order_id FROM tables WHERE id=?");
  $stmt0->bind_param("i",$table_id);
  $stmt0->execute();
  $row0 = $stmt0->get_result()->fetch_assoc();
  $stmt0->close();

  if (!empty($row0['current_order_id'])) {
    // append to existing
    $order_id = intval($row0['current_order_id']);
  } else {
    // create a new order
    $stmt1 = $connection->prepare("INSERT INTO orders (table_id) VALUES (?)");
    $stmt1->bind_param("i",$table_id);
    $stmt1->execute();
    $order_id = $stmt1->insert_id;
    $stmt1->close();
    // mark table occupied & set current_order_id
    $stmt2 = $connection->prepare("
      UPDATE tables
         SET status='occupied', current_order_id=?
       WHERE id=?
    ");
    $stmt2->bind_param("ii",$order_id,$table_id);
    $stmt2->execute();
    $stmt2->close();
  }

  // 2) insert each new item
  $stmt3 = $connection->prepare("
    INSERT INTO order_items
      (order_id, product_id, options, quantity, served)
    VALUES (?,?,?,?,0)
  ");
  foreach ($items as $it) {
    $pid = intval($it['product_id']);
    $opts = implode(',', array_map('intval',$it['options']));
    $qty = intval($it['quantity']);
    $stmt3->bind_param("iisi", $order_id, $pid, $opts, $qty);
    $stmt3->execute();
  }
  $stmt3->close();

  $connection->commit();
  echo json_encode(['success'=>true]);
} catch (Exception $e) {
  $connection->rollback();
  echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
