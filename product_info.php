<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$product_id = isset($_GET['id']) ? intval($_GET['id']) : null;
if (!$product_id) {
    echo "No product selected.";
    exit();
}

// Get product details
$product_query = $connection->prepare("SELECT * FROM products WHERE id = ?");
$product_query->bind_param("i", $product_id);
$product_query->execute();
$product_result = $product_query->get_result();
$product = $product_result->fetch_assoc();

if (!$product) {
    echo "Product not found.";
    exit();
}

$current_category_id = $product['category'];


$optCats = $connection
  ->query("SELECT id,name FROM option_categories ORDER BY name")
  ->fetch_all(MYSQLI_ASSOC);


// ❷ For each category, fetch its options
$optionsByCat = [];
foreach ($optCats as $cat) {
  $stmt = $connection->prepare("
    SELECT id,label 
      FROM options 
     WHERE type_id = ? AND deleted = 0
     ORDER BY label
  ");
  $stmt->bind_param("i", $cat['id']);
  $stmt->execute();
  $result = $stmt->get_result();
  $optionsByCat[$cat['id']] = $result->fetch_all(MYSQLI_ASSOC);
  $result->close();
  $stmt->close();
}

// ❸ Fetch the option_ids this product currently has
$assigned = [];
$stmt = $connection->prepare("
  SELECT option_id 
    FROM product_options 
   WHERE product_id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
while ($r = $result->fetch_assoc()) {
  $assigned[] = (int)$r['option_id'];
}
$result->close();
$stmt->close();

// Handle update
// After form‐submit processing, make sure to handle the options:
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_product'])) {
  // Remove old assignments
  $del = $connection->prepare("DELETE FROM product_options WHERE product_id=?");
  $del->bind_param("i",$product_id);
  $del->execute();
  $del->close();

  // Insert the new ones
  if (!empty($_POST['option_ids'])) {
    $ins = $connection->prepare("
      INSERT INTO product_options (product_id, option_id)
      VALUES (?,?)
    ");
    // Split the comma-separated string into array
    $option_ids = explode(',', $_POST['option_ids']);
    foreach ($option_ids as $oid) {
      $oid = intval($oid);
      if ($oid > 0) { // Only insert valid option IDs
        $ins->bind_param("ii",$product_id,$oid);
        $ins->execute();
      }
    }
    $ins->close();
  }
}

$posted = trim($_POST['option_ids'] ?? '');
$ids = $posted === '' ? [] : explode(',', $posted);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_image'])) {
    $upd = $connection->prepare("UPDATE products SET image = '' WHERE id = ?");
    $upd->bind_param("i", $product_id);
    $upd->execute();
    $upd->close();
    // reload so $product['image'] is empty
    header("Location: product_info.php?id={$product_id}");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    // Soft delete - just mark as deleted
    $del = $connection->prepare("UPDATE products SET deleted = 1 WHERE id = ?");
    $del->bind_param("i", $product_id);
    $del->execute();
    $del->close();
    header("Location: product_management.php?category={$current_category_id}&deleted=1");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $name = $_POST['product_name'];
    $price = $_POST['product_price'];
    $description = $_POST['product_desc'] ?? '';
    $image_path = $product['image']; // Keep existing image by default

    if (!empty($_FILES['product_image']['name'])) {
        $upload_dir = 'uploads/';
        $image_path = $upload_dir . basename($_FILES['product_image']['name']);
        move_uploaded_file($_FILES['product_image']['tmp_name'], $image_path);
    }

    $stmt = $connection->prepare("UPDATE products SET name = ?, price = ?, description = ?, image = ? WHERE id = ?");
    $stmt->bind_param("sissi", $name, $price, $description, $image_path, $product_id);
    $stmt->execute();
    $stmt->close();

    header("Location: product_management.php?category=$current_category_id&updated=1");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Product</title>
    <link rel="stylesheet" href="style.css">
</head>
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
<body>
<div class="forms-container">
    <?php if ($error): ?>
        <div class="error-popup">
        <?= htmlspecialchars($error) ?>
        <button style="margin-top: 10px; margin-bottom: 5px; padding: 5px; " onclick="this.parentElement.style.display='none'">Close</button>
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
    <div style="display:flex; gap:24px; align-items:flex-start; padding-top: 20px">

      <!-- ◀ LEFT PANEL: image + clear ▶ -->
      <div style="width:250px;">
        <?php if ($product['image']): ?>
          <img src="<?=htmlspecialchars($product['image'])?>" style="max-width:100%;border:1px solid #ccc;padding:4px;">
          <button form="edit-product-form" name="clear_image" style="margin-top:8px;">
            Clear Image
          </button>
        <?php endif; ?>
      </div>

      <!-- ⬜ MIDDLE PANEL: the form (flex:1) ▶ -->
      <form
        id="edit-product-form"
        method="POST"
        enctype="multipart/form-data"
        style="flex:1; display:flex; flex-direction:column; gap:8px; width: 500px;"
      >
        <!-- your existing inputs -->
        <label>Tên sản phẩm:</label>
        <input type="text" name="product_name" value="<?=htmlspecialchars($product['name'])?>" required>

        <label>Giá Thành:</label>
        <input type="number" name="product_price" value="<?=htmlspecialchars($product['price'])?>" required>

        <label>Ảnh:</label>
        <input type="file" name="product_image" accept="image/*">

        <label>Mô tả:</label>
        <textarea name="product_desc"><?=htmlspecialchars($product['description'])?></textarea>

        <!-- only the hidden goes _inside_ the form -->
        <input type="hidden" name="option_ids" id="option-ids" value="<?=implode(',',$assigned)?>">

        <!-- your Update / Delete / Cancel buttons -->
          <button type="submit" name="update_product">Cập Nhật Sản Phẩm</button>
          <a href="product_info.php?id=<?= $product_id ?>&confirm_delete=1">
            <button type="button" style="background:red;color:white;">
              Xóa Sản Phẩm
            </button>
          </a>
          <a href="product_management.php?category=<?=$current_category_id?>">
            <button type="button">Hủy</button>
          </a>
      </form>

      <!-- ▶ RIGHT PANEL: options dropdowns (outside the form) ▶ -->
      <div class="options-panel" style="width:300px;">
        <div class="options-header">Tùy Chọn</div>
        <?php foreach ($optCats as $cat): ?>
          <details class="opt-category">
            <summary><?=htmlspecialchars($cat['name'])?></summary>
            <div class="option-list">
              <?php foreach ($optionsByCat[$cat['id']] as $opt): ?>
                <div
                  class="option-item<?=in_array($opt['id'],$assigned)?' selected':''?>"
                  data-option-id="<?=$opt['id']?>">
                  <?=htmlspecialchars($opt['label'])?>
                </div>
              <?php endforeach; ?>
            </div>
          </details>
        <?php endforeach; ?>
      </div>

</div>
    <script src="script.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', () => {
  const hidden = document.getElementById('option-ids');
  // initialize the hidden with any pre-selected
  const initial = Array.from(document.querySelectorAll('.option-item.selected'))
                       .map(el => el.dataset.optionId);
  hidden.value = initial.join(',');

  // attach click handler to each option‐block
  document.querySelectorAll('.option-item').forEach(el => {
    el.addEventListener('click', () => {
      const id = el.dataset.optionId;
      let list = hidden.value ? hidden.value.split(',') : [];
      if (el.classList.toggle('selected')) {
        // just selected: add
        list.push(id);
      } else {
        // deselected: remove
        list = list.filter(x => x !== id);
      }
      hidden.value = list.join(',');
    });
  });
});
</script>

</body>
</html>
