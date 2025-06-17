<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'waiter') {
    http_response_code(403);
    echo 'Error 403: Unauthorized access';
    exit();
}

$current_category_id = isset($_GET['category']) ? intval($_GET['category']) : null;
$categories = $connection->query("SELECT * FROM table_categories")
                         ->fetch_all(MYSQLI_ASSOC);

$tables = [];
if ($current_category_id !== null) {
    // Fetch each table plus how many un‐served items it has
    $sql = "
      SELECT
        t.*,
        IFNULL(SUM(CASE WHEN oi.served = 0 AND o.status != 'deleted' THEN 1 END), 0) AS unserved_count
      FROM tables t
      LEFT JOIN orders o ON o.id = t.current_order_id
      LEFT JOIN order_items oi
        ON oi.order_id = t.current_order_id
      WHERE t.table_category = ? AND t.active = 1 AND t.deleted = 0
      GROUP BY t.id
    ";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $current_category_id);
    $stmt->execute();
    $tables = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tables Ordering</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      background-image: url("uploads/waiter-page.jpg");
      background-color: transparent;
      background-repeat: no-repeat;
      background-position: center;
      background-size: cover;
    }
    .table-card.occupied { background-color: yellow!important; }
    .table-card.served   { background-color: lightgreen!important; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  <div id="notification" class="notification-popup"></div>
  <audio id="bell-sound" src="uploads/bell.mp3" preload="auto"></audio>

  <!-- Top horizontal category bar -->
  <div class="top-category-bar">
    <div class="category-scroll">
      <?php foreach ($categories as $cat): ?>
        <a href="?category=<?= $cat['id'] ?>"
           class="category-btn <?= $current_category_id == $cat['id'] ? 'active' : '' ?>">
          <?= htmlspecialchars($cat['name']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="table-grid" style="margin-top:80px; padding:20px;">
    <?php foreach ($tables as $tbl):
      $hasOrder     = !empty($tbl['current_order_id']);
      $unserved     = intval($tbl['unserved_count']);
      $isOccupied   = $hasOrder && $unserved > 0;
      $isFullyServed = $hasOrder && $unserved === 0;

      // pick URL
      $href = $hasOrder
            ? "table_info_waiter.php?table_id={$tbl['id']}"
            : "waiter_ordering.php?table_id={$tbl['id']}";
    ?>
      <a href="<?= $href ?>"
        class="table-card <?= $isFullyServed ? 'served' : ($isOccupied?'occupied':'') ?>"
        data-table-id="<?= $tbl['id'] ?>">
        <h4 style="font-size: 1.4em;"><?= htmlspecialchars($tbl['table_name']) ?></h4>
      </a>
    <?php endforeach; ?>
  </div>

  <script src="script.js"></script>
  <script src="notif_script.js"></script>

</body>
</html>