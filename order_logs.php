<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user'], $_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location:index.php");
    exit();
}

function toLocalInput(string $dt): string {
    return str_replace(' ', 'T', substr($dt, 0, 16));
}

$now = new DateTimeImmutable();
$raw_start = $_GET['start_dt'] ?? '';
$raw_end = $_GET['end_dt'] ?? '';
$preset = $_GET['preset'] ?? '';

// Store filter state in session if it's being set
if ($raw_start && $raw_end) {
    $_SESSION['order_logs_filter'] = [
        'start_dt' => $raw_start,
        'end_dt' => $raw_end,
        'preset' => $preset
    ];
} else if (isset($_SESSION['order_logs_filter'])) {
    // Use stored filter if no new filter is set
    $raw_start = $_SESSION['order_logs_filter']['start_dt'];
    $raw_end = $_SESSION['order_logs_filter']['end_dt'];
    $preset = $_SESSION['order_logs_filter']['preset'];
}

if (!$raw_start || !$raw_end) {
    $startObj = $now->setTime(0, 0, 0);
    $endObj = $now->setTime(23, 59, 59);
    $preset = 'today';
} else {
    $startObj = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $raw_start) ?: $now->setTime(0, 0, 0);
    $endObj = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $raw_end) ?: $now->setTime(23, 59, 59);
}

$start_sql = $startObj->format('Y-m-d H:i:s');
$end_sql = $endObj->format('Y-m-d H:i:s');

