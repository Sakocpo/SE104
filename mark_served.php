<?php
session_start();
require_once 'config.php';

// Only POSTs from kitchen may hit this
if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || !isset($_SESSION['user'])
    || $_SESSION['user']['role'] !== 'kitchen') {
    header('Location: index.php');
    exit();
}

// Grab order and any explicitly‐checked items
$order_id    = intval($_POST['order_id'] ?? 0);
$serve_items = $_POST['serve_items'] ?? [];

if (!$order_id) {
    exit('No order specified.');
}

// If nothing was checked, fetch all un‑served items for that order
if (empty($serve_items)) {
    $stmt = $connection->prepare("
        SELECT id
        FROM order_items
        WHERE order_id = ? AND served = 0
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $serve_items = [];
    while ($row = $res->fetch_assoc()) {
        $serve_items[] = $row['id'];
    }
    $stmt->close();
}

// Now mark each of those items as served
if (!empty($serve_items)) {
    $stmt = $connection->prepare("
        UPDATE order_items
           SET served = 1
         WHERE id = ?
    ");
    foreach ($serve_items as $item_id) {
        $iid = intval($item_id);
        $stmt->bind_param("i", $iid);
        $stmt->execute();
    }
    $stmt->close();
}

// (Optional) If you want to mark the entire order as “completed” once *all* its items are served,
// you could run a quick check here and update orders.status and the table’s occupied flag.

// Finally, send the user back to the kitchen list:
header("Location: kitchen_orders.php");
exit();
