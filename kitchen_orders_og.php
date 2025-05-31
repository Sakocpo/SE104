<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user'], $_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'kitchen') {
    header("Location: index.php");
    exit();
}

// Option map
$optLabels = [];
$res = $connection->query("SELECT id,label FROM options");
while ($row = $res->fetch_assoc()) {
    $optLabels[intval($row['id'])] = $row['label'];
}

// Unserved items with order info
$sql = "
  SELECT
    o.id           AS order_id,
    o.table_id     AS table_id,
    o.created_at   AS created_at,
    oi.id          AS item_id,
    oi.product_id  AS product_id,
    p.name         AS product_name,
    oi.quantity    AS quantity,
    oi.options     AS options
  FROM order_items oi
  JOIN orders o    ON oi.order_id = o.id
  JOIN products p  ON oi.product_id = p.id
  WHERE oi.served = 0
  ORDER BY o.created_at ASC, oi.id ASC
";
$result = $connection->query($sql);

// Group into orders
$orders = [];
while ($row = $result->fetch_assoc()) {
    $oid = $row['order_id'];
    if (!isset($orders[$oid])) {
        $orders[$oid] = [
            'table_id'   => $row['table_id'],
            'created_at' => $row['created_at'],
            'items'      => []
        ];
    }
    $orders[$oid]['items'][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Kitchen — New Items</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .order-card {
      border: 1px solid #ccc;
      border-radius: 8px;
      margin: 16px auto;
      padding: 16px;
      max-width: 600px;
      background: #FBDB93;
      color: #641B2E;
    }
    .order-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 12px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 12px;
    }
    th, td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: left;
    }
    tr { background: #F3C623; }
    th { background: #BE5B50; color: white; }
    .serve-btn {
      background: #28a745;
      color: #fff;
      border: none;
      padding: 8px 12px;
      border-radius: 4px;
      cursor: pointer;
    }
    .option-pill {
      display: inline-block;
      margin-right: 6px;
      margin-top: 4px;
      padding: 2px 6px;
      border-radius: 12px;
      background: #f5f5f5;
      border: 1px solid #ccc;
      font-size: 0.9em;
      color: #333;
    }
  </style>
</head>
<body>
  <div id="sidebar" class="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
    <ul>
      <li><a href="kitchen.php">Kitchen Home</a></li>
      <li><a href="kitchen_orders.php">New Items</a></li>
    </ul>
  </div>

  <div class="main-kitchen-list">
    <?php if (empty($orders)): ?>
      <h2>Chưa Có Đơn Mới</h2>
    <?php else: ?>
      <?php foreach ($orders as $order_id => $order): ?>
        <div class="order-card" id="order-card-table-<?= $order['table_id'] ?>">
          <div class="order-header">
            <div>
              <strong>Order #<?= $order_id ?></strong><br>
              Table: <?= htmlspecialchars($order['table_id']) ?><br>
              Placed at: <?= htmlspecialchars($order['created_at']) ?>
            </div>
          </div>

          <form id="form-table-<?= $order['table_id'] ?>" method="POST" action="mark_served.php" onsubmit="return handleServeSubmit(this)">
            <input type="hidden" name="order_id" value="<?= $order_id ?>">

            <table>
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Qty</th>
                  <th>Options</th>
                  <th>Serve?</th>
                </tr>
              </thead>
              <tbody id="order-items-table-<?= $order['table_id'] ?>">
                <?php foreach ($order['items'] as $it): ?>
                  <tr>
                    <td><?= htmlspecialchars($it['product_name']) ?></td>
                    <td><?= intval($it['quantity']) ?></td>
                    <td>
                      <?php
                        $ids = array_filter(array_map('intval', explode(',', $it['options'])));
                        foreach ($ids as $id):
                          if (isset($optLabels[$id])):
                      ?>
                        <span class="option-pill">
                          <?= htmlspecialchars($optLabels[$id]) ?>
                        </span>
                      <?php endif; endforeach; ?>
                    </td>
                    <td>
                      <input type="checkbox"
                             name="serve_items[]"
                             value="<?= intval($it['item_id']) ?>"
                             data-product="<?= htmlspecialchars($it['product_name']) ?>"
                             data-table="<?= htmlspecialchars($order['table_id']) ?>">
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

            <button type="submit" class="serve-btn">
              Mark Selected Served
            </button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <script src="script.js"></script>
  <script>
  const socket = new WebSocket("ws://localhost:8080");

  socket.onopen = () => {
    socket.send(JSON.stringify({ type: "debug", message: "Kitchen page loaded" }));
  };

  function handleServeSubmit(form) {
    let checkboxes = form.querySelectorAll('input[name="serve_items[]"]:checked');

    // If nothing is checked, assume all unserved items should be marked
    if (checkboxes.length === 0) {
      checkboxes = form.querySelectorAll('input[name="serve_items[]"]');
    }

    // For final check if this is a full order being served:
    const totalItems = form.querySelectorAll('input[name="serve_items[]"]').length;
    const selectedItems = checkboxes.length;

    // Send individual product messages
    checkboxes.forEach(cb => {
      const product = cb.dataset.product;
      const table = cb.dataset.table;

      socket.send(JSON.stringify({
        type: 'serve',
        table: 'Table ' + table,
        product: product
      }));
    });

    if (selectedItems === totalItems && totalItems > 0) {
      const first = checkboxes[0];
      const table = first.dataset.table;

      socket.send(JSON.stringify({
        type: 'order',
        table: 'Table ' + table
      }));
    }

    return true; 
  }
  </script>


</body>
</html>
