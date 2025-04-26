<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$current_category_id = isset($_GET['category']) ? $_GET['category'] : null;
$categories = [
    ['id' => 'admin', 'name' => 'Admin'],
    ['id' => 'waiter', 'name' => 'Waiter']
];


$users = [];
if ($current_category_id !== null) {
    $stmt = $connection->prepare("SELECT * FROM users WHERE role = ?");
    $stmt->bind_param("s", $current_category_id);
    $stmt->execute();
    $users_result = $stmt->get_result();
    $users = $users_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
// Add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    if (!empty($username) && !empty($_POST['password']) && in_array($role, $roles)) {
        $stmt = $connection->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password, $role);
        $stmt->execute();
        $stmt->close();
        header("Location: user_management.php?role=$role");
        exit();
    }
}

// Delete user
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $connection->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: user_management.php?role=$current_role");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <link rel="stylesheet" href="style.css">
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
          <li><a href="product_options_management.php">Product Options</a></li>
          <li><a href="working_calendar.php">Working Calendar</a></li>
      </ul>
    </div>

    <!-- Horizontal category bar -->
    <div class="top-category-bar" style="position: fixed; top: 0; left: 50px; right: 0; background: #f0f0f0; padding: 10px 20px; display: flex; align-items: center; justify-content: space-between; z-index: 1000; border-bottom: 1px solid #ccc;">

        <!-- Category list container -->
        <div style="display: flex; gap: 12px; overflow-x: auto;">
            <?php foreach ($categories as $cat): ?>
                <a href="user_management.php?category=<?= $cat['id'] ?>"
                    style="
                        padding: 8px 14px;
                        border-radius: 18px;
                        text-decoration: none;
                        white-space: nowrap;
                        background-color: <?= ($current_category_id == $cat['id']) ? '#007bff' : '#e0e0e0' ?>;
                        color: <?= ($current_category_id == $cat['id']) ? 'white' : '#333' ?>;
                        font-weight: <?= ($current_category_id == $cat['id']) ? 'bold' : 'normal' ?>;
                    ">
                    <?= htmlspecialchars($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="user-grid" style="display: flex; flex-wrap: wrap; margin-top: 80px; gap: 16px; padding: 20px;">
        <?php foreach ($users as $user): ?>
            <div class="product-card"
                onclick="window.location.href='user_info.php?id=<?= $user['id'] ?>'">
                <div class="product-block" >
                    <img src="<?= htmlspecialchars($user['image'] ?? 'placeholder.png') ?>" alt=""
                        style="max-height: 100%; max-width: 100%;">
                </div>
                <div style="padding: 10px;">
                    <h4 style="margin: 0 0 8px;"><?= htmlspecialchars($user['username']) ?></h4>
                </div>
            </div>
        <?php endforeach; ?>
    </div>


    <!-- Add user form -->
    <div class="add-button">

        <?php if($current_category_id): ?>
        <div style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
            <a href="add_user.php?category=<?= $current_category_id ?>">
            <button style="font-size: 28px; padding: 10px 18px; border-radius: 50%; background: #007bff; color: white; border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">+</button>
        </div>
    <?php endif; ?>


    </div>


    <script src="script.js"></script>
</body>
</html>
