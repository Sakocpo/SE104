<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo 'Error 403: Unauthorized access';
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

// Fetch each order only once, no join to order_items
$stmt = $connection->prepare("  
  SELECT
    o.id,
    o.created_at,
    o.status,
    o.paid_amount,
    o.method,
    CASE WHEN t.deleted = 1 THEN '*deleted*' ELSE t.table_name END AS table_name
  FROM orders o
  JOIN tables t ON o.table_id = t.id
  WHERE o.created_at BETWEEN ? AND ?
  AND o.deleted = 0
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
        // Last 7 days
        const past = new Date(now);
        past.setDate(past.getDate() - 7);
        startDate = new Date(past);
        startDate.setHours(0, 0, 0, 0);
        endDate = new Date(now);
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
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
    <h2 style="text-align:center;">Danh Sách Đơn</h2>
    <div class="page-layout">
      <!-- LEFT: Order List -->
      <div class="order-list-container">
        <?php if (empty($orders)): ?>
          <div class="scroll-box">
            <div style="height: 100%; display: flex; align-items: center; justify-content: center; padding: 40px;">
              <div style="background: #fef2f2; color: #c00; padding: 20px 30px; border-radius: 8px; border: 1px solid #f5c2c7;">
                Không Có Đơn Nào Trong Khoảng Thời Gian Này.
              </div>
            </div>
          </div>
        <?php else: ?>
          <div class="scroll-box">
            <table class="report-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Phương Thức</th>
                  <th>Tổng</th>
                  <th>Trạng Thái</th>
                  <th>Thời Gian</th>
                  <th>Bàn</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $orderNo = 1;
                foreach ($orders as $order):
                  $methodIcon = ($order['method'] === 'qr') ? '📱' : '💵';
                  $statusIcon = ($order['status'] === 'paid')
                                ? '✅'
                                : (($order['status'] === 'pending') ? '⌛' : '❌');
                  $timeStr = date("H:i - d M Y", strtotime($order['created_at']));
                ?>
                <tr onclick="window.location='order_view.php?order_id=<?= $order['id'] ?>'">
                  <td><?= $orderNo++ ?></td>
                  <td><?= $methodIcon ?></td>
                  <td><?= number_format($order['paid_amount'], 2) ?> đ</td>
                  <td class="status-icon"><?= $statusIcon ?></td>
                  <td><?= $timeStr ?></td>
                  <td><?= htmlspecialchars($order['table_name']) ?></td>
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
          <label style="color: black;" for="start_dt">Từ Ngày</label>
          <input type="datetime-local" id="start_dt" name="start_dt" value="<?= toLocalInput($start_sql) ?>">

          <label style="color: black;" for="end_dt">Đến Ngày</label>
          <input type="datetime-local" id="end_dt" name="end_dt" value="<?= toLocalInput($end_sql) ?>">

          <select id="preset" onchange="applyPreset(event)">
            <option value="today" <?= $preset==='today' ? 'selected':''?>>Hôm Nay</option>
            <option value="yesterday" <?= $preset==='yesterday' ? 'selected':''?>>Hôm Qua</option>
            <option value="week" <?= $preset==='week' ? 'selected':''?>>Tuần Này</option>
          </select>

          <input type="hidden" id="preset_input" name="preset" value="<?= htmlspecialchars($preset) ?>">

          <button type="submit">Lọc</button>
        </form>
      </div>
    </div>
  </div>

  <script src="script.js"></script>
</body>
</html>
