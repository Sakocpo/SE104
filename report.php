<?php
session_start();
require_once 'config.php';

// Only admin users can view this page
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo 'Error 403: Unauthorized access';
    exit();
}

function toLocalInput(string $dt): string {
    // "YYYY-MM-DD HH:MM:SS" → "YYYY-MM-DDTHH:MM"
    return str_replace(' ', 'T', substr($dt, 0, 16));
}

function fromLocalInput(string $s): string {
    // "YYYY-MM-DDTHH:MM" → "YYYY-MM-DD HH:MM:00"
    return str_replace('T', ' ', $s) . ':00';
}

$now       = new DateTimeImmutable();
$raw_start = $_GET['start_dt'] ?? '';
$raw_end   = $_GET['end_dt']   ?? '';
$preset    = $_GET['preset']   ?? '';
$log_type  = $_GET['log_type'] ?? 'orders'; 

if (!$raw_start || !$raw_end) {
    $startObj = $now->setTime(0, 0, 0);
    $endObj   = $now->setTime(23, 59, 59);
    $preset   = 'today';
} else {
    $startObj = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $raw_start) 
                  ?: $now->setTime(0, 0, 0);
    $endObj   = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $raw_end) 
                  ?: $now->setTime(23, 59, 59);
}

if ($raw_start && $raw_end) {
    $todayStr     = $now->format('Y-m-d');
    $yesterdayStr = $now->modify('-1 day')->format('Y-m-d');
    $weekStartStr = $now->modify('last sunday')->format('Y-m-d');
    $weekEndStr   = $now->modify('next saturday')->format('Y-m-d');

    $sd = $startObj->format('Y-m-d');
    $ed = $endObj->format('Y-m-d');

    if ($sd === $todayStr && $ed === $todayStr) {
        $preset = 'today';
    } elseif ($sd === $yesterdayStr && $ed === $yesterdayStr) {
        $preset = 'yesterday';
    } elseif ($sd === $weekStartStr && $ed === $weekEndStr) {
        $preset = 'week';
    }
}

$start_sql = $startObj->format('Y-m-d H:i:s');
$end_sql   = $endObj->format('Y-m-d H:i:s');

$sales          = [];
$grand_qty      = 0;
$grand_rev      = 0.0;
$orders         = [];
$ingredientLogs = [];

