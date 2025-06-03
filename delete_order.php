<?php
// delete_order.php

session_start();
require_once 'config.php';

if (!isset($_SESSION['user'], $_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$order_id = intval($_POST['order_id'] ?? 0);
if (!$order_id) {
    exit("Invalid order ID.");
}

// 1) Soft‐delete the order row
$stmt = $connection->prepare("UPDATE orders SET deleted = 1 WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$stmt->close();

// 2) Soft‐delete all its items
$stmt = $connection->prepare("UPDATE order_items SET deleted = 1 WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$stmt->close();

header("Location: order_logs.php?deleted=1");
exit();
