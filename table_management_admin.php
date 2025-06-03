<?php
session_start();
require_once 'config.php';



if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$current_category_id = isset($_GET['category']) ? intval($_GET['category']) : null;
$categories_result = $connection->query("SELECT * FROM table_categories");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

$tables = [];
if ($current_category_id !== null) {
    $stmt = $connection->prepare("SELECT * FROM tables WHERE table_category = ? AND deleted = 0");
    $stmt->bind_param("s", $current_category_id);
    $stmt->execute();
    $success = True;
    $table_result = $stmt->get_result();
    $tables = $table_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['add_table'])) {
      $table_number = $_POST['table_name'];
      $table_category = $_POST['table_category'];
      $description = isset($_POST['table_desc']) ? $_POST['table_desc'] : '';
      $active_bool = $_POST['active'];
      $stmt = $connection->prepare("INSERT INTO tables (table_name, table_category, table_desc, active) VALUES (?, ?, ?, ?)");
      // Adjusted bind_param:
      // "s" for product_name, "s" for category_id (if it's a string; change to "i" if it's int),
      // "i" for price, "s" for options, and "s" for description.
      $stmt->bind_param("sisi", $table_number, $table_category, $description, $active_bool);
      $stmt->execute();
      $stmt->close();
  }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tables Management</title>
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
  </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <!-- Horizontal category bar -->
    <div class="top-category-bar" style="position: fixed; top: 0; left: 50px; right: 0; background: #f0f0f0; padding: 10px 20px; display: flex; align-items: center; justify-content: space-between; z-index: 1000; border-bottom: 1px solid #ccc;">

        <!-- Category list container -->
        <div style="display: flex; gap: 12px; overflow-x: auto;">
            <?php foreach ($categories as $cat): ?>
                <a href="table_management_admin.php?category=<?= $cat['id'] ?>"
                    style="
                        padding: 8px 14px;
                        border-radius: 18px;
                        text-decoration: none;
                        white-space: nowrap;
                        background-color: <?= ($current_category_id == $cat['id']) ? '#007bff' : '#e0e0e0' ?>;
                        color: <?= ($current_category_id == $cat['id']) ? 'white' : '#333' ?>;
                        font-weight: <?= ($current_category_id == $cat['id']) ? 'bold' : 'normal' ?>;
                    ">
                    <?= htmlspecialchars($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Add button container -->
        <div>
            <a href="categories.php?entity=tables" title="Add new category" style="font-size: 24px; text-decoration: none; font-weight: bold; color: #333;">➕</a>
        </div>

    </div>

    <div class="table-grid" style="display: flex; flex-wrap: wrap; margin-top: 80px; gap: 16px; padding: 20px;">
        <?php foreach ($tables as $table): ?>
            <div class="table-card"
                onclick="window.location.href='table_info.php?id=<?= $table['id'] ?>'"
                style="width: 180px; height: 180px; border: 1px solid #ccc; border-radius: 12px; overflow: hidden; cursor: pointer; background: #fff; position: relative; display: flex; align-items: center; justify-content: center;">

                <!-- Table name centered -->
                <h4 style="margin: 0; font-size: 18px; text-align: center; color: brown;"><?= htmlspecialchars($table['table_name']) ?></h4>

                <!-- Active status indicator -->
                <div style="
                    position: absolute;
                    bottom: 10px;
                    right: 10px;
                    width: 18px;
                    height: 18px;
                    border: 2px solid #666;
                    border-radius: 4px;
                    background-color: <?= $table['active'] ? '#28a745' : '#ccc' ?>;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 14px;
                    color: white;
                ">
                    <?= $table['active'] ? '✓' : '' ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="add-button">
        <!-- <button onclick="document.getElementById('add-product-form').style.display='block'">Add Product</button> -->
        <?php if($current_category_id): ?>
        <div style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
            <a href="add_table.php?category=<?= $current_category_id ?>">
            <button style="font-size: 28px; padding: 10px 18px; border-radius: 50%; background: #007bff; color: white; border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">+</button>
        </div>
    <?php endif; ?>
</div>
<script src="script.js"></script>
</body>
</html>