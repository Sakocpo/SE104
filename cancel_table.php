<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_SESSION['user']) ||
    $_SESSION['user']['role'] !== 'waiter'
) {
    header('Location: index.php');
    exit;
}

$table_id = intval($_POST['table_id'] ?? 0);
if (!$table_id) {
    header('Location: table_management_waiter.php');
    exit;
}

// 1) fetch that table’s current_order_id AND table_name
$stmt = $connection->prepare("
  SELECT current_order_id, table_name
    FROM tables
   WHERE id = ?
");
$stmt->bind_param("i", $table_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

if (!empty($row['current_order_id'])) {
    $order_id   = intval($row['current_order_id']);
    $table_name = $row['table_name'];

    // 2) delete its items
    $del = $connection->prepare("
      DELETE FROM order_items 
       WHERE order_id = ?
    ");
    $del->bind_param("i", $order_id);
    $del->execute();
    $del->close();

    // 3) delete the order itself
    $del = $connection->prepare("
      DELETE FROM orders 
       WHERE id = ?
    ");
    $del->bind_param("i", $order_id);
    $del->execute();
    $del->close();

    // 4) clear the table’s pointer
    $upd = $connection->prepare("
      UPDATE tables 
         SET current_order_id = NULL 
       WHERE id = ?
    ");
    $upd->bind_param("i", $table_id);
    $upd->execute();
    $upd->close();

    // ─── 5) real-time CANCEL broadcast to kitchen ───
    $msg = json_encode([
      'type'     => 'cancel',
      'order_id' => $order_id,
      'table_id' => $table_id,
      'table'    => $table_name
    ]);
    // adjust nc command / host:port if needed
    shell_exec("echo '" . addslashes($msg) . "' | nc localhost 8080");
    // ────────────────────────────────────────────────
}

// 6) back to overview
header('Location: table_management_waiter.php');
exit;
