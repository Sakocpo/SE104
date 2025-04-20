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

// Get options
$options_result = mysqli_query($connection, "SELECT * FROM options");
$options_by_type = [];
while ($opt = mysqli_fetch_assoc($options_result)) {
    $options_by_type[$opt['type']][] = $opt;
}

$product_option_ids = explode(',', $product['options']);

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $name = $_POST['product_name'];
    $price = $_POST['product_price'];
    $description = $_POST['product_desc'] ?? '';
    $selected_options = [];
    $image_path = $product['image'];
    $upload_dir = 'uploads/';
    $image_path = '';

    if (!empty($_FILES['product_image']['name'])) {
        $upload_dir = 'uploads/';
        $image_path = $upload_dir . basename($_FILES['product_image']['name']);
        move_uploaded_file($_FILES['product_image']['tmp_name'], $image_path);
    }

    foreach ($options_by_type as $type => $_) {
        if (isset($_POST[$type . '_options'])) {
            $selected_options = array_merge($selected_options, $_POST[$type . '_options']);
        }
    }

    $options_str = implode(',', $selected_options);

    $stmt = $connection->prepare("UPDATE products SET name = ?, price = ?, options = ?, description = ?, image = ? WHERE id = ?");
    $stmt->bind_param("sisssi", $name, $price, $options_str, $description, $image_path, $product_id);
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
<body>
<div class="forms-container">
    <form id="edit-product-form" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="category_id" value="<?= htmlspecialchars($current_category_id) ?>">

        <h3>Edit Product</h3>

        <label for="product_name">Product Name:</label>
        <input type="text" name="product_name" value="<?= htmlspecialchars($product['name']) ?>" required>

        <label for="product_price">Price:</label>
        <input type="number" name="product_price" value="<?= htmlspecialchars($product['price']) ?>" required>

        <div id="option-blocks" style="margin: 5px 0;">
            <?php foreach ($options_by_type as $type => $optionList): ?>
                <div class="option-block" onclick="toggleCheckboxes('<?= $type ?>')">
                    <?= ucfirst($type) ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php foreach ($options_by_type as $type => $optionList): ?>
            <div id="checkboxes_<?= $type ?>" class="checkbox-wrapper" style="display:none; margin-top:10px;">
                <label style="font-weight:bold;"><?= ucfirst($type) ?> Options:</label>
                <div class="checkbox-grid">
                    <?php foreach ($optionList as $opt): ?>
                        <label>
                            <input type="checkbox"
                                   name="<?= $type ?>_options[]"
                                   value="<?= $opt['id'] ?>"
                                   <?= in_array($opt['id'], $product_option_ids) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($opt['label']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (!empty($product['image'])): ?>
            <div style="margin-bottom:10px;">
                <img src="<?= htmlspecialchars($product['image']) ?>" alt="Product Image" style="max-width: 150px;">
            </div>
        <?php endif; ?>
        <label for="product_image">Product Image:</label>
        <input type="file" name="product_image" accept="image/*">

        <label for="product_desc">Product Description:</label>
        <textarea name="product_desc"><?= htmlspecialchars($product['description']) ?></textarea>

        <button type="submit" name="update_product">Update Product</button>
        <a href="product_management.php?category=<?= $current_category_id ?>">
            <button type="button">Cancel</button>
        </a>
    </form>
</div>
    <script src="script.js"></script>
</body>
</html>
