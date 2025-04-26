<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'waiter') {
    header("Location: index.php");
    exit();
}

$current_category_id = isset($_GET['category']) ? intval($_GET['category']) : null;
$categories_result = $connection->query("SELECT * FROM table_categories");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

$tables = [];
if ($current_category_id !== null) {
    $stmt = $connection->prepare("SELECT * FROM tables WHERE table_category = ?");
    $stmt->bind_param("i", $current_category_id);
    $stmt->execute();
    $table_result = $stmt->get_result();
    $tables = $table_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tables (Waiter View)</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <div id="sidebar" class="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
    <ul>
      <li><a href="waiter.php">Waiter Page</a></li>
      <li><a href="table_management_waiter.php">Tables Overview</a></li>
      <li><a href="order_page.php">Take Orders</a></li>
    </ul>
  </div>

  <!-- Top horizontal category bar -->
  <div class="top-category-bar" style="position: fixed; top: 0; left: 50px; right: 0; background: #f0f0f0; padding: 10px 20px; display: flex; align-items: center; justify-content: space-between; z-index: 1000; border-bottom: 1px solid #ccc;">
    
    <div style="display: flex; gap: 12px; overflow-x: auto;">
      <?php foreach ($categories as $cat): ?>
        <a href="table_management_waiter.php?category=<?= $cat['id'] ?>"
          style="
            padding: 8px 14px;
            border-radius: 18px;
            text-decoration: none;
            white-space: nowrap;
            background-color: <?= ($current_category_id == $cat['id']) ? '#28a745' : '#e0e0e0' ?>;
            color: <?= ($current_category_id == $cat['id']) ? 'white' : '#333' ?>;
            font-weight: <?= ($current_category_id == $cat['id']) ? 'bold' : 'normal' ?>;
        ">
          <?= htmlspecialchars($cat['name']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Table display area -->
    <div class="table-grid" style="display: flex; flex-wrap: wrap; margin-top: 80px; gap: 16px; padding: 20px;">
    <?php foreach ($tables as $table): ?>
        <a href="waiter_ordering.php?table_id=<?= $table['id'] ?>"  
        class="product-card"
        style="text-decoration: none; color: inherit; width: 180px; height: 180px; border: 1px solid #ccc; border-radius: 12px; overflow: hidden; background: #fff; position: relative; display: flex; align-items: center; justify-content: center; transition: 0.3s ease;">

        <h4 style="margin: 0; font-size: 18px; text-align: center;">
            <?= htmlspecialchars($table['table_name']) ?>
        </h4>
        </a>
    <?php endforeach; ?>
    </div>


  <script src="script.js"></script>
</body>
</html>
