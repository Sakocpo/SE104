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



// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['product_name'];
    $price = $_POST['product_price'];
    $description = $_POST['product_desc'] ?? '';
    $image_path = '';

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
    header("Location: product_management.php?category=$current_category_id&added=1");
    exit();
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Add Product</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="forms-container">
    <form id="add-product-form" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="category_id" value="<?= htmlspecialchars($current_category_id) ?>">

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
</div>
    <script src="script.js"></script>
</body>
</html>
