<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
// Handle new category submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_name'])) {
    $name = trim($_POST['category_name']);
    if ($name !== '') {
        $stmt = $connection->prepare("INSERT INTO option_categories (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $stmt->close();
        header("Location: add_option_category.php");
        exit();
    }
}

// Fetch all categories to display below
$categories = [];
$res = $connection->query("SELECT * FROM option_categories ORDER BY id ASC");
while ($row = $res->fetch_assoc()) {
    $categories[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Option Category</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .category-list {
      border-top: 1px solid #ccc;
      padding-top: 20px;
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
    }
    .category-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      margin-top: 10px;
      border-bottom: 1px solid #eee;
    }
    .category-item span {
      font-size: 1em;
      width: 200px;
    }
    .category-item button {
      color: white;
      background: red;
      width: 100px;
      font-size: 1.2em;
      cursor: pointer;
      margin-bottom: 0px;
    }
    .category-item button:hover {
      color: yellow;
    }
  </style>
  <script>
    function confirmDelete(catId) {
      if (confirm("Are you sure you want to delete this category?")) {
        window.location.href = 'delete_option_category.php?id=' + catId;
      }
    }
  </script>
</head>
<body>
  <div class="form-container">
    <form method="POST">
      <h3>Add New Option Category</h3>
      <input type="text" name="category_name" placeholder="Category name" required>
      <button type="submit">Add Category</button>
      <a href="product_options_management.php">
        <button type="button">Cancel</button>
      </a>
    </form>

    <div class="category-list">
        <h4>Existing Categories</h4>
        <?php if (empty($categories)): ?>
        <p><em>No categories yet.</em></p>
        <?php else: ?>
        <?php foreach ($categories as $cat): ?>
            <div class="category-item">
            <span><?= htmlspecialchars($cat['name']) ?></span>
            <button onclick="confirmDelete(<?= $cat['id'] ?>)">Delete</button>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
  </div>
</body>
</html>
