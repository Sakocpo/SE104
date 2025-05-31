<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'waiter') {
    header("Location: index.php");
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
        IFNULL(SUM(CASE WHEN oi.served = 0 THEN 1 END), 0) AS unserved_count
      FROM tables t
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
  <title>Tables (Waiter View)</title>
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
    <div id="sidebar" class="sidebar">
        <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
        <ul>
            <li><a href="waiter.php">Trang Phục Vụ</a></li>
            <li><a href="table_management_waiter.php">Order Tại Bàn</a></li>
        </ul>
    </div>

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
  <script>
  const socket = new WebSocket("ws://localhost:8080");
  const bell = document.getElementById('bell-sound');
  const notifyBox = document.getElementById('notification');
  let hideNotifTimeout;

  socket.onopen = () => {
    console.log("✅ WebSocket connected to waiter page");
  };

  socket.onmessage = function(event) {
    console.log("📩 Raw WebSocket message received:", event.data);

    let data;
    try {
      data = JSON.parse(event.data);
    } catch (e) {
      console.error("❌ Failed to parse WebSocket JSON:", e);
      return;
    }

    console.log("🔍 Parsed data:", data);

    const msg = buildNotificationMessage(data);
    if (!msg) return;

    

    // Play bell + show popup
    bell.currentTime = 0;
    bell.play();
    notifyBox.textContent = msg;
    notifyBox.classList.remove("show");
    void notifyBox.offsetWidth;
    notifyBox.classList.add("show");

    // Hide after 3s
    if (hideNotifTimeout) clearTimeout(hideNotifTimeout);
    hideNotifTimeout = setTimeout(() => {
      notifyBox.classList.remove("show");
      hideNotifTimeout = null;
    }, 3000);
    // setTimeout(() => notifyBox.classList.remove("show"), 3000);

    // If the full order is served, mark the table as green
    if (data.type === "order" && data.table) {
      document.querySelectorAll('.table-card').forEach(card => {
        const name = card.querySelector('h4')?.textContent.trim();
        if (name === data.table) {
          card.classList.remove('occupied');
          card.classList.add('served');
          console.log("🟢 Marked", data.table, "as served");
        }
      });
    }
    

  //   if (data.type === "order") {
  //   const tableId = parseInt(data.table.replace("Table ", ""));
  //   const card = document.querySelector(`.table-card[data-table-id="${tableId}"]`);
  //   if (card) {
  //     card.classList.remove("occupied");
  //     card.classList.add("served");

  //     const bell = document.getElementById("bell-sound");
  //     const popup = document.getElementById("notification");

  //     if (popup) {
  //       popup.innerText = `Bàn ${tableId} đã pha chế xong`;
  //       popup.style.display = "block";
  //       setTimeout(() => popup.style.display = "none", 4000);
  //     }

  //     if (bell) bell.play();
  //   }
  // }

  };

  function buildNotificationMessage(data) {
    if (data.type === 'serve' && data.table && data.product) {
      return `${data.product} has been completed for ${data.table}`;
    }
    if (data.type === 'order' && data.table) {
      return ` Bàn ${data.table} đã pha chế xong`;
    }
    return null;
  }
  </script>


</body>
</html>



<!-- remove the checkbox -->
<!-- add in a textarea for waiter -->