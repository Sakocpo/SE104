<?php
session_start();
require_once 'config.php';

// only admin can close out a day
if (!isset($_SESSION['user'], $_SESSION['user']['role'])
  || $_SESSION['user']['role'] !== 'admin') {
  header("Location: index.php");
  exit();
}

// ensure today hasn't already been archived
$today = date('Y-m-d');
$chk = $connection->prepare("SELECT id FROM daily_reports WHERE report_date = ?");
$chk->bind_param("s", $today);
$chk->execute();
if ($chk->get_result()->num_rows) {
  $chk->close();
  die("Today's report already archived.");
}
$chk->close();

// 1️⃣ Summarize today's sales
$sql = "
  SELECT
    oi.product_id,
    SUM(oi.quantity) AS total_qty,
    SUM(oi.quantity * p.price) AS total_rev
  FROM orders o
  JOIN order_items oi ON o.id = oi.order_id
  JOIN products p     ON oi.product_id = p.id
  WHERE DATE(o.created_at) = ?
  GROUP BY oi.product_id
";
$stmt = $connection->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$res = $stmt->get_result();

// if no sales, nothing to do
if ($res->num_rows === 0) {
  die("No sales to archive for $today.");
}

// 2️⃣ Create the archive header
$ins = $connection->prepare("INSERT INTO daily_reports (report_date) VALUES (?)");
$ins->bind_param("s", $today);
$ins->execute();
$report_id = $ins->insert_id;
$ins->close();

// 3️⃣ Insert line items
$ins2 = $connection->prepare("
  INSERT INTO daily_report_items
    (report_id, product_id, quantity, revenue)
  VALUES (?, ?, ?, ?)
");
while ($row = $res->fetch_assoc()) {
  $ins2->bind_param(
    "iiid",
    $report_id,
    $row['product_id'],
    $row['total_qty'],
    $row['total_rev']
  );
  $ins2->execute();
}
$ins2->close();
$stmt->close();

// 4️⃣ Clear live orders & reset tables
$connection->begin_transaction();
try {
  // drop all order_items, orders
  $connection->query("DELETE FROM order_items");
  $connection->query("DELETE FROM orders");
  // free up tables
  $connection->query("UPDATE tables SET current_order_id = NULL, status = 'free'");

  $connection->commit();
  header("Location: report.php?archived=1");
  exit();
} catch (Exception $e) {
  $connection->rollback();
  die("Archive failed: " . $e->getMessage());
}
?>
