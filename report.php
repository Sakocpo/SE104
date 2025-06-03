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

function fromLocalInput(string $s): string {
    return str_replace('T', ' ', $s) . ':00';
}

$now = new DateTimeImmutable();
$raw_start = $_GET['start_dt'] ?? '';
$raw_end = $_GET['end_dt'] ?? '';
$preset = $_GET['preset'] ?? '';

if (!$raw_start || !$raw_end) {
    $startObj = $now->setTime(0, 0, 0);
    $endObj = $now->setTime(23, 59, 59);
    $preset = 'today';
} else {
    $startObj = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $raw_start) ?: $now->setTime(0, 0, 0);
    $endObj = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $raw_end) ?: $now->setTime(23, 59, 59);
}

if (!$raw_start && !$raw_end) {
    // do nothing, already set to today
} elseif ($startObj->format('Y-m-d') === $now->format('Y-m-d') && $endObj->format('Y-m-d') === $now->format('Y-m-d')) {
    $preset = 'today';
} elseif ($startObj->format('Y-m-d') === $now->modify('-1 day')->format('Y-m-d') && $endObj->format('Y-m-d') === $now->modify('-1 day')->format('Y-m-d')) {
    $preset = 'yesterday';
} elseif ($startObj->format('Y-m-d') === $now->modify('last sunday')->format('Y-m-d') && $endObj->format('Y-m-d') === $now->modify('next saturday')->format('Y-m-d')) {
    $preset = 'week';
}

$start_sql = $startObj->format('Y-m-d H:i:s');
$end_sql = $endObj->format('Y-m-d H:i:s');

// Sales summary
$stmt = $connection->prepare("
  SELECT
    CASE WHEN p.deleted = 1 THEN '*deleted*' ELSE p.name END AS product_name,
    p.price AS unit_price,
    SUM(oi.quantity) AS total_qty,
    SUM(oi.quantity * p.price) AS total_rev
  FROM orders o
  JOIN order_items oi ON o.id = oi.order_id
  JOIN products p ON oi.product_id = p.id
  WHERE o.created_at BETWEEN ? AND ?
  AND o.deleted = 0
  GROUP BY p.id, p.name, p.price, p.deleted
  ORDER BY p.name
");
$stmt->bind_param("ss", $start_sql, $end_sql);
$stmt->execute();
$result = $stmt->get_result();

$grand_qty = 0;
$grand_rev = 0.0;
$sales = [];
while ($r = $result->fetch_assoc()) {
    $sales[] = $r;
    $grand_qty += (int)$r['total_qty'];
    $grand_rev += (float)$r['total_rev'];
}
$stmt->close();

// Order logs
$stmt = $connection->prepare("
  SELECT
    o.id, o.created_at, o.status, o.paid_amount, o.method,
    CASE WHEN t.deleted = 1 THEN '*deleted*' ELSE t.table_name END as table_name
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
  <title>Sales Report</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      background-image: url("uploads/admin-page.jpg");
      background-color: transparent;
      background-attachment: fixed;
      background-repeat: no-repeat;
      background-position: center;
      background-size: cover;
    }
    .filters {
      margin: 20px auto;
      max-width: 800px;
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      align-items: center;
    }
    .filters input, .filters select, .filters button {
      padding: 6px;
    }
    .report-table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px auto;
      border: 3px solid black;
      max-width: 800px;
    }
    .report-table th, .report-table td {
      border: 1px solid black; /* color cells here */
      padding: 8px;
      background: gray;
      opacity: 0.8;
      text-align: left;
    }
    .report-table tfoot td {
      font-weight: bold;
      background-color: green;
    }
    .report-table tbody tr:hover {
      background: #f1f1f1;
      cursor: pointer;
    }
    .report-section {
      margin-top: 40px;
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

    function applyPreset() {
      const sel = document.getElementById('preset');
      const p = sel.value;
      let now = new Date(), sd, ed;
      if (p === 'today') {
        sd = new Date(now); sd.setHours(0,0,0);
        ed = new Date(now); ed.setHours(23,59,0);
      } else if (p === 'yesterday') {
        now.setDate(now.getDate()-1);
        sd = new Date(now); sd.setHours(0,0,0);
        ed = new Date(now); ed.setHours(23,59,0);
      } else if (p === 'week') {
        let dow = now.getDay();
        sd = new Date(now); sd.setDate(now.getDate()-dow); sd.setHours(0,0,0);
        ed = new Date(sd); ed.setDate(sd.getDate()+6); ed.setHours(23,59,0);
      } else return;
      document.getElementById('start_dt').value = fmt(sd);
      document.getElementById('end_dt').value   = fmt(ed);
      document.getElementById('preset_input').value = p;
      document.getElementById('filterForm').submit();
    }
  </script>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content" style="padding:20px;">
      <h2>Báo Cáo Doanh Thu</h2>
      <form id="filterForm" method="GET" class="filters">
        <div>
          <label>Từ Ngày
            <input type="datetime-local" id="start_dt" name="start_dt" value="<?= toLocalInput($start_sql) ?>">
          </label>
        </div>
        <div>
          <label>Đến Ngày
            <input type="datetime-local" id="end_dt" name="end_dt" value="<?= toLocalInput($end_sql) ?>">
          </label>
        </div>
        <select id="preset" onchange="applyPreset()">
          <option value="today"     <?= $preset==='today'     ? 'selected':''?>>Hôm Nay</option>
          <option value="yesterday" <?= $preset==='yesterday' ? 'selected':''?>>Hôm Qua</option>
          <option value="week"      <?= $preset==='week'      ? 'selected':''?>>Tuần Này</option>
        </select>
        <input type="hidden" id="preset_input" name="preset" value="<?= htmlspecialchars($preset) ?>">
        <button type="submit">Lọc</button>
      </form>

      <?php if (empty($sales)): ?>
        <p style="text-align:center;">Không Có Doanh Thu Trong Khoảng Thời Gian Này.</p>
      <?php else: ?>
        <table class="report-table">
          <thead>
            <tr>
              <th>Sản Phẩm</th><th>Đơn Giá</th><th>Số Lượng</th><th>Tổng</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($sales as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['product_name']) ?></td>
              <td><?= number_format($row['unit_price'],2) ?></td>
              <td><?= intval($row['total_qty']) ?></td>
              <td><?= number_format($row['total_rev'],2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td><strong>Tổng Cộng</strong></td><td></td>
              <td><?= $grand_qty ?></td>
              <td><?= number_format($grand_rev,2) ?></td>
            </tr>
          </tfoot>
        </table>
      <?php endif; ?>

    </div>
    <script src="script.js"></script>
</body>
</html>



<!-- repot should save as text and not pointer  -->