if ($log_type === 'orders') {
    $stmt = $connection->prepare("
      SELECT
        CASE WHEN p.deleted = 1 THEN '*deleted*' ELSE p.name END AS product_name,
        p.price AS unit_price,
        SUM(oi.quantity) AS total_qty,
        SUM(oi.quantity * p.price) AS total_rev
      FROM orders o
      JOIN order_items oi ON o.id = oi.order_id
      JOIN products p    ON oi.product_id = p.id
      WHERE o.created_at BETWEEN ? AND ?
        AND o.deleted = 0
        AND o.status  = 'paid'
      GROUP BY p.id, p.name, p.price, p.deleted
      ORDER BY p.name
    ");
    $stmt->bind_param("ss", $start_sql, $end_sql);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $sales[]      = $row;
        $grand_qty   += (int)$row['total_qty'];
        $grand_rev   += (float)$row['total_rev'];
    }
    $stmt->close();

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
        AND o.status  = 'paid'
      ORDER BY o.created_at ASC
    ");
    $stmt->bind_param("ss", $start_sql, $end_sql);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

elseif ($log_type === 'ingredients') {
    $stmt = $connection->prepare("
      SELECT
        il.id,
        il.created_at,
        i.name       AS ingredient_name,
        uo.name     AS unit_label,
        il.change_amount,
        il.before_qty,
        il.after_qty,
        usr.username AS taken_by
      FROM ingredient_logs il
      JOIN ingredients i   ON il.ingredient_id = i.id
      JOIN unit_options uo ON i.unit_id       = uo.id
      LEFT JOIN users usr  ON il.user_id       = usr.id
      WHERE il.created_at BETWEEN ? AND ?
      ORDER BY il.created_at ASC
    ");
    $stmt->bind_param("ss", $start_sql, $end_sql);
    $stmt->execute();
    $ingredientLogs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sales &amp; Ingredient Logs Report</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      background-image: url("uploads/admin-page.jpg");
      background-attachment: fixed;
      background-repeat: no-repeat;
      background-position: center;
      background-size: cover;
    }

    .filters {
      margin: 20px auto;
      display: flex;
      flex-wrap: nowrap;         
      align-items: flex-end;     
      gap: 12px;
      max-width: 100%;
      overflow-x: auto;          
    }

    .filters .field-from,
    .filters .field-to {
      display: flex;
      flex-direction: column;
      gap: 4px;                  
      flex: 1 1 200px;           
    }

    .filters .field-preset,
    .filters .field-logtype {
      flex: 0 0 150px;           
    }

    .filters select {
      width: 100%;
      padding: 12px;
      font-size: 1em;
    }

    .filter-button-container {
      margin-top: 12px;
      text-align: right;         
    }

    .filter-button-container button {
      padding: 6px 12px;
      font-size: 1em;
    }

    .filters::-webkit-scrollbar {
      height: 6px;
    }
    .filters::-webkit-scrollbar-thumb {
      background-color: rgba(0, 0, 0, 0.2);
      border-radius: 3px;
    }

    .report-table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px auto;
      border: 3px solid black;
      max-width: 900px;
    }
    .report-table th,
    .report-table td {
      border: 1px solid black;
      padding: 8px;
      background: gray;
      opacity: 0.8;
      text-align: center;
    }
    .report-table tfoot td {
      font-weight: bold;
      background-color: green;
      color: white;
    }
    .report-table tbody tr:hover {
      background: #f1f1f1;
      cursor: default;
    }

    .report-section {
      margin-top: 40px;
      max-width: 900px;
      margin-left: auto;
      margin-right: auto;
    }

    .status-icon {
      font-size: 1.2em;
    }

    .no-data {
      background: #fef2f2;
      color: #c00;
      padding: 20px 30px;
      border-radius: 8px;
      border: 1px solid #f5c2c7;
      font-size: 1em;
      max-width: 900px;
      margin: 20px auto;
      text-align: center;
    }
  </style>

  <script>
    function fmt(d) {
      const pad = n => n.toString().padStart(2, '0');
      const yyyy = d.getFullYear();
      const mm   = pad(d.getMonth() + 1);
      const dd   = pad(d.getDate());
      const hh   = pad(d.getHours());
      const min  = pad(d.getMinutes());
      return `${yyyy}-${mm}-${dd}T${hh}:${min}`;
    }

    function applyPreset() {
      const sel = document.getElementById('preset');
      const p   = sel.value;
      let now   = new Date(),
          sd, ed;

      if (p === 'today') {
        sd = new Date(now); sd.setHours(0,  0, 0);
        ed = new Date(now); ed.setHours(23, 59, 0);
      }
      else if (p === 'yesterday') {
        now.setDate(now.getDate() - 1);
        sd = new Date(now); sd.setHours(0,  0,  0);
        ed = new Date(now); ed.setHours(23, 59,  0);
      }
      else if (p === 'week') {
        ed = new Date(now);        ed.setHours(23, 59, 0);
        sd = new Date(now);
        sd.setDate(now.getDate() - 7);
        sd.setHours(0,  0,  0);
      }
      else {
        return; 
      }

      document.getElementById('start_dt').value = fmt(sd);
      document.getElementById('end_dt').value   = fmt(ed);
      document.getElementById('preset_input').value = p;

      const logTypeSel = document.getElementById('log_type');
      document.getElementById('log_type_input').value = logTypeSel.value;

      document.getElementById('filterForm').submit();
    }

    function onLogTypeChange() {
      const lt = document.getElementById('log_type').value;
      document.getElementById('log_type_input').value = lt;
      document.getElementById('filterForm').submit();
    }
  </script>
</head>
<body>
  <?php include 'sidebar.php'; ?>

  <div class="main-content" style="padding:20px;">
    <h2>Báo Cáo Doanh Thu </h2>

    <form id="filterForm" method="GET">
  <div class="filters">
    <div class="field-from">
      <label for="start_dt">Từ Ngày</label>
      <input 
        type="datetime-local" 
        id="start_dt" 
        name="start_dt" 
        value="<?= toLocalInput($start_sql) ?>"
        required
      >
    </div>

    <div class="field-to">
      <label for="end_dt">Đến Ngày</label>
      <input 
        type="datetime-local" 
        id="end_dt" 
        name="end_dt" 
        value="<?= toLocalInput($end_sql) ?>"
        required
      >
    </div>

    <div class="field-preset">
      <select id="preset" name="preset" onchange="applyPreset()">
        <option value="today"     <?= $preset==='today'     ? 'selected':'' ?>>Hôm Nay</option>
        <option value="yesterday" <?= $preset==='yesterday' ? 'selected':'' ?>>Hôm Qua</option>
        <option value="week"      <?= $preset==='week'      ? 'selected':'' ?>>Tuần Này</option>
      </select>
    </div>

    <div class="field-logtype">
      <select id="log_type" name="log_type" onchange="onLogTypeChange()">
        <option value="orders"      <?= $log_type==='orders'      ? 'selected':'' ?>>Đơn</option>
        <option value="ingredients" <?= $log_type==='ingredients' ? 'selected':'' ?>>Nguyên Liệu</option>
      </select>
    </div>

    <input type="hidden" id="preset_input"   name="preset"   value="<?= htmlspecialchars($preset) ?>">
    <input type="hidden" id="log_type_input" name="log_type" value="<?= htmlspecialchars($log_type) ?>">
  </div>

  <div class="filter-button-container">
    <button type="submit">Lọc</button>
  </div>
</form>


    <?php if ($log_type === 'orders'): ?>
      <div class="report-section">
        <?php if (empty($sales)): ?>
          <div class="no-data">Không Có Doanh Thu Trong Khoảng Thời Gian Này.</div>
        <?php else: ?>
          <table class="report-table">
            <thead>
              <tr>
                <th>Sản Phẩm</th>
                <th>Đơn Giá</th>
                <th>Số Lượng</th>
                <th>Tổng</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($sales as $row): ?>
                <tr>
                  <td><?= htmlspecialchars($row['product_name']) ?></td>
                  <td><?= number_format($row['unit_price'], 2) ?></td>
                  <td><?= intval($row['total_qty']) ?></td>
                  <td><?= number_format($row['total_rev'], 2) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr>
                <td><strong>Tổng Cộng</strong></td>
                <td></td>
                <td><?= $grand_qty ?></td>
                <td><?= number_format($grand_rev, 2) ?></td>
              </tr>
            </tfoot>
          </table>
        <?php endif; ?>
      </div>

    <?php elseif ($log_type === 'ingredients'): ?>
      <div class="report-section">

        <?php if (empty($ingredientLogs)): ?>
          <div class="no-data">Không Có Lịch Sử Lấy Nguyên Liệu Trong Khoảng Thời Gian Này.</div>
        <?php else: ?>
          <table class="report-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Thời Gian Lấy</th>
                <th>Nguyên Liệu</th>
                <th>Thay Đổi (+/-)</th>
                <th>Đơn Vị</th>
                <th>Trước Khi Lấy</th>
                <th>Sau Khi Lấy</th>
                <th>Người Thực Hiện</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($ingredientLogs as $log): ?>
                <tr>
                  <td><?= htmlspecialchars($log['id']) ?></td>
                  <td><?= htmlspecialchars($log['created_at']) ?></td>
                  <td><?= htmlspecialchars($log['ingredient_name']) ?></td>
                  <td style="text-align:center;"><?= number_format($log['change_amount'], 2) ?></td>
                  <td><?= htmlspecialchars($log['unit_label']) ?></td>
                  <td style="text-align:center;"><?= number_format($log['before_qty'], 2) ?></td>
                  <td style="text-align:center;"><?= number_format($log['after_qty'], 2) ?></td>
                  <td><?= htmlspecialchars($log['taken_by'] ?? '—') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  </div>

  <script src="script.js"></script>
</body>
</html>
