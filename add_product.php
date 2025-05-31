<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$current_category_id = isset($_GET['category']) ? intval($_GET['category']) : null;
if (!$current_category_id) {
    echo "No category selected.";
    exit();
}

// Fetch option categories and their options
$optCats = $connection
    ->query("SELECT id,name FROM option_categories ORDER BY name")
    ->fetch_all(MYSQLI_ASSOC);

// For each category, fetch its options
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['product_name'];
    $price = $_POST['product_price'];
    $description = $_POST['product_desc'] ?? '';
    $image_path = '';

    // Check for duplicate name only among non-deleted products
    $stmt = $connection->prepare("SELECT COUNT(*) as cnt FROM products WHERE name = ? AND deleted = 0");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['cnt'];
    $stmt->close();

    if ($count > 0) {
        $error = "Đã có món tên là \"{$name}\" trong menu, vui lòng chọn tên khác";
    }
    else
    {   
        if (!empty($_FILES['product_image']['name'])) {
            $upload_dir = 'uploads/';
            $image_path = $upload_dir . basename($_FILES['product_image']['name']);
            move_uploaded_file($_FILES['product_image']['tmp_name'], $image_path);
        }

        // Insert product
        $stmt = $connection->prepare("INSERT INTO products (name, category, price, description, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $name, $current_category_id, $price, $description, $image_path);
        $stmt->execute();
        $product_id = $stmt->insert_id;
        $stmt->close();

        // Insert selected options
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
<html>
<head>
    <title>Add Product</title>
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
        text-align: center;
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
<body>
<div class="forms-container">
    <?php if ($error): ?>
        <div class="error-popup">
            <?= htmlspecialchars($error) ?>
            <button style="margin-top: 10px; margin-bottom: 5px; padding: 5px; " onclick="this.parentElement.style.display='none'">Close</button>
        </div>
    <?php endif; ?>
    <div style="display:flex; gap:24px; align-items:flex-start; padding-top: 20px; justify-content: center; max-width: 1200px; margin: 0 auto;">

        <!-- Main Form -->
        <form id="add-product-form" method="POST" enctype="multipart/form-data" style="min-width: 300px;">
            <input type="hidden" name="category_id" value="<?= htmlspecialchars($current_category_id) ?>">
            <input type="hidden" name="option_ids" id="option_ids">

            <h3>Add Product</h3>

            <label for="product_name">Product Name:</label>
            <input type="text" name="product_name" required>

            <label for="product_price">Price:</label>
            <input type="number" name="product_price" required>

            <label for="product_image">Product Image:</label>
            <input type="file" name="product_image" accept="image/*">

            <label for="product_desc">Product Description:</label>
            <textarea name="product_desc"></textarea>

            <button type="submit" name="add_product">Add Product</button>
            <a href="product_management.php?category=<?= $current_category_id ?>">
                <button type="button">Cancel</button>
            </a>
        </form>
        <!-- Options Panel -->
        <div class="options-panel">
            <div class="options-header">Available Options</div>
            <input type="hidden" name="option_ids" id="option_ids">
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
document.addEventListener('DOMContentLoaded', function() {
    const selectedOptions = new Set();
    const optionIdsInput = document.getElementById('option_ids');

    document.querySelectorAll('.option-item').forEach(item => {
        item.addEventListener('click', function() {
            const id = this.dataset.id;
            if (this.classList.contains('selected')) {
                this.classList.remove('selected');
                selectedOptions.delete(id);
            } else {
                this.classList.add('selected');
                selectedOptions.add(id);
            }
            optionIdsInput.value = Array.from(selectedOptions).join(',');
        });
    });
});
</script>
</body>
</html>
