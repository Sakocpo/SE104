<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD']!=='POST'
 || !isset($_SESSION['user'],$_SESSION['user']['role'])
 || $_SESSION['user']['role']!=='waiter') {
  http_response_code(403);
  exit(json_encode(['success'=>false,'error'=>'Forbidden']));
}

$data = json_decode(file_get_contents('php://input'), true);
$t = intval($data['table_id'] ?? 0);
$paid = floatval($data['paid'] ?? 0);
if (!$t) {
  exit(json_encode(['success'=>false,'error'=>'No table']));
}

// 1️⃣ mark order as paid
// fetch order_id
$row = $connection->prepare("SELECT current_order_id FROM tables WHERE id=?");
$row->bind_param("i",$t);
$row->execute();
$o = $row->get_result()->fetch_assoc()['current_order_id'] ?? 0;
$row->close();

$connection->begin_transaction();
try {
  // orders.status = 'paid', record paid amount
  $u = $connection->prepare(
    "UPDATE orders SET status='paid', paid_amount=? WHERE id=?"
  );
  $u->bind_param("di",$paid,$o);
  $u->execute();
  $u->close();

  // free table
  $u = $connection->prepare(
    "UPDATE tables SET status='free', current_order_id=NULL WHERE id=?"
  );
  $u->bind_param("i",$t);
  $u->execute();
  $u->close();

  $connection->commit();
  echo json_encode(['success'=>true]);
} catch(Exception $e){
  $connection->rollback();
  echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
