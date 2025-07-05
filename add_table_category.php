<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo 'Error 403: Unauthorized access';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category']) && !empty($_POST['category_name'])) {
        $name = trim($_POST['category_name']);
        $stmt = $connection->prepare("INSERT INTO table_categories (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $stmt->close();

        $_SESSION['success_message'] = "Category added successfully!";
        header("Location: add_table_category.php");
        exit();
    }

    if (isset($_POST['delete_category'])) {
        $category_id = $_POST['category_id'];

        $stmt = $connection->prepare("DELETE FROM table_categories WHERE id = ?");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['success_message'] = "Category deleted successfully!";
        header("Location: add_table_category.php");
        exit();
    }
}

// Fetch categories
$categories = $connection->query("SELECT * FROM table_categories")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container { max-width: 600px; margin: 30px auto; padding: 20px; background: #f9f9f9; border-radius: 10px; }
        h2 { text-align: center; }
        .category-list { margin-top: 20px; }
        .category-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding: 10px; background: #fff; border: 1px solid #ccc; border-radius: 5px; }
        .category-item form { display: inline; }
        .btn-danger { background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; }
        .btn-danger:hover { background: #c82333; }
        .btn-primary { background: #007bff; color: white; border: none; padding: 7px 15px; border-radius: 5px; cursor: pointer; }
        .btn-primary:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div id="sidebar" class="sidebar">
      <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
      <ul>
          <li><a href="admin.php">Admin Page</a></li>
          <li><a href="product_management.php">Product Management</a></li>
          <li><a href="inventory_management.php">Inventory Management</a></li>
          <li><a href="user_management.php">Users Management</a></li>
          <li><a href="table_management_admin.php">Tables Management</a></li>
          <li><a href="working_calendar.php">Working Calendar</a></li>
      </ul>
    </div>  

    <div class="container">
        <h2>Manage Categories</h2>

        <?php if (isset($_SESSION['success_message'])): ?>
            <script>
                alert("<?= $_SESSION['success_message'] ?>");
            </script>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <form method="POST" onsubmit="return confirm('Are you sure you want to add this category?')">
            <input type="text" name="category_name" placeholder="New category name" required>
            <button type="submit" name="add_category" class="btn-primary">Add Category</button>
        </form>

    <div class="category-list">
        <?php foreach ($categories as $cat): ?>
            <div class="category-item">
                <span><?= htmlspecialchars($cat['name']) ?></span>
                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this category?')">
                    <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                    <button type="submit" name="delete_category" class="btn-danger">Delete</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <div style="margin-top: 20px; text-align: center;">
        <a href="table_management_admin.php">← Back to Product Management</a>
    </div>
</div>

<script src="script.js"></script>

</body>
</html>
