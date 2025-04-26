<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
  header("Location: index.php");
  exit();
}

$current_category_id = isset($_GET['category']) ? $_GET['category'] : null;
if (!$current_category_id) {
  echo "No category selected.";
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
  $username = $_POST['username'];
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];
  $image_path = null;

  if ($password !== $confirm_password) {
    echo "Passwords do not match.";
    exit();
  }

  $hashed_password = password_hash($password, PASSWORD_DEFAULT);

  // Optional image upload
  if (isset($_FILES['user_image']) && $_FILES['user_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
      mkdir($upload_dir, 0777, true);
    }

    $image_path = $upload_dir . basename($_FILES['user_image']['name']);
    move_uploaded_file($_FILES['user_image']['tmp_name'], $image_path);
  }

  $stmt = $connection->prepare("INSERT INTO users (username, password, role, image_path, user_category) VALUES (?, ?, ?, ?, ?)");
  $stmt->bind_param("ssssi", $username, $hashed_password, $_POST['role'], $image_path, $current_category_id);
  $stmt->execute();
  $stmt->close();

  header("Location: user_management.php?category=$current_category_id&added=1");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Add User</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="forms-container">
    <form id="add-user-form" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="user_category" value="<?= htmlspecialchars($current_category_id) ?>">

      <h3>Add User</h3>

      <label for="username">Username</label>
      <input type="text" name="username" required>

      <label for="password">Password</label>
      <input type="password" name="password" required>

      <label for="confirm_password">Confirm Password</label>
      <input type="password" name="confirm_password" required>

      <label for="role">Role</label>
      <select name="role" required>
        <option value="admin">Admin</option>
        <option value="waiter">Waiter</option>
      </select>

      <label for="user_image">User Image (optional)</label>
      <input type="file" name="user_image" accept="image/*">

      <button type="submit" name="add_user">Add User</button>
      <a href="user_management.php?category=<?= $current_category_id ?>">
        <button type="button">Cancel</button>
      </a>
    </form>
  </div>

  <script src="script.js"></script>
</body>
</html>
