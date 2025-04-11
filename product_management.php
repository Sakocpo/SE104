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
        $price = $_POST['price'];
        $connection->query("INSERT INTO products (name, category_id, price) VALUES ('$product_name', $category_id, $price)");
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
    <div class="content">
        <h1>Product Management</h1>
        <form method="POST">
            <h2>Add Category</h2>
            <input type="text" name="category_name" placeholder="Category Name" required>
            <button type="submit" name="add_category">Add Category</button>
        </form>

        <form method="POST">
            <h2>Delete Category</h2>
            <select name="category_id" required>
                <?php
                $categories = $connection->query("SELECT * FROM categories");
                while ($category = $categories->fetch_assoc()) {
                    echo "<option value='{$category['id']}'>{$category['name']}</option>";
                }
                ?>
            </select>
            <button type="submit" name="delete_category">Delete Category</button>
        </form>

        <form method="POST">
            <h2>Add Product</h2>
            <input type="text" name="product_name" placeholder="Product Name" required>
            <select name="category_id" required>
                <?php
                $categories = $connection->query("SELECT * FROM categories");
                while ($category = $categories->fetch_assoc()) {
                    echo "<option value='{$category['id']}'>{$category['name']}</option>";
                }
                ?>
            </select>
            <input type="number" name="price" placeholder="Price" required>
            <button type="submit" name="add_product">Add Product</button>
        </form>
    </div>
</body>
</html>