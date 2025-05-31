<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user'], $_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$order_id = intval($_GET['order_id'] ?? 0);
if (!$order_id) {
    exit("Invalid order ID.");
}

$stmt = $connection->prepare("
  SELECT o.*, t.table_name, u.username
  FROM orders o
  JOIN tables t ON o.table_id = t.id
  LEFT JOIN users u ON o.created_by = u.id
  WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    exit("Order not found.");
}

$stmt = $connection->prepare("
  SELECT oi.quantity, oi.options, oi.served,
         p.name AS product_name, p.price
  FROM order_items oi
  JOIN products p ON p.id = oi.product_id
  WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$optLabels = [];
$res = $connection->query("SELECT id, label FROM options");
while ($row = $res->fetch_assoc()) {
    $optLabels[intval($row['id'])] = $row['label'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order #<?= $order['id'] ?> View</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      margin: 0;
      font-family: Roboto, sans-serif;
      background-image: url("uploads/admin-page.jpg");
      background-color: transparent;
      background-attachment: fixed;
      background-repeat: no-repeat;
      background-position: center;
      background-size: cover;
    }
    .order-meta {
      background: #ffc107;
      color: #222;
      padding: 16px;
      border-radius: 8px;
      max-width: 800px;
      /* margin: 24px auto; */
      font-size: 1.1em;
      position: relative;
    }
    .order-meta div { margin-bottom: 6px; }
    .order-status-icon {
      position: absolute;
      top: 16px;
      right: 16px;
      font-size: 1.8em;
    }
    .main-content-list {
        border: 5px solid rgb(12, 9, 4);
        border-radius: 6px;
    }
    .order-list-container {
      max-width: 800px;
      margin: 24px auto;
      overflow-x: auto;
    }
    .order-list-table {
      width: 100%;
      border-collapse: collapse;
    }
    .order-list-table th, .order-list-table td {
      border: 1px solid #ccc;
      padding: 8px;
      background: #f2c47c;
      color: #062905;
    }
    .order-list-table th {
      background: #a4daab;
      color: #724e04;
    }
    .order-list-table td.quantity {
      color: #28a745;
      font-weight: bold;
    }
    .total-row td {
      font-weight: bold;
      background: #FCEFCB;
    }
    .item-options {
      margin-top: 4px;
    }
    .option-label {
      display: inline-block;
      margin-right: 6px;
      padding: 2px 6px;
      border: 1px solid #ccc;
      border-radius: 12px;
      background: #f5f5f5;
      font-size: 0.9em;
    }
    .action-buttons {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 16px;
      /* margin: 24px auto; */
      max-width: 8000px;
    }
    .action-buttons form button,
    .action-buttons button,
    .action-buttons a {
      padding: 12px 24px;
      border: none;
      border-radius: 6px;
      background: #007bff;
      color: white;
      cursor: pointer;
      max-width: 200px;
      font-weight: bold;
      text-decoration: none;
      text-align: center;
      margin-bottom: 10px;
    }
    .action-buttons .cancel-btn {
      background: #dc3545;
    }
    @media print {
      .sidebar, .toggle-btn, .action-buttons {
        display: none !important;
      }
      body {
        padding: 0;
        margin: 0;
      }
    }
    
  </style>
</head>
<body>
<div id="sidebar" class="sidebar">
  <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
  <ul>
    <li><a href="admin.php">Admin Page</a></li>
    <li><a href="product_management.php">Product Management</a></li>
    <li><a href="inventory_management.php">Inventory Management</a></li>
    <li><a href="user_management.php">Users Management</a></li>
    <li><a href="table_management_admin.php">Tables Management</a></li>
    <li><a href="product_options_management.php">Product Options</a></li>
    <li><a href="report.php">Report</a></li>
    <li><a href="order_logs.php">Order Logs</a></li>
  </ul>
</div>

<div class="main-content-list">
  <div class="order-meta">
    <div><strong>Table:</strong> <?= htmlspecialchars($order['table_name']) ?></div>
    <div><strong>Ordered by:</strong> <?= htmlspecialchars($order['username'] ?? 'Unknown') ?></div>
    <div><strong>Ordered at:</strong> <?= date('H:i, j M Y', strtotime($order['created_at'])) ?></div>
    <div><strong>Method:</strong> <?= $order['method'] === 'qr' ? '📱 QR' : '💵 Cash' ?></div>
    <div class="order-status-icon">
      <?= $order['status'] === 'paid' ? '✅' : ($order['status'] === 'pending' ? '⌛' : '❌') ?>
    </div>
  </div>

  <div class="order-list-container">
    <table class="order-list-table">
      <thead>
        <tr>
          <th>Product & Options</th>
          <th>Price</th>
          <th>Quantity</th>
          <th>Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $grouped = [];
        $grandTotal = 0;
        foreach ($items as $item) {
            $key = $item['product_name'] . '|' . $item['price'] . '|' . $item['options'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'name' => $item['product_name'],
                    'price' => $item['price'],
                    'options' => $item['options'],
                    'quantity' => 0
                ];
            }
            $grouped[$key]['quantity'] += $item['quantity'];
        }

        foreach ($grouped as $g) {
            $sub = $g['quantity'] * $g['price'];
            $grandTotal += $sub;

            $labels = [];
            foreach (explode(',', $g['options']) as $optId) {
                $id = intval($optId);
                if (isset($optLabels[$id])) {
                    $labels[] = $optLabels[$id];
                }
            }
            ?>
            <tr>
              <td>
                <?= htmlspecialchars($g['name']) ?>
                <?php if ($labels): ?>
                  <div class="item-options">
                    <?php foreach ($labels as $lbl): ?>
                      <span class="option-label">&bull; <?= htmlspecialchars($lbl) ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </td>
              <td><?= number_format($g['price'], 2) ?></td>
              <td class="quantity"><?= intval($g['quantity']) ?></td>
              <td><?= number_format($sub, 2) ?></td>
            </tr>
            <?php
        }
        ?>
        <tr class="total-row">
          <td colspan="3"><strong>Total</strong></td>
          <td><strong><?= number_format($grandTotal, 2) ?></strong></td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Action Buttons -->
    <div class="action-buttons">
        <form method="POST" action="cancel_table.php" onsubmit="return confirm('Cancel this order?')" style="display: inline;">
            <input type="hidden" name="table_id" value="<?= $order['table_id'] ?>">
            <button type="submit" class="cancel-btn">Cancel Order</button>
        </form>

        <button onclick="window.print()">🖨️ Print</button>

        <a href="order_logs.php" class="back-btn">← Back to Logs</a>
    </div>

</div>

<script src="script.js"></script>
</body>
</html>
