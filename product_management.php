<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $category_name = $_POST['category_name'];
        $connection->query("INSERT INTO categories (name) VALUES ('$category_name')");
    } elseif (isset($_POST['delete_category'])) {
        $category_id = $_POST['category_id'];
        $connection->query("DELETE FROM categories WHERE id = $category_id");
    } elseif (isset($_POST['add_product'])) {
        $product_name = $_POST['product_name'];
        $category_id = $_POST['category_id'];
        $price = $_POST['product_price'];

        // Initialize options based on category
        $options = '';
        if ($category_id === 'temp' && isset($_POST['temp_options'])) {
            $options = implode(',', $_POST['temp_options']);
        } elseif ($category_id === 'sugar' && isset($_POST['sugar_options'])) {
            $options = implode(',', $_POST['sugar_options']);
        }

        // Insert product into the database
        $stmt = $connection->prepare("INSERT INTO products (name, category, price, options) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $product_name, $category_id, $price, $options);
        $stmt->execute();
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    
    <div class="forms-container">

        <form id="add-product-form" method="POST">
            <h3>Add Product</h3>
            <label for="product_name">Product Name:</label>
            <input type="text" name="product_name" required>

            <label for="product_price">Price:</label>
            <input type="number" name="product_price" required>

            <label for="category_id">Category:</label>
            <select id="category_id" name="category_id" required onchange="handleCategoryChange()">
                <option value="temp">Nhiệt Độ</option>
                <option value="sugar">Đường</option>
            </select>

            <div id="temp-options" style="display:none;">
                <label><input type="checkbox" name="temp_options[]" value="cold"> Đá</label>
                <label><input type="checkbox" name="temp_options[]" value="hot"> Nóng</label>
            </div>

            <div id="sugar-options" style="display:none;">
                <label><input type="checkbox" name="sugar_options[]" value="sugar1"> Đắng</label>
                <label><input type="checkbox" name="sugar_options[]" value="sugar2"> Bình Thường </label>
                <label><input type="checkbox" name="sugar_options[]" value="sugar3"> Ngọt</label>
                <!-- <label><input type="checkbox" name="sugar_options[]" value="sugar4"> Sugar Option 4</label>
                <label><input type="checkbox" name="sugar_options[]" value="sugar5"> Sugar Option 5</label> -->
            </div>

            <label for="product_desc">Product Description:</label>
            <textarea name="product_desc"></textarea>

            <button type="submit" name="add_product">Add Product</button>
            <button type="back" onclick="document.getElementById('add-product-form').style.display='none'">Cancel</button>



        </form>

    </div>

    <script>
    function handleCategoryChange() {
        const category = document.getElementById('category_id').value;
        document.getElementById('temp-options').style.display = category === 'temp' ? 'block' : 'none';
        document.getElementById('sugar-options').style.display = category === 'sugar' ? 'block' : 'none';
    }
    </script>

    <div id="sidebar" class="sidebar">
        <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
        <ul>
            <li><a href="product_management.php">Product Management</a></li>
            <li><a href="inventory_management.php">Inventory Management</a></li>
            <li><a href="user_management.php">Users Management</a></li>
        </ul>
    </div>

    <div class="add-button">

        <button onclick="document.getElementById('add-category-form').style.display='block'">Add Category</button>
        <button onclick="document.getElementById('delete-category-form').style.display='block'">Delete Category</button>
        <button onclick="document.getElementById('add-product-form').style.display='block'">Add Product</button>

    </div>

</body>
</html>