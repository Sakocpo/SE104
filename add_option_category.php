<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_name'])) {
    $name = trim($_POST['category_name']);
    if ($name !== '') {
        $stmt = $connection->prepare("INSERT INTO option_categories (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $stmt->close();
        header("Location: product_options_management.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Option Category</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container">
    <form method="POST">
        <h3>Add New Option Category</h3>
        <input type="text" name="category_name" placeholder="Category name" required>
        <button type="submit">Add Category</button>
        <a href="product_options_management.php"><button type="button">Cancel</button></a>
    </form>
</div>
</body>
</html>
