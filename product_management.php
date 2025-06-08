<?php
session_start();
require_once 'config.php';



if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$current_category_id = isset($_GET['category']) ? intval($_GET['category']) : null;
$categories_result = $connection->query("SELECT * FROM product_categories WHERE deleted = 0 ORDER BY name ASC");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

$products = [];
if ($current_category_id !== null) {
    $stmt = $connection->prepare("SELECT * FROM products WHERE category = ? AND deleted = 0");
    $stmt->bind_param("s", $current_category_id);
    $stmt->execute();
    $products_result = $stmt->get_result();
    $products = $products_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}



?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
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
                <a href="product_management.php?category=<?= $cat['id'] ?>"
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
            <a href="categories.php?entity=products" title="Add new category" style="font-size: 24px; text-decoration: none; font-weight: bold; color: #333;">➕</a>
        </div>

    </div>

    <div class="product-grid" style="display: flex; flex-wrap: wrap; margin-top: 80px; gap: 16px; padding: 20px;">
        <?php foreach ($products as $product): ?>
            <div class="product-card"
                onclick="window.location.href='product_info.php?id=<?= $product['id'] ?>'">
                <div class="product-block" >
                    <img src="<?= htmlspecialchars($product['image'] ?? 'placeholder.png') ?>" alt="">
                </div>
                <div class="text-block" style="padding: 10px;">
                    <h4 style="margin: 0 0 8px; text-align: center;"><?= htmlspecialchars($product['name']) ?></h4>
                    <p style="margin: 0; font-size: 1em; font-weight: 200; color: #007bff;">₫<?= number_format($product['price']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
            
    <div class="add-button">
            <!-- <button onclick="document.getElementById('add-product-form').style.display='block'">Add Product</button> -->
            <?php if($current_category_id): ?>
            <div style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
                <a href="add_product.php?category=<?= $current_category_id ?>">
                <button style="font-size: 28px; padding: 10px 18px; border-radius: 50%; background: #007bff; color: white; border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">+</button>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="script.js"></script>

</body>
</html>