<?php
session_start();
require 'config.php';

require 'd:/xampp/vendor/autoload.php';

use WebSocket\Client;

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

    $upd = $connection->prepare("
      UPDATE orders 
         SET status = 'deleted'
       WHERE id = ?
    ");
    $upd->bind_param("i", $order_id);
    $upd->execute();
    $upd->close();

    $upd = $connection->prepare("
      UPDATE tables 
         SET current_order_id = NULL 
       WHERE id = ?
    ");
    $upd->bind_param("i", $table_id);
    $upd->execute();
    $upd->close();

    $payload = json_encode([
      'type'     => 'cancel',
      'order_id' => $order_id,
      'table'    => $table_name
    ]);

    try {
        $ws = new Client("ws://127.0.0.1:8080");
        $ws->send($payload);
        $ws->close();
    } catch (\Exception $e) {
        error_log("WebSocket broadcast failed: " . $e->getMessage());
    }
}

header('Location: table_management_waiter.php');
exit;
