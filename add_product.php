<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo 'Error 403: Unauthorized access';
    exit();
}

require_once 'config.php';

$current_category_id = isset($_GET['category']) ? intval($_GET['category']) : null;
if (!$current_category_id) {
    echo "No category selected.";
    exit();
}

// Fetch option categories and their options
$optCats = $connection
    ->query("SELECT id,name FROM option_categories WHERE deleted = 0 ORDER BY name")
    ->fetch_all(MYSQLI_ASSOC);

$prodCats = $connection
    ->query("SELECT id,name FROM product_categories WHERE deleted = 0 ORDER BY name")
    ->fetch_all(MYSQLI_ASSOC);

// For each category, fetch its options
$optionsByCat = [];
foreach ($optCats as $cat) {
    $stmt = $connection->prepare("SELECT id,label FROM options WHERE type_id = ? AND deleted = 0 ORDER BY label");
    $stmt->bind_param("i", $cat['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $optionsByCat[$cat['id']] = $result->fetch_all(MYSQLI_ASSOC);
    $result->close();
    $stmt->close();
}

// Initialize error message
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['product_name']);
    $price = floatval($_POST['product_price']);
    $description = $_POST['product_desc'] ?? '';
    $image_path = '';
    $category_id = intval($_POST['category_id']);

    // Validate price non-negative
    if ($price < 0) {
        $error = 'Giá không được âm.';
    }
    // Check for duplicate name if no prior error
    elseif (empty($error)) {
        $stmt = $connection->prepare("SELECT COUNT(*) as cnt FROM products WHERE name = ? AND deleted = 0");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['cnt'];
        $stmt->close();
        if ($count > 0) {
            $error = "Đã có món tên là \"{$name}\" trong menu, vui lòng chọn tên khác";
        }
    }

    // Proceed if no errors
    if (empty($error)) {
        if (!empty($_FILES['product_image']['name'])) {
            $upload_dir = 'uploads/';
            $image_path = $upload_dir . basename($_FILES['product_image']['name']);
            move_uploaded_file($_FILES['product_image']['tmp_name'], $image_path);
        }

        // Insert product
        $stmt = $connection->prepare(
            "INSERT INTO products (name, category, price, description, image) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssiss", $name, $category_id, $price, $description, $image_path);
        $stmt->execute();
        $product_id = $stmt->insert_id;
        $stmt->close();

        // Insert selected options
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

        header("Location: product_management.php?category=$current_category_id&added=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Product</title>
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
    .options-panel {
        border: 1px solid #ccc;
        background: #f8f8f8;
        border-radius: 4px;
        overflow: hidden;
        font-family: sans-serif;
    }

    .options-header {
        background: #ccc;
        color: black;
        padding: 12px;
        width: 300px;
        text-align: center;
        font-weight: bold;
    }

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
        text-align: center;
        cursor: pointer;
        user-select: none;
        transition: background 0.2s;
    }

    .option-item:hover {
        background: #f0f0f0;
    }

    .option-item.selected {
        background: #28a745;
        color: #fff;
    }
  </style>
</head>
<body>

  
  <div class="forms-container">
      <?php if (!empty($error)): ?>
        <div class="error-popup" id="serverError">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>
    <div style="display:flex; gap:24px; align-items:flex-start; padding-top:20px; justify-content:center; max-width:1200px; margin:0 auto;">

      <!-- Main Form -->
      <form id="add-product-form" method="POST" enctype="multipart/form-data" style="min-width:300px;">
        <input type="hidden" name="category_id" value="<?= htmlspecialchars($current_category_id) ?>">
        <input type="hidden" name="option_ids" id="option_ids">

        <h3>Thêm Món</h3>

        <label for="product_name">Tên Món:</label>
        <input type="text" name="product_name" required>

        <label>Danh Mục:</label>
        <select name="category_id" required>
          <?php foreach ($prodCats as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $cat['id']==$current_category_id?'selected':'' ?>>
              <?= htmlspecialchars($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label for="product_price">Giá Thành:</label>
        <input type="number" name="product_price" min="0" step="0.01" required>

        <label for="product_image">Ảnh:</label>
        <input type="file" name="product_image" accept="image/*">

        <label for="product_desc">Mô Tả:</label>
        <textarea name="product_desc"></textarea>

        <button type="submit" name="add_product">Thêm Món</button>
        <a href="product_management.php?category=<?= $current_category_id ?>">
          <button type="button">Hủy</button>
        </a>
      </form>

      <!-- Options Panel -->
      <div class="options-panel">
        <div class="options-header">Các Tùy Chọn</div>
        <?php foreach ($optCats as $cat): ?>
          <details class="opt-category">
            <summary><?= htmlspecialchars($cat['name']) ?></summary>
            <div class="option-list">
              <?php foreach ($optionsByCat[$cat['id']] as $opt): ?>
                <div class="option-item" data-id="<?= $opt['id'] ?>">
                  <?= htmlspecialchars($opt['label']) ?>
                </div>
              <?php endforeach; ?>
            </div>
          </details>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <script>
  // Auto-hide server error after 5s
  window.addEventListener('DOMContentLoaded', () => {
    const srv = document.getElementById('serverError');
    if (srv) setTimeout(() => srv.remove(), 5000);
  });

  document.addEventListener('DOMContentLoaded', () => {
    const selectedOptions = new Set();
    const optionIdsInput = document.getElementById('option_ids');
    document.querySelectorAll('.option-item').forEach(item => {
      item.addEventListener('click', function() {
        const id = this.dataset.id;
        if (this.classList.toggle('selected')) {
          selectedOptions.add(id);
        } else {
          selectedOptions.delete(id);
        }
        optionIdsInput.value = Array.from(selectedOptions).join(',');
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
