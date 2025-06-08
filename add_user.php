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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
  $username = $_POST['username'];
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];
  $image_path = null;

  // Check for duplicate username
  $check = $connection->prepare("SELECT id FROM users WHERE username = ?");
  $check->bind_param("s", $username);
  $check->execute();
  $result = $check->get_result();
  if ($result->num_rows > 0) {
    $error = "Tên người dùng đã tồn tại.";
  } else if ($password !== $confirm_password) {
    $error = "Mật khẩu không khớp.";
  } else {
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
  $check->close();
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
    <?php if ($error): ?>
      <div class="error-popup" id="serverError"><?=htmlspecialchars($error)?></div>
    <?php endif; ?>


    <form id="add-user-form" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="user_category" value="<?= htmlspecialchars($current_category_id) ?>">

      <h3>Thêm Người Dùng</h3>

      <label for="username">Tên Người Dùng</label>
      <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>

      <label for="password">Mật Khẩu</label>
      <input type="password" name="password" required autocomplete="new-password">

      <label for="confirm_password">Xác Nhận Mật Khẩu</label>
      <input type="password" name="confirm_password" required autocomplete="new-password">

      <label for="role">Vị Trí</label>
      <select name="role" required>
        <option value="admin">Admin</option>
        <option value="waiter">Waiter</option>
        <option value="kitchen">Kitchen</option>
      </select>

      <label for="user_image">Ảnh</label>
      <input type="file" name="user_image" accept="image/*">

      <button type="submit" name="add_user">Thêm Người Dùng</button>
      <a href="user_management.php?category=<?= $current_category_id ?>">
        <button type="button">Hủy</button>
      </a>
    </form>
  </div>

  <script src="script.js"></script>
  <script>
    window.addEventListener('DOMContentLoaded', () => {
      const err = document.getElementById('serverError');
      if (err) setTimeout(() => err.remove(), 4000);
    });
  </script>
</body>
</html>
