<?php
session_start();
require_once 'config.php';

// Ensure admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$product_id = isset($_GET['id']) ? intval($_GET['id']) : null;
if (!$product_id) {
    echo "No product selected.";
    exit();
}

// Fetch product
$stmt = $connection->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();
if (!$product) {
    echo "Product not found.";
    exit();
}

$current_category_id = $product['category'];

$prodCats = $connection->query("SELECT id,name FROM product_categories WHERE deleted = 0 ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Fetch option categories and their options
$optCats = $connection->query("SELECT id,name FROM option_categories WHERE deleted = 0 ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$optionsByCat = [];
foreach ($optCats as $cat) {
    $stmt = $connection->prepare(
        "SELECT id,label FROM options WHERE type_id = ? AND deleted = 0 ORDER BY label"
    );
    $stmt->bind_param("i", $cat['id']);
    $stmt->execute();
    $optionsByCat[$cat['id']] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Fetch assigned options
$assigned = [];
$stmt = $connection->prepare("SELECT option_id FROM product_options WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $assigned[] = (int)$r['option_id'];
}
$stmt->close();

// Initialize error message
$error = '';

// Handle clear image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_image'])) {
    $upd = $connection->prepare("UPDATE products SET image = '' WHERE id = ?");
    $upd->bind_param("i", $product_id);
    $upd->execute();
    $upd->close();
    header("Location: product_info.php?id={$product_id}");
    exit();
}

// Handle delete product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $del = $connection->prepare("UPDATE products SET deleted = 1 WHERE id = ?");
    $del->bind_param("i", $product_id);
    $del->execute();
    $del->close();
    header("Location: product_management.php?category={$current_category_id}&deleted=1");
    exit();
}

// Handle update product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $name        = trim($_POST['product_name']);
    $price       = floatval($_POST['product_price']);
    $description = $_POST['product_desc'] ?? '';
    $new_category= intval($_POST['product_category']);
    $image_path  = $product['image'];

    // Validate price
    if (!is_numeric($_POST['product_price']) || $price < 0) {
        $error = 'Giá không được âm.';
    }
    // Validate duplicate name
    if (empty($error)) {
        $chk = $connection->prepare(
            "SELECT COUNT(*) FROM products WHERE name = ? AND deleted = 0 AND id <> ?"
        );
        $chk->bind_param("si", $name, $product_id);
        $chk->execute();
        $chk->bind_result($cnt);
        $chk->fetch();
        $chk->close();
        if ($cnt > 0) {
            $error = "Đã có món tên là \"{$name}\" trong menu.";
        }
    }

    // If no error, proceed
    if (empty($error)) {
        // Handle image upload
        if (!empty($_FILES['product_image']['name'])) {
            $upload_dir = 'uploads/';
            $image_path = $upload_dir . basename($_FILES['product_image']['name']);
            move_uploaded_file($_FILES['product_image']['tmp_name'], $image_path);
        }

        // Remove old options
        $del = $connection->prepare("DELETE FROM product_options WHERE product_id = ?");
        $del->bind_param("i", $product_id);
        $del->execute();
        $del->close();

        // Insert new options
        if (!empty($_POST['option_ids'])) {
            $ins = $connection->prepare(
                "INSERT INTO product_options (product_id, option_id) VALUES (?, ?)"
            );
            $option_ids = explode(',', $_POST['option_ids']);
            foreach ($option_ids as $oid) {
                $oid = intval($oid);
                if ($oid > 0) {
                    $ins->bind_param("ii", $product_id, $oid);
                    $ins->execute();
                }
            }
            $ins->close();
        }

        // Update product
        $stmt = $connection->prepare(
            "UPDATE products SET name = ?, category = ?, price = ?, description = ?, image = ? WHERE id = ?"
        );
        $stmt->bind_param("siissi", $name, $new_category, $price, $description, $image_path, $product_id);
        $stmt->execute();
        $stmt->close();

        header("Location: product_management.php?category=$current_category_id&updated=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Product</title>
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
        /* panel wrapper */
    
    .opt-category {
    border-top: 1px solid #ddd;
    }

    .opt-category summary {
    background: #e0e0e0;
    color: brown;
    padding: 10px;
    text-align: center;           /* center the category name */
    cursor: pointer;
    position: relative;
    }

    .opt-category summary::-webkit-details-marker {
    display: none;
    }
    .opt-category summary::after {
    content: "▾";
    position: absolute;
    right: 12px;
    }
    .opt-category[open] summary::after {
    content: "▴";
    }

    .option-list {
    padding: 0;
    margin: 0;
    }

    .option-item {
    background: #fff;
    padding: 10px;
    border-top: 1px solid black;
    color: black;
    text-align: center;           /* center the label text */
    cursor: pointer;
    user-select: none;
    transition: background 0.2s;
    }

    .option-item:hover {
    background: #f0f0f0;
    }

    /* when selected, turn green with white text */
    .option-item.selected {
    background: #28a745;
    color: #fff;
    }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>

  <?php if ($error): ?>
    <div class="error-popup" id="serverError">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['confirm_delete'])): ?>
    <div class="confirm-popup">
      <h3>Xác Nhận Xóa</h3>
      <p>Bạn có chắc chắn muốn xóa "<?= htmlspecialchars($product['name']) ?>"?</p>
      <form method="POST">
        <button type="submit" name="delete_product" class="confirm-btn">Xác Nhận</button>
        <button type="button" class="cancel-btn" onclick="window.location.href='product_info.php?id=<?= $product_id ?>'">Hủy</button>
      </form>
    </div>
  <?php endif; ?>

  <div class="forms-container" style="display:flex; gap:24px; align-items:flex-start; padding-top:20px;">

    <!-- LEFT PANEL -->
    <div style="width:250px;">
      <?php if ($product['image']): ?>
        <img src="<?= htmlspecialchars($product['image']) ?>" style="max-width:100%;border:1px solid #ccc;padding:4px;">
        <button form="edit-product-form" name="clear_image" style="margin-top:8px;">Xóa Ảnh</button>
      <?php endif; ?>
    </div>

    <!-- MIDDLE PANEL -->
    <form id="edit-product-form" method="POST" enctype="multipart/form-data" style="flex:1; display:flex; flex-direction:column; gap:8px;">
      <label>Tên sản phẩm:</label>
      <input type="text" name="product_name" value="<?= htmlspecialchars($product['name']) ?>" required>


      <label>Danh Mục:</label>
      <select name="product_category">
        <?php foreach ($prodCats as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= $cat['id']==$current_category_id?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Giá Thành:</label>
      <input type="number" name="product_price" min="0" step="0.01" value="<?= htmlspecialchars($product['price']) ?>" required>

      <label>Ảnh:</label>
      <input type="file" name="product_image" accept="image/*">

      <label>Mô tả:</label>
      <textarea name="product_desc"><?= htmlspecialchars($product['description']) ?></textarea>

      <input type="hidden" name="option_ids" id="option-ids" value="<?= implode(',', $assigned) ?>">

      <button type="submit" name="update_product">Cập Nhật Sản Phẩm</button>
      <a href="product_info.php?id=<?= $product_id ?>&confirm_delete=1"><button type="button" style="background:red;color:white;">Xóa Sản Phẩm</button></a>
      <a href="product_management.php?category=<?= $current_category_id ?>"><button type="button">Hủy</button></a>
    </form>

    <!-- RIGHT PANEL -->
    <div class="options-panel" style="width:300px;">
      <div class="options-header">Các Tùy Chọn</div>
      <?php foreach ($optCats as $cat): ?>
        <details class="opt-category">
          <summary><?= htmlspecialchars($cat['name']) ?></summary>
          <div class="option-list">
            <?php foreach ($optionsByCat[$cat['id']] as $opt): ?>
              <div class="option-item<?= in_array($opt['id'], $assigned) ? ' selected' : '' ?>" data-option-id="<?= $opt['id'] ?>">
                <?= htmlspecialchars($opt['label']) ?>
              </div>
            <?php endforeach; ?>
          </div>
        </details>
      <?php endforeach; ?>
    </div>

  </div>
  <script src="script.js"></script>
  <script>
    // Auto-hide server error
    window.addEventListener('DOMContentLoaded', () => {
      const srv = document.getElementById('serverError');
      if (srv) setTimeout(() => srv.remove(), 4000);
    });

    // Options selection
    document.addEventListener('DOMContentLoaded', () => {
      const hidden = document.getElementById('option-ids');
      const selected = new Set(hidden.value.split(',').filter(Boolean));
      document.querySelectorAll('.option-item').forEach(item => {
        item.addEventListener('click', () => {
          const id = item.dataset.optionId;
          if (item.classList.toggle('selected')) selected.add(id);
          else selected.delete(id);
          hidden.value = Array.from(selected).join(',');
        });
      });
      // Prevent negative price client-side
      const priceInput = document.querySelector('input[name="product_price"]');
      priceInput.addEventListener('input', () => {
        if (parseFloat(priceInput.value) < 0) priceInput.value = 0;
      });
    });
  </script>
</body>
</html>
