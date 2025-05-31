<?php
session_start();
require "config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
 || !isset($_SESSION['user'], $_SESSION['user']['role'])
 || $_SESSION['user']['role'] !== 'waiter') {
  http_response_code(403);
  exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$table_id = intval($data['table_id'] ?? 0);
$items    = $data['items'] ?? [];

if (!$table_id || !$items) {
  echo json_encode(['success' => false, 'error' => 'No items']);
  exit;
}

// Get table name from DB
$stmt = $connection->prepare("SELECT table_name FROM tables WHERE id = ?");
$stmt->bind_param("i", $table_id);
$stmt->execute();
$tableRow = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$tableRow) {
  echo json_encode(['success' => false, 'error' => 'Invalid table']);
  exit;
}
$table_name = $tableRow['table_name'];

$connection->begin_transaction();
try {
  // Check for existing order or create new
  $stmt0 = $connection->prepare("SELECT current_order_id FROM tables WHERE id = ?");
  $stmt0->bind_param("i", $table_id);
  $stmt0->execute();
  $row0 = $stmt0->get_result()->fetch_assoc();
  $stmt0->close();

  if (!empty($row0['current_order_id'])) {
    $order_id = intval($row0['current_order_id']);
  } else {
    $created_by = $_SESSION['user']['id'];
    $stmt1 = $connection->prepare("INSERT INTO orders (table_id, created_by) VALUES (?, ?)");
    $stmt1->bind_param("ii", $table_id, $created_by);
    $stmt1->execute();
    $order_id = $stmt1->insert_id;
    $stmt1->close();

    $stmt2 = $connection->prepare("UPDATE tables SET status = 'occupied', current_order_id = ? WHERE id = ?");
    $stmt2->bind_param("ii", $order_id, $table_id);
    $stmt2->execute();
    $stmt2->close();
  }

  // Load options and products map
  $optionMap = [];
  $res = $connection->query("SELECT id, label FROM options");
  while ($row = $res->fetch_assoc()) {
    $optionMap[intval($row['id'])] = $row['label'];
  }

  $productMap = [];
  $res = $connection->query("SELECT id, name FROM products");
  while ($row = $res->fetch_assoc()) {
    $productMap[intval($row['id'])] = $row['name'];
  }

  foreach ($items as $it) {
    $pid = intval($it['product_id']);
    $optsArr = array_map('intval', $it['options']);
    sort($optsArr);
    $opts = implode(',', $optsArr);
    $qty = intval($it['quantity']);

    $check = $connection->prepare("SELECT id, quantity, served FROM order_items WHERE order_id = ? AND product_id = ? AND options = ? ORDER BY id DESC");
    $check->bind_param("iis", $order_id, $pid, $opts);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    $check->close();

    if ($existing && $existing['served'] == 0) {
      $newQty = $existing['quantity'] + $qty;
      $update = $connection->prepare("UPDATE order_items SET quantity = ? WHERE id = ?");
      $update->bind_param("ii", $newQty, $existing['id']);
      $update->execute();
      $update->close();
    } else {
      $insert = $connection->prepare("INSERT INTO order_items (order_id, product_id, options, quantity, served) VALUES (?, ?, ?, ?, 0)");
      $insert->bind_param("iisi", $order_id, $pid, $opts, $qty);
      $insert->execute();
      $insert->close();
    }

    // Notify kitchen
    $optionLabels = array_map(function($oid) use ($optionMap) {
      return $optionMap[$oid] ?? '';
    }, $optsArr);

    $msg = json_encode([
      'type'      => 'serve',
      'order_id'  => $order_id,
      'table'     => $table_name,
      'product'   => $productMap[$pid] ?? 'Unknown Product',
      'quantity'  => $qty,
      'options'   => $optionLabels
    ]);
    file_put_contents("debug_ws_log.txt", $msg . PHP_EOL, FILE_APPEND);
    shell_exec("echo '" . $msg . "' | nc localhost 8080");
  }

  $connection->commit();
  // right after $connection->commit();
echo json_encode([
  'success'  => true,
  'order_id'=> $order_id,
  'table'    => $table_name
]);
exit;


} catch (Exception $e) {
  $connection->rollback();
  echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
