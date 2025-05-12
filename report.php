<?php
session_start();
require_once 'config.php';

// only admin
if (!isset($_SESSION['user'],$_SESSION['user']['role'])
  || $_SESSION['user']['role']!=='admin') {
  header("Location:index.php");
  exit();
}

// Helpers for formatting
function toLocalInput(string $dt): string {
    // expects "YYYY-MM-DD HH:MM:SS", returns "YYYY-MM-DDTHH:MM"
    return str_replace(' ', 'T', substr($dt,0,16));
}
function fromLocalInput(string $s): string {
    // "YYYY-MM-DDTHH:MM" -> "YYYY-MM-DD HH:MM:00"
    return str_replace('T',' ',$s).':00';
}

// 1️⃣ Determine date‐time range & preset
$now = new DateTimeImmutable();

$raw_start = $_GET['start_dt'] ?? '';
$raw_end   = $_GET['end_dt']   ?? '';
$preset    = $_GET['preset']   ?? '';

if (!$raw_start || !$raw_end) {
    // default today
    $startObj = $now->setTime(0,0,0);
    $endObj   = $now->setTime(23,59,59);
    $preset = 'today';
} else {
    $startObj = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $raw_start)
                ?: $now->setTime(0,0,0);
    $endObj   = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $raw_end)
                ?: $now->setTime(23,59,59);
}
// detect if a known preset
if (!$raw_start && !$raw_end) {
    // already defaulted to today
} elseif ($startObj->format('Y-m-d')=== $now->format('Y-m-d')
       && $endObj  ->format('Y-m-d')=== $now->format('Y-m-d')) {
    $preset='today';
} elseif ($startObj->format('Y-m-d')=== $now->modify('-1 day')->format('Y-m-d')
       && $endObj  ->format('Y-m-d')=== $now->modify('-1 day')->format('Y-m-d')) {
    $preset='yesterday';
} elseif ($startObj->format('Y-m-d')=== $now->modify('last sunday')->format('Y-m-d')
       && $endObj  ->format('Y-m-d')=== $now->modify('next saturday')->format('Y-m-d')) {
    $preset='week';
}

// 2️⃣ Fetch sales in that span
$start_sql = $startObj->format('Y-m-d H:i:s');
$end_sql   = $endObj  ->format('Y-m-d H:i:s');

$stmt = $connection->prepare("
  SELECT
    p.name           AS product_name,
    p.price          AS unit_price,
    SUM(oi.quantity) AS total_qty,
    SUM(oi.quantity * p.price) AS total_rev
  FROM orders o
  JOIN order_items oi ON o.id = oi.order_id
  JOIN products p     ON oi.product_id = p.id
  WHERE o.created_at BETWEEN ? AND ?
  GROUP BY p.id, p.name, p.price
  ORDER BY p.name
");
$stmt->bind_param("ss", $start_sql, $end_sql);
$stmt->execute();
$result = $stmt->get_result();

$grand_qty = 0;
$grand_rev = 0.0;
$sales = [];
while ($r = $result->fetch_assoc()) {
  $sales[]     = $r;
  $grand_qty  += (int)$r['total_qty'];
  $grand_rev  += (float)$r['total_rev'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sales Report</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .filters { margin:20px auto; max-width:600px; display:flex; gap:12px; align-items:center; }
    .filters input, .filters select, .filters button { padding:6px; }
    .report-table { width:100%; border-collapse:collapse; margin:20px auto; max-width:600px; }
    .report-table th, .report-table td { border:1px solid #ccc; padding:8px; text-align:left; border-color: black; }
    .report-table tfoot td { font-weight:bold; background:#fafafa; }
    .eod-btn { margin:0 auto 20px; display:block; padding:10px 16px; background:#dc3545; color:#fff; border:none; cursor:pointer; }
    select {width: 300px;}
  </style>
  <script>
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
      function fmt(d){ return d.toISOString().slice(0,16); }
      document.getElementById('start_dt').value = fmt(sd);
      document.getElementById('end_dt').value   = fmt(ed);
      document.getElementById('preset_input').value = p;
      document.getElementById('filterForm').submit();
    }
    function confirmEOD() {
      if (confirm("Archive today's sales and clear all orders?")) {
        window.location='end_of_day.php';
      }
    }
  </script>
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
      </ul>
    </div>


  <div class="main-content" style="padding:20px;">
    
    <h2>Sales Report</h2>
    <form id="filterForm" method="GET" class="filters">
      <div class="date-range">
        <label>From 
          <input type="datetime-local" id="start_dt" name="start_dt"
                value="<?= toLocalInput($start_sql) ?>">
        </label>
        <label>To   
          <input type="datetime-local" id="end_dt"   name="end_dt"
                value="<?= toLocalInput($end_sql) ?>">
        </label>
      </div>
      <select id="preset" onchange="applyPreset()">
        <option value="today"     <?= $preset==='today'     ? 'selected':''?>>Today</option>
        <option value="yesterday" <?= $preset==='yesterday' ? 'selected':''?>>Yesterday</option>
        <option value="week"      <?= $preset==='week'      ? 'selected':''?>>This Week</option>
      </select>
      <input type="hidden" id="preset_input" name="preset" value="<?= htmlspecialchars($preset) ?>">
      <button type="submit">Filter</button>
    </form>

    <!-- <button class="eod-btn" onclick="confirmEOD()">End of Day</button> -->

    <?php if (empty($sales)): ?>
      <p style="text-align:center;">No sales in this period.</p>
    <?php else: ?>
      <table class="report-table">
        <thead>
          <tr>
            <th>Product</th><th>Unit Price</th><th>Qty</th><th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($sales as $row): ?>
          <tr>
            <td><?=htmlspecialchars($row['product_name'])?></td>
            <td><?=number_format($row['unit_price'],2)?></td>
            <td><?=intval($row['total_qty'])?></td>
            <td><?=number_format($row['total_rev'],2)?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td><strong>Grand Total</strong></td><td></td>
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
