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



// Handle update

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $stmt = $connection->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->close();

    header("Location: product_management.php?category=$current_category_id&deleted=1");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $name = $_POST['product_name'];
    $price = $_POST['product_price'];
    $description = $_POST['product_desc'] ?? '';
    $image_path = $product['image'];
    $upload_dir = 'uploads/';
    $image_path = '';

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
<body>
<div class="forms-container">
    <form id="edit-product-form" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="category_id" value="<?= htmlspecialchars($current_category_id) ?>">

        <h3>Edit Product</h3>

        <label for="product_name">Product Name:</label>
        <input type="text" name="product_name" value="<?= htmlspecialchars($product['name']) ?>" required>

        <label for="product_price">Price:</label>
        <input type="number" name="product_price" value="<?= htmlspecialchars($product['price']) ?>" required>

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

        <button type="submit"
                name="delete_product"
                style="background-color: red; color: white; margin-top: 10px;"
                onclick="return confirm('Are you sure you want to delete this product?');">
            Delete Product
        </button>


        <a href="product_management.php?category=<?= $current_category_id ?>">
            <button type="button">Cancel</button>
        </a>
    </form>
</div>
    <script src="script.js"></script>
</body>
</html>
