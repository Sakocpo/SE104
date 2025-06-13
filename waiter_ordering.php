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

$categories_result = $connection->query("SELECT * FROM product_categories WHERE deleted = 0 ORDER BY name ASC");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

$current_category_id = isset($_GET['category']) ? intval($_GET['category']) : ($categories[0]['id'] ?? null);

// 1. Load table and its current order ID
$t = intval($_GET['table_id'] ?? 0);
$stmt = $connection->prepare("SELECT * FROM tables WHERE id = ?");
$stmt->bind_param("i", $t);
$stmt->execute();
$table = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$table) exit("Table not found");

$table_cat = $table['table_category'];
$table_name = $table['table_name'];

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
  <title>Ordering - Table <?= $table_id ?></title>
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

  <div id="sidebar" class="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
    <ul>
      <li><a href="waiter.php">Trang Phục Vụ</a></li>
      <li><a href="table_management_waiter.php">Order Tại Bàn</a></li>
    </ul>
  </div>
  
<div id="notification" class="notification-popup"></div>
<audio id="bell-sound" src="uploads/bell.mp3" preload="auto"></audio>

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
                <div class="text-block" style="padding: 10px;">
                    <h4 style="margin: 0 0 8px;"><?= htmlspecialchars($product['name']) ?></h4>
                    <p style="margin: 0; font-weight: bold;">₫<?= number_format($product['price']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="options-popup" id="options-popup">
  <div class="popup-content" id="popup-content">
    <!-- … inside your <div class="popup-content"> … -->
<h3 id="popup-product-name"></h3>

<!-- row of category "tabs" -->
<div id="option-categories" class="option-category-row"></div>

<!-- row of that category's label-pills -->
<div id="option-items"      class="option-item-row"></div>

<!-- qty and buttons below… -->
<div class="quantity-control">
  <button type="button" onclick="adjustQty(-1)">−</button>
  <input id="quantity-input" type="number" value="1" min="0">
  <button type="button" onclick="adjustQty(1)">+</button>
</div>
<button type="button" onclick="addToOrder()" class="confirm-btn">Thêm Vào Đơn</button>
<button type="button" onclick="closePopup()"   class="cancel-btn">Hủy</button>

    <div class="note-control" style="margin: 12px 0;">
      <label for="note-input" style="display: block; margin-bottom: 4px; font-weight: bold;">Ghi Chú</label>
      <textarea
        id="note-input"
        placeholder="Nhập ghi chú (nếu có)"
        maxlength="200"
        rows="2"
        style="width: 100%; padding: 6px; font-size: 1em; resize: vertical;"
      ></textarea>
    </div>

  </div>
</div>


<!-- Review Order Button -->
<div class="review-order-btn-container">
  <button onclick="openReview()" class="review-btn">📝 Xem Lại Đơn</button>
</div>

<!-- Order Review Panel -->
<div class="order-review" id="order-review">
  <div class="review-content">
    <h3 style="color: black; ">Đơn Của Bàn <?= $table_name ?></h3>

    <div id="order-summary-list" class="review-list"></div>

    <button onclick="submitOrder(<?= $table_id ?>)" class="submit-order-btn">Gửi Đến Bếp</button>
    <button onclick="closeReview()" class="cancel-review-btn">Hủy</button>
  </div>
</div>

  <a href="table_management_waiter.php?category=<?=$table_cat?>"
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
<?php
  $allOptionLabels = [];
  $res = $connection->query("SELECT id, label FROM options");
  while ($row = $res->fetch_assoc()) {
    $allOptionLabels[intval($row['id'])] = $row['label'];
  }
?>
<script>
  const TABLE_ID = <?= $table_id ?>;
  window.products       = <?= json_encode($products, JSON_HEX_TAG) ?>;
  window.allOptionLabels= <?= json_encode($allOptionLabels, JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="waiter_ordering.js"></script>
<script src="script.js"></script>
<script>
    const STORAGE_KEY = `pending_order_table_${TABLE_ID}`;

    function isReloadNavigation() {
      const nav = performance.getEntriesByType('navigation');
      if (nav.length) return nav[0].type === 'reload';
      if (performance.navigation)
        return performance.navigation.type === performance.navigation.TYPE_RELOAD;
      return false;
    }

    function savePendingOrder() {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(order));
    }

    function loadPendingOrder() {
      if (isReloadNavigation()) {
        order = [];
        localStorage.removeItem(STORAGE_KEY);
      } else {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (raw) {
          try { order = JSON.parse(raw) }
          catch { order = [] }
        }
      }
    }

    window.addEventListener('beforeunload', savePendingOrder);
    loadPendingOrder();
  </script>


</body>
</html>
