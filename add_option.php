<?php
session_start();
require_once 'config.php';

// Ensure admin is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo 'Error 403: Unauthorized access';
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


    $stmt = $connection->prepare("SELECT COUNT(*) as cnt FROM options WHERE label = ? AND deleted = 0");
    $stmt->bind_param("s", $label);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['cnt'];
    $stmt->close();

    if ($count > 0) {
        $error = "Tùy chọn \"{$label}\" đã tồn tại, vui lòng chọn tên khác";
    }

    else
    {
        if ($label !== '') {
            $stmt = $connection->prepare("INSERT INTO options (label, type_id) VALUES (?, ?)");
            $stmt->bind_param("si", $label, $category_id);
            $stmt->execute();
            $stmt->close();
            header("Location: product_options_management.php?category=$category_id");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Tùy Chọn</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container">
    <?php if ($error): ?>
        <div class="error-popup">
        <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <form method="POST">
        <h3 style="color: black;">Thêm Tùy Chọn</h3>
        <input type="text" name="label" placeholder="Tên Tùy Chọn" required>

        <button type="submit">Thêm</button>
        <a href="product_options_management.php?category=<?= $category_id ?>">
            <button type="button">Quay Lại</button>
        </a>
    </form>
</div>
<script>
    window.addEventListener('DOMContentLoaded', () => {
      const err = document.getElementById('serverError');
      if (err) setTimeout(() => err.remove(), 4000);
    });
  </script>
</body>
</html>
