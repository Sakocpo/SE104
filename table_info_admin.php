<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo 'Error 403: Unauthorized access';
    exit();
}

$table_id = isset($_GET['id']) ? intval($_GET['id']) : null;
if (!$table_id) {
    echo "No table selected.";
    exit();
}


$categories = $connection
    ->query("SELECT id, name FROM table_categories WHERE deleted = 0 ORDER BY name")
    ->fetch_all(MYSQLI_ASSOC);



// Fetch table (non-deleted)
$stmt = $connection->prepare("SELECT * FROM tables WHERE id = ? AND deleted = 0");
$stmt->bind_param("i", $table_id);
$stmt->execute();
$table = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$table) {
    echo "Table not found";
    exit();
}

$current_category_id = $table['table_category'];
$error = '';

// Soft-delete handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_table'])) {
    if (!empty($table['current_order_id'])) {
        $error = 'Bàn "' . htmlspecialchars($table['table_name']) . '" hiện đang có đơn và không thể xóa.';
    } else {
        $del = $connection->prepare("UPDATE tables SET deleted = 1 WHERE id = ?");
        $del->bind_param("i", $table_id);
        $del->execute();
        $del->close();
        header("Location: table_management_admin.php?category={$current_category_id}&deleted=1");
        exit();
    }
}

// Update handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_table'])) {
    $name        = trim($_POST['table_name']);
    $description = $_POST['table_desc'] ?? '';
    $active      = isset($_POST['active']) ? 1 : 0;
    $new_category= intval($_POST['category_id']);

    // Duplicate name check
    $chk = $connection->prepare(
        "SELECT COUNT(*) FROM tables WHERE table_name = ? AND id <> ? AND deleted = 0"
    );
    $chk->bind_param("si", $name, $table_id);
    $chk->execute();
    $chk->bind_result($count);
    $chk->fetch();
    $chk->close();
    if ($count > 0) {
        $error = 'Tên bàn đã tồn tại.';
    }
    else if (!empty($table['current_order_id'])) {
        $error = 'Bàn "' . htmlspecialchars($table['table_name']) . '" hiện đang có đơn và không thể cập nhật.';
    }
    else {
        $upd = $connection->prepare(
            "UPDATE tables
               SET table_name = ?, table_category = ?, table_desc = ?, active = ?
             WHERE id = ?"
        );
        $upd->bind_param("sisii", $name, $new_category, $description, $active, $table_id);
        $upd->execute();
        $upd->close();
        header("Location: table_management_admin.php?category={$current_category_id}&updated=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Table</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      background: url("uploads/admin-page.jpg") no-repeat center/cover;
    }
    .forms-container {
      max-width: 600px;
      margin: 40px auto;
      padding: 20px;
      border-radius: 8px;
    }
    form {
      width: 400px;
    }
    .error-popup {
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      background: #f8d7da;
      color: #721c24;
      padding: 12px 20px;
      border: 1px solid #f5c6cb;
      border-radius: 6px;
      z-index: 3000;
    }
  </style>
</head>
<body>
  <div class="forms-container">

    <?php if ($error): ?>
        <div class="error-popup"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['confirm_delete'])): ?>
        <div class="confirm-popup">
            <h3>Xác Nhận Xóa</h3>
            <p>Bạn có chắc chắn muốn xóa bàn "<?= htmlspecialchars($table['table_name']) ?>"?</p>
            <form method="POST">
                <button type="submit" name="delete_table" class="confirm-btn">Xác Nhận</button>
                <button type="button" class="cancel-btn" onclick="window.location.href='table_info_admin.php?id=<?= $table_id ?>'">Hủy</button>
            </form>
        </div>
    <?php endif; ?>

    <form id="edit-table-form" method="POST">
      <h3>Sửa Thông Tin Bàn</h3>

      <label for="table_name">Tên Bàn:</label>
      <input type="text" name="table_name" value="<?= htmlspecialchars($table['table_name']) ?>" required>

      <label for="category_id">Danh Mục:</label>
      <select name="category_id">
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= $cat['id']==$current_category_id ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label for="table_desc">Mô Tả Bàn:</label>
      <textarea name="table_desc"><?= htmlspecialchars($table['table_desc']) ?></textarea>

      <label for="active">Sử Dụng?</label>
      <input type="checkbox" name="active" <?= $table['active'] ? 'checked' : '' ?>>

      <div style="margin-top:12px;">
        <button type="submit" name="update_table">Cập Nhật</button>
        <a href="table_info_admin.php?id=<?= $table_id ?>&confirm_delete=1"><button type="button" style="background:red;color:white;">Xóa Bàn</button></a>
        <a href="table_management_admin.php?category=<?= $current_category_id ?>"><button type="button">Hủy</button></a>
      </div>
    </form>
  </div>
  <script>
    // Auto-hide error after 4s
    window.addEventListener('DOMContentLoaded', () => {
      const err = document.querySelector('.error-popup');
      if (err) setTimeout(() => err.remove(), 4000);
    });
  </script>
</body>
</html>
