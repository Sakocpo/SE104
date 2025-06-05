<?php
session_start();
require_once 'config.php';

// Ensure admin is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Get category from URL parameter
$category_id = isset($_GET['category']) ? intval($_GET['category']) : null;
if (!$category_id) {
    echo "No category selected.";
    exit();
}

// Get category name for display
$stmt = $connection->prepare("SELECT name FROM option_categories WHERE id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$category = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$category) {
    echo "Invalid category.";
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['label'])) {
    $label = trim($_POST['label']);

    if ($label !== '') {
        $stmt = $connection->prepare("INSERT INTO options (label, type_id) VALUES (?, ?)");
        $stmt->bind_param("si", $label, $category_id);
        $stmt->execute();
        $stmt->close();
        header("Location: product_options_management.php?category=$category_id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Thêm Tùy Chọn</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container">
    <form method="POST">
        <h3 style="color: black;">Thêm Tùy Chọn</h3>
        <input type="text" name="label" placeholder="Tên Tùy Chọn" required>

        <button type="submit">Thêm</button>
        <a href="product_options_management.php?category=<?= $category_id ?>">
            <button type="button">Quay Lại</button>
        </a>
    </form>
</div>
</body>
</html>
