<?php
session_start();
require 'config.php';

// 1️⃣ Only waiters can view
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'waiter') {
    http_response_code(403);
    echo 'Error 403: Unauthorized access';
    exit();
}
// 2️⃣ Load the table
$t = intval($_GET['table_id'] ?? 0);
$stmt = $connection->prepare("SELECT * FROM tables WHERE id = ?");
$stmt->bind_param("i", $t);
$stmt->execute();
$table = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$table) {
    exit("Table not found.");
}

$showConfirmCancel = isset($_GET['confirm_cancel']) && $_GET['confirm_cancel'] == '1';

// 3️⃣ Build a map of option_id → label
$optLabels = [];
$res = $connection->query("SELECT id,label FROM options");
while ($row = $res->fetch_assoc()) {
    $optLabels[intval($row['id'])] = $row['label'];
}

$table_cat = $table['table_category'];

// 4️⃣ Fetch the order meta & items (if any)
$orderMeta = null;
$items     = [];
if (!empty($table['current_order_id'])) {
    $oid = intval($table['current_order_id']);

    // order timestamp
    $q = $connection->prepare("SELECT created_at FROM orders WHERE id = ?");
    $q->bind_param("i", $oid);
    $q->execute();
    $orderMeta = $q->get_result()->fetch_assoc();
    $q->close();

    // order items + when they were marked served
    $q = $connection->prepare("
      SELECT 
        oi.product_id,
        oi.options,
        SUM(oi.quantity) AS quantity,
        p.name AS product_name,
        p.price
      FROM order_items oi
      JOIN products p ON p.id = oi.product_id
      WHERE oi.order_id = ?
      GROUP BY oi.product_id
    ");
    $q->bind_param("i", $oid);
    $q->execute();
    $items = $q->get_result()->fetch_all(MYSQLI_ASSOC);
    $q->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Table <?= htmlspecialchars($table['table_name']) ?></title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      background-image: url("uploads/waiter-page.jpg");
      background-color: transparent;
      background-repeat: no-repeat;
      background-position: center;
      background-size: cover;
    }
    .order-meta {
      background: rgba(238,238,238,0.9);
      color: #222;
      padding: 16px; 
      border-radius: 8px;
      max-width: 800px; 
      margin: 24px auto;
      font-size: 1.1em;
    }
    .order-meta div { margin-bottom: 4px; }
    .order-meta-buttons {
      display: flex; 
      gap: 12px; 
      margin-top: 12px;
    }
    .order-meta-buttons a button {
      flex: 1;
      padding: 8px; 
      border: none;
      border-radius: 6px; 
      background: #007bff;
      color: #fff; 
      cursor: pointer;
    }
    /* --- Order list table --- */
    .order-list-container {
      max-width: 800px; 
      margin:24px auto;
      overflow-x: auto;
    }
    .order-list-table {
      width:100%; 
      border-collapse:collapse;
    }
    .order-list-table th,
    .order-list-table td {
      border: 1px solid #ccc;
      border-color: #FCEFCB;
      padding: 8px;
      text-align: left;
      background: rgba(255,255,255,0.9);
      color: #062905;
      width: 2000px;
    }
    .order-list-table th {
      background: rgba(200,200,200,0.9);
      color: #724e04;
    }
    .order-list-table td.quantity {
      color: #28a745;
      font-weight: bold;
    }
    .order-list-table .total-row td {
      font-weight: bold;
      background: rgba(240,240,240,0.9);
      border-top: 1px solid black;
    }
    .item-options { margin-top:4px; }
    .option-label {
      display: inline-block; 
      margin-right: 6px;
      padding: 2px 6px; 
      border: 1px solid #ccc;
      border-radius: 12px; 
      background: #f5f5f5;
      font-size: 0.9em;
    }
    /* --- Action buttons --- */
    .action-buttons {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      max-width: 800px;
      margin: 24px auto;
    }
    .action-buttons a button,
    .action-buttons form button {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 6px;
      background: #007bff;
      color: #fff;
      cursor: pointer;
    }
    .action-buttons form button.cancel {
      background: #dc3545;
    }
    .action-buttons a.charge button {
      background: #28a745;
    }
    .action-buttons a.back button {
      background: #6c757d;
    }
  </style>
</head>
<body>

<!-- ── ADDED: If “confirm_cancel” is set, show the confirmation overlay ── -->
<?php if ($showConfirmCancel): ?>
      <div class="confirm-popup">
        <h3 style="margin-bottom: 10px;">Xác Nhận Hủy Đơn</h3>
        <p>Bạn có chắc chắn muốn hủy đơn cho bàn “<?= htmlspecialchars($table['table_name']) ?>”?</p>
        <div class="confirm-buttons">
          <!-- When “Yes” is clicked, POST to cancel_table.php -->
          <form method="POST" action="cancel_table.php" style="margin:0;">
            <input type="hidden" name="table_id" value="<?= $t ?>">
            <button type="submit" class="confirm-btn">Xác Nhận</button>
          </form>
          <!-- When “No” is clicked, simply reload without confirm_cancel -->
          <button 
            type="button" 
            class="cancel-btn" 
            onclick="window.location.href='table_info_waiter.php?table_id=<?= $t ?>'">
            Hủy
          </button>
        </div>
      </div>
  <?php endif; ?>
  <?php include 'sidebar.php'; ?>

  <div class="main-content-list">
    <!-- Meta Block -->
    <div class="order-meta">
      <div><strong>Bàn:</strong> <?= htmlspecialchars($table['table_name']) ?></div>
      <div><strong>Đặt Bởi:</strong> <?= htmlspecialchars($_SESSION['user']['username']) ?></div>
      <div><strong>Đặt Lúc:</strong>
        <?php if ($orderMeta): ?>
          <?= date('H:i, j M Y', strtotime($orderMeta['created_at'])) ?>
        <?php else: ?>
          <em>– Chưa Có Đơn –</em>
        <?php endif; ?>
      </div>
      <div class="order-meta-buttons">
        <a href="change_table.php?src=<?= $t ?>"><button>Chuyển Bàn</button></a>
        <!-- <a href="merge_table.php?src=<?= $t ?>"><button>Merge Table</button></a> -->
      </div>
    </div>

    <!-- If no order yet, prompt to take first order -->
    <?php if (!$orderMeta): ?>
      <div style="text-align:center; margin:40px;">
        <a href="waiter_ordering.php?table_id=<?= $t ?>">
          <button style="padding:12px 24px; font-size:1em;">
            Lấy Đơn 
          </button>
        </a>
      </div>
      <div style="text-align:center; margin-bottom:40px;">
        <a class="back" href="table_management_waiter.php">
          <button>Trở Lại Bàn</button>
        </a>
      </div>
      <?php exit; ?>
    <?php endif; ?>

    <!-- Order Items Table -->
    <div class="order-list-container">
      <table class="order-list-table">
        <thead>
          <tr>
            <th>Đồ Uống Đã Đặt</th>
            <th>Giá Thành</th>
            <th>Số Lượng</th>
            <th>Tổng Cộng</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $grandTotal = 0;
          foreach ($items as $it):
            $sub = $it['quantity'] * $it['price'];
            $grandTotal += $sub;
            $labels = [];
            foreach (explode(',', $it['options']) as $optId) {
              $id = intval($optId);
              if (isset($optLabels[$id])) {
                $labels[] = $optLabels[$id];
              }
            }
          ?>
          <tr>
            <td>
              <?= htmlspecialchars($it['product_name']) ?>
              <?php if ($labels): ?>
                <div class="item-options">
                  <?php foreach ($labels as $lbl): ?>
                    <span class="option-label">&bull; <?= htmlspecialchars($lbl) ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </td>
            <td><?= number_format($it['price'],2) ?></td>
            <td class="quantity"><?= intval($it['quantity']) ?></td>
            <td><?= number_format($sub,2) ?></td>
          </tr>
          <?php endforeach; ?>

          <!-- Total row -->
          <tr class="total-row">
            <td colspan="3"><strong style="font-size: 2em; font-weight: 9000;">Tổng Cộng</strong></td>
            <td><strong style="font-size: 2em;"><?= number_format($grandTotal,2) ?></strong></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Bottom Action Buttons -->
    <div class="action-buttons">
      <a href="table_info_waiter.php?table_id=<?= $t ?>&confirm_cancel=1">
        <button style="background: red;"class="cancel">Hủy Đơn</button>
      </a>
      <a href="waiter_ordering.php?table_id=<?= $t ?>"><button>Thêm Món</button></a>
      <a class="back" href="table_management_waiter.php?category=<?=$table_cat?>"><button>Trở Lại</button></a>
      <a class="charge" href="charge_table.php?table_id=<?= $t ?>"><button>Thanh Toán</button></a>
    </div>
  </div>

  <script src="script.js"></script>
  <script src="notif_script.js"></script>


</body>
</html>
