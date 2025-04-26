<?php
session_start();

require_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'waiter') {
    header("Location: index.php");
    exit();
}

$table_id = isset($_GET['table_id']) ? intval($_GET['table_id']) : null;
if (!$table_id) {
    header("Location: table_management_waiter.php");
    exit();
}

$categories_result = $connection->query("SELECT * FROM product_categories");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

$current_category_id = isset($_GET['category']) ? intval($_GET['category']) : ($categories[0]['id'] ?? null);

$products = [];
if ($current_category_id) {
    $stmt = $connection->prepare("SELECT * FROM products WHERE category = ?");
    $stmt->bind_param("i", $current_category_id);
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
  <title>Ordering - Table <?= $table_id ?></title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="waiter_ordering.css">
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


<div class="top-category-bar" style="position: fixed; top: 0; left: 50px; right: 0; background: #f0f0f0; padding: 10px 20px; display: flex; align-items: center; justify-content: space-between; z-index: 1000; border-bottom: 1px solid #ccc;">
    
    <div style="display: flex; gap: 12px; overflow-x: auto;">
      <?php foreach ($categories as $cat): ?>
        <a href="waiter_ordering.php?table_id=<?=$table_id?>&category=<?=$cat['id']?>"
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


<div class="product-grid" style="display: flex; flex-wrap: wrap; margin-top: 80px; gap: 16px; padding: 20px;">
        <?php foreach ($products as $product): ?>
            <div class="product-card"
                onclick="openOptionsPopup(<?= htmlspecialchars(json_encode($product)) ?>)">
                <div class="product-block" >
                    <img src="<?= htmlspecialchars($product['image'] ?? 'placeholder.png') ?>" alt=""
                        style="max-height: 100%; max-width: 100%;">
                </div>
                <div style="padding: 10px;">
                    <h4 style="margin: 0 0 8px;"><?= htmlspecialchars($product['name']) ?></h4>
                    <p style="margin: 0; font-weight: bold; color: #007bff;">₫<?= number_format($product['price']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="options-popup" id="options-popup">
  <div class="popup-content" id="popup-content">
    <h3 id="popup-product-name">Product Name</h3>

    <!-- NEW: category buttons go here -->
    <div id="option-categories" class="option-category-row"></div>
    <!-- NEW: single options container -->
    <div id="option-items" class="option-content-row"></div>

    <div class="quantity-control">
      <button onclick="adjustQty(-1)">−</button>
      <input type="number" id="quantity-input" value="1" min="1" />
      <button onclick="adjustQty(1)">+</button>
    </div>
    <button onclick="addToOrder()" class="confirm-btn">Add to Order</button>
    <button onclick="closePopup()" class="cancel-btn">Cancel</button>
  </div>
</div>


<!-- Review Order Button -->
<div class="review-order-btn-container">
  <button onclick="openReview()" class="review-btn">📝 Review Order</button>
</div>

<!-- Order Review Panel -->
<div class="order-review" id="order-review">
  <div class="review-content">
    <h3>Order for Table <?= $table_id ?></h3>
    <div id="order-summary-list" class="review-list"></div>
    <button onclick="submitOrder(<?= $table_id ?>)" class="submit-order-btn">Send to Kitchen</button>
    <button onclick="closeReview()" class="cancel-review-btn">Cancel</button>
  </div>
</div>


<script src="waiter_ordering.js"></script>
<script src="script.js"></script>
</body>
</html>
