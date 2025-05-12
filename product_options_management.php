<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$categories_result = $connection->query("SELECT * FROM option_categories");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);
$current_category_id = isset($_GET['category']) ? intval($_GET['category']) : null;

// Determine selected category
// $selected_type_id = $current_category_id ?? ($categories[0]['id'] ?? null);


// Get options of selected category
$options = [];
if ($current_category_id !== null) {
    $stmt = $connection->prepare("SELECT * FROM options WHERE type_id = ?");
    $stmt->bind_param("i", $current_category_id);
    $stmt->execute();
    $options_result = $stmt->get_result();
    while ($row = $options_result->fetch_assoc()) {
        $options[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Product Options Management</title>
    <link rel="stylesheet" href="style.css">
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



    <!-- Horizontal category bar -->
    <div class="top-category-bar" style="position: fixed; top: 0; left: 50px; right: 0; background: #f0f0f0; padding: 10px 20px; display: flex; align-items: center; justify-content: space-between; z-index: 1000; border-bottom: 1px solid #ccc;">

        <!-- Category list container -->
        <div style="display: flex; gap: 12px; overflow-x: auto;">
            <?php foreach ($categories as $cat): ?>
                <a href="product_options_management.php?category=<?= $cat['id'] ?>"
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
            <a href="categories.php?entity=options" title="Add new category" style="font-size: 24px; text-decoration: none; font-weight: bold; color: #333;">➕</a>
        </div>

    </div>

        <div class="option-grid" style="display: flex; flex-wrap: wrap; margin-top: 80px; gap: 16px; padding: 20px;">
            <?php foreach ($options as $option): ?>

                <div class="option-card"
                    onclick="window.location.href='option_info.php?id=<?= $option['id'] ?>'"
                    style="width: 180px; height: 180px; border: 1px solid #ccc; border-radius: 12px; overflow: hidden; cursor: pointer; background: #fff; position: relative; display: flex; align-items: center; justify-content: center;">

                    <h4 style="margin: 0; font-size: 18px; text-align: center;"><?= htmlspecialchars($option['label']) ?></h4>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="add-button">

    <?php if($current_category_id): ?>
    <div style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
        <a href="add_option.php?category=<?= $current_category_id ?>">
        <button style="font-size: 28px; padding: 10px 18px; border-radius: 50%; background: #007bff; color: white; border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">+</button>
    </div>
    <?php endif; ?>



    <script src="script.js"></script>
</body>
</html>
