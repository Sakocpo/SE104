<?php
session_start();

require_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'waiter') {
    header("Location: index.php");
    exit();
}

$categories_result = $connection->query("SELECT * FROM product_categories");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

$current_category_id = isset($_GET['category']) ? intval($_GET['category']) : ($categories[0]['id'] ?? null);

$products = [];
if ($current_category_id) {
    $stmt = $connection->prepare("SELECT * FROM products WHERE category = ? AND deleted = 0");
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
    <title>Take Away Order</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="waiter_ordering.css">
    <style>
        body {
            background-image: url("uploads/waiter-page.jpg");
            background-color: transparent;
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div id="notification" class="notification-popup"></div>
    <audio id="bell-sound" src="bell.mp3" preload="auto"></audio>

    <div class="top-category-bar" style="position: fixed; top: 0; left: 50px; right: 0; background: #f0f0f0; padding: 10px 20px; display: flex; align-items: center; justify-content: space-between; z-index: 1000; border-bottom: 1px solid #ccc;">
        <div style="display: flex; gap: 12px; overflow-x: auto;">
            <?php foreach ($categories as $cat): ?>
                <a href="take_away_order.php?category=<?=$cat['id']?>"
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
                <div class="product-block">
                    <img src="<?= htmlspecialchars($product['image'] ?? 'placeholder.png') ?>" alt=""
                        style="max-height: 100%; max-width: 100%;">
                </div>
                <div class="text-block" style="padding: 10px;">
                    <h4 style="margin: 0 0 8px;"><?= htmlspecialchars($product['name']) ?></h4>
                    <p style="margin: 0; font-weight: bold;">₫<?= number_format($product['price']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="options-popup" id="options-popup">
        <div class="popup-content" id="popup-content">
            <h3 id="popup-product-name"></h3>
            <div id="option-categories" class="option-category-row"></div>
            <div id="option-items" class="option-item-row"></div>
            <div class="quantity-control">
                <button type="button" onclick="adjustQty(-1)">−</button>
                <input id="quantity-input" type="number" value="1" min="0">
                <button type="button" onclick="adjustQty(1)">+</button>
            </div>
            <button type="button" onclick="addToOrder()" class="confirm-btn">Thêm Vào Đơn</button>
            <button type="button" onclick="closePopup()" class="cancel-btn">Hủy</button>
        </div>
    </div>

    <!-- Review Order Button -->
    <div class="review-order-btn-container">
        <button onclick="openReview()" class="review-btn">📝 Xem Lại Đơn</button>
    </div>

    <!-- Order Review Panel -->
    <div class="order-review" id="order-review">
        <div class="review-content">
            <h3 style="color: black;">Đơn Mang Đi</h3>
            <div id="order-summary-list" class="review-list"></div>
            <button onclick="submitOrder()" class="submit-order-btn">Gửi Đến Bếp</button>
            <button onclick="closeReview()" class="cancel-review-btn">Hủy</button>
        </div>
    </div>

    <!-- Cancel button -->
    <a href="waiter.php"
        style="
            position: fixed;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: #dc3545;
            color: white;
            padding: 12px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            z-index: 1001;
        ">
        Quay Lại
    </a>

    <script>
        window.products = <?= json_encode($products, JSON_HEX_TAG) ?>;
        window.isTakeAway = true; // Flag to indicate this is a take-away order
    </script>
    <script src="waiter_ordering.js"></script>
    <script src="script.js"></script>
</body>
</html>