$stmt = $connection->prepare("
  SELECT
    o.id, o.created_at, o.status, o.paid_amount, o.method,
    CASE WHEN t.deleted = 1 THEN '*deleted*' ELSE t.table_name END as table_name,
    oi.id as item_id,
    oi.quantity,
    oi.options,
    CASE WHEN p.deleted = 1 THEN '*deleted*' ELSE p.name END as product_name,
    p.price
  FROM orders o
  JOIN tables t ON o.table_id = t.id
  JOIN order_items oi ON o.id = oi.order_id
  JOIN products p ON oi.product_id = p.id
  WHERE o.created_at BETWEEN ? AND ?
  ORDER BY o.created_at ASC
");
$stmt->bind_param("ss", $start_sql, $end_sql);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order Logs</title>
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
    .page-layout {
      display: flex;
      gap: 20px;
      padding: 20px;
    }
    .order-list-container {
      flex: 3;
    }
    .filter-sidebar {
      flex: 1;
      max-width: 240px;
    }
    .filters {
      display: flex;
      flex-direction: column;
      gap: 10px;
      background: #f9f9f9;
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 10px;
      max-height: 500px;
      overflow-y: auto;
    }
    .filters label {
      font-size: 14px;
    }
    .filters input, .filters select, .filters button {
      padding: 6px;
      font-size: 14px;
    }
    .report-table {
      width: 100%;
      border-collapse: collapse;
      border: 3px solid black;
      margin-top: 10px;
      min-width: 600px;
    }
    .report-table th, .report-table td {
      border: 1px solid #ccc;
      padding: 8px;
      background-color: gray;
      opacity: 0.8;
      text-align: left;
    }
    .report-table tbody tr:hover {
      background: #f1f1f1;
      cursor: pointer;
    }
    .scroll-box {
      max-height: 520px;
      overflow-y: auto;
      /* border: 1px solid #ccc; */
      border-radius: 8px;
    }
    .status-icon {
      font-size: 1.2em;
    }
  </style>
  <script>
    function fmt(d) {
    const pad = n => n.toString().padStart(2, '0');
    const yyyy = d.getFullYear();
    const mm = pad(d.getMonth() + 1);
    const dd = pad(d.getDate());
    const hh = pad(d.getHours());
    const min = pad(d.getMinutes());
    return `${yyyy}-${mm}-${dd}T${hh}:${min}`;
    }

    function applyPreset(e) {
      e.preventDefault();

      const preset = document.getElementById('preset').value;
      const now = new Date();
      let startDate, endDate;

      if (preset === 'today') {
        startDate = new Date(now); startDate.setHours(0, 0, 0, 0);
        endDate = new Date(now); endDate.setHours(23, 59, 0, 0);
      } else if (preset === 'yesterday') {
        const yest = new Date(now);
        yest.setDate(yest.getDate() - 1);
        startDate = new Date(yest); startDate.setHours(0, 0, 0, 0);
        endDate = new Date(yest); endDate.setHours(23, 59, 0, 0);
      } else if (preset === 'week') {
        const dayOfWeek = now.getDay();
        startDate = new Date(now);
        startDate.setDate(now.getDate() - dayOfWeek);
        startDate.setHours(0, 0, 0, 0);
        endDate = new Date(startDate);
        endDate.setDate(startDate.getDate() + 6);
        endDate.setHours(23, 59, 0, 0);
      } else {
        return;
      }

      document.getElementById('start_dt').value = fmt(startDate);
      document.getElementById('end_dt').value = fmt(endDate);
      document.getElementById('preset_input').value = preset;

      document.getElementById('filterForm').submit();
    }
  </script>
</head>
<body>
    <div id="sidebar" class="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
    <ul>
        <li><a href="admin.php">Trang Admin</a></li>
        <li><a href="product_management.php">Quản Lý Hàng</a></li>
        <li><a href="ingredients_management.php">Quản Lý Nguyên Liệu</a></li>
        <li><a href="user_management.php">Quản Lý Người Dùng</a></li>
        <li><a href="table_management_admin.php">Quản Lý Bàn</a></li>
        <li><a href="product_options_management.php">Quản Lý Options</a></li>
        <li><a href="report.php">Báo Cáo Cuối Ngày</a></li>
        <li><a href="order_logs.php">Danh Sách Đơn</a></li>
    </ul>
    </div>

<div class="main-content">
  <h2 style="text-align:center;">Order Logs</h2>
  <div class="page-layout">
    
    <!-- LEFT: Order List -->
    <div class="order-list-container">
      <?php if (empty($orders)): ?>
        <div class="scroll-box">
          <div style="height: 100%; display: flex; align-items: center; justify-content: center; padding: 40px;">
            <div style="background: #fef2f2; color: #c00; padding: 20px 30px; border-radius: 8px; border: 1px solid #f5c2c7;">
              No orders in this period.
            </div>
          </div>
        </div>
      <?php else: ?>
        <div class="scroll-box">
          <table class="report-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Method</th>
                <th>Total</th>
                <th>Status</th>
                <th>Time</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $orderNo = 1;
              foreach ($orders as $order):
                $methodIcon = $order['method'] === 'qr' ? '📱' : '💵';
                $statusIcon = $order['status'] === 'paid' ? '✅' : ($order['status'] === 'pending' ? '⌛' : '❌');
                $timeStr = date("H:i - d M Y", strtotime($order['created_at']));
              ?>
              <tr onclick="window.location='order_view.php?order_id=<?= $order['id'] ?>'">
                <td><?= $orderNo++ ?></td>
                <td><?= $methodIcon ?></td>
                <td><?= number_format($order['paid_amount'], 2) ?> đ</td>
                <td class="status-icon"><?= $statusIcon ?></td>
                <td><?= $timeStr ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- RIGHT: Date Filter -->
    <div class="filter-sidebar">
      <form id="filterForm" method="GET" class="filters">
        <label for="start_dt">From</label>
        <input type="datetime-local" id="start_dt" name="start_dt" value="<?= toLocalInput($start_sql) ?>">
        
        <label for="end_dt">To</label>
        <input type="datetime-local" id="end_dt" name="end_dt" value="<?= toLocalInput($end_sql) ?>">

        <label for="preset">Quick Range</label>
        <select id="preset" onchange="applyPreset(event)">
          <option value="today"     <?= $preset==='today'     ? 'selected':''?>>Today</option>
          <option value="yesterday" <?= $preset==='yesterday' ? 'selected':''?>>Yesterday</option>
          <option value="week"      <?= $preset==='week'      ? 'selected':''?>>This Week</option>
        </select>

        <input type="hidden" id="preset_input" name="preset" value="<?= htmlspecialchars($preset) ?>">

        <button type="submit">Filter</button>
      </form>
    </div>
  </div>
</div>

<script src="script.js"></script>
</body>
</html>
