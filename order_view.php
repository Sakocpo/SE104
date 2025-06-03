<?php
// order_view.php

session_start();
require 'config.php';

if (!isset($_SESSION['user'], $_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$order_id = intval($_GET['order_id'] ?? 0);
if (!$order_id) {
    exit("Invalid order ID.");
}

// Fetch order master data
$stmt = $connection->prepare("
  SELECT o.*, t.table_name, u.username,
         CASE 
           WHEN o.status = 'paid' THEN o.charged_at
           ELSE NULL
         END as charged_at
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

// Fetch all items in this order
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

// Build option‐label lookup
$optLabels = [];
$res = $connection->query("SELECT id,label FROM options");
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
      border: 5px solid #0c0904;
      border-radius: 6px;
      max-width: 850px;
      margin: 24px auto;
      background: rgba(255,255,255,0.6);
    }
    .order-list-container {
      overflow-x: auto;
      padding-top: 5px;
    }
    .order-list-table {
      width: 100%;
      border-collapse: collapse;
    }
    .order-list-table th,
    .order-list-table td {
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
      gap: 10px;
      padding: 16px 0;
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
    .action-buttons .delete-btn {
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
  <?php include 'sidebar.php'; ?>
  <div class="main-content-list">
    <div class="order-meta">
      <div><strong>Bàn:</strong> <?= htmlspecialchars($order['table_name']) ?></div>
      <div><strong>Được Đặt Bởi:</strong> <?= htmlspecialchars($order['username'] ?? 'Không Xác Định') ?></div>
      <div><strong>Được Đặt Lúc:</strong> <?= date('H:i, j M Y', strtotime($order['created_at'])) ?></div>
      <div><strong>Phương Thức:</strong> <?= $order['method'] === 'qr' ? '📱 QR' : '💵 Tiền Mặt' ?></div>
      <div><strong>Tính Tiền Lúc:</strong> <?= $order['charged_at'] ? date('H:i, j M Y', strtotime($order['charged_at'])) : 'Chưa tính tiền' ?></div>
      <div class="order-status-icon">
        <?= $order['status'] === 'paid' ? '✅' : ($order['status'] === 'pending' ? '⌛' : '❌') ?>
      </div>
    </div>

    <div class="order-list-container">
      <table class="order-list-table">
        <thead>
          <tr>
            <th>Món Đã Đặt</th>
            <th>Giá</th>
            <th>Số Lượng</th>
            <th>Tổng</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // Group items by product+options
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
            <td colspan="3"><strong>Tổng</strong></td>
            <td><strong><?= number_format($grandTotal, 2) ?></strong></td>
          </tr>
        </tbody>
      </table>
    </div>

    <?php if (isset($_GET['confirm_delete'])): ?>
      <div class="confirm-popup" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.3); z-index: 1000;">
        <h3>Xác Nhận Xóa Đơn</h3>
        <p>Bạn có chắc chắn muốn xóa đơn hàng #<?= intval($order['id']) ?> tại bàn "<?= htmlspecialchars($order['table_name']) ?>"?</p>
        <form method="POST" action="delete_order.php">
          <input type="hidden" name="order_id" value="<?= intval($order['id']) ?>">
          <button type="submit" name="confirm_delete" class="confirm-btn">Xác Nhận</button>
          <button type="button" class="cancel-btn"
                  onclick="window.location.href='order_view.php?order_id=<?= intval($order['id']) ?>'">
            Hủy
          </button>
        </form>
      </div>
    <?php endif; ?>

    <div class="action-buttons">
      <form method="GET" action="order_view.php" style="display:inline; padding:0; margin:0;">
        <input type="hidden" name="order_id" value="<?= intval($order['id']) ?>">
        <input type="hidden" name="confirm_delete" value="1">
        <button type="submit" class="delete-btn">Xóa Hóa Đơn</button>
      </form>

      <button onclick="window.print()">🖨️ In Hóa Đơn</button>
      <a href="order_logs.php" class="back-btn">← Quay Lại</a>
    </div>

  </div>

  <script src="script.js"></script>
</body>
</html>
