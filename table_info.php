<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$table_id = isset($_GET['id']) ? intval($_GET['id']) : null;
if (!$table_id) {
    echo "No table selected.";
    exit();
}

// Fetch the table row (including current_order_id)
$table_query = $connection->prepare("SELECT * FROM tables WHERE id = ?");
$table_query->bind_param("i", $table_id);
$table_query->execute();
$table = $table_query->get_result()->fetch_assoc();
$table_query->close();

if (!$table) {
    echo "Table not found";
    exit();
}

$current_category_id = $table['table_category'];
$error = '';

// ─── DELETE HANDLER WITH IN‐USE CHECK ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_table'])) {
    // If this table still has an order, block deletion
    if (!empty($table['current_order_id'])) {
        $error = 'Bàn "' . $table['table_name'] . '" hiện đang có đơn và không thể xoá.';
    } else {
        // Safe to delete
        $del = $connection->prepare("DELETE FROM tables WHERE id = ?");
        $del->bind_param("i", $table_id);
        $del->execute();
        $del->close();

        header("Location: table_management_admin.php?category={$current_category_id}&deleted=1");
        exit();
    }
}

// ─── UPDATE HANDLER ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_table'])) {
    $name = trim($_POST['table_name']);
    $description = $_POST['table_desc'] ?? '';
    $active = isset($_POST['active']) ? 1 : 0;

    // Check for duplicate table name (excluding current table)
    $check = $connection->prepare("SELECT id FROM tables WHERE table_name = ? AND id != ? AND deleted = 0");
    $check->bind_param("si", $name, $table_id);
    $check->execute();
    $result = $check->get_result();
    if ($result->num_rows > 0) {
        $error = "Table name already exists.";
    } else {
        $upd = $connection->prepare("
          UPDATE tables
             SET table_name = ?, table_desc = ?, active = ?
           WHERE id = ?
        ");
        $upd->bind_param("ssii", $name, $description, $active, $table_id);
        $upd->execute();
        $upd->close();

        header("Location: table_management_admin.php?category={$current_category_id}&updated=1");
        exit();
    }
    $check->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Table</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      background: url("uploads/admin-page.jpg") no-repeat center/cover;
    }
    .forms-container {
      max-width: 600px;
      margin: 40px auto;
      /* background: rgba(255,255,255,0.9); */
      padding: 20px;
      border-radius: 8px;
    }
    form {
      width: 400px;
    }
  </style>
</head>
<body>
  <div class="forms-container">

    <?php if ($error): ?>
        <div class="error-popup">
        <?= htmlspecialchars($error) ?>
        <button style="margin-top: 10px; margin-bottom: 5px; padding: 5px; " onclick="this.parentElement.style.display='none'">Close</button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['confirm_delete'])): ?>
        <div class="confirm-popup">
            <h3>Xác Nhận Xóa</h3>
            <p>Bạn có chắc chắn là muốn xóa bàn "<?= htmlspecialchars($table['table_name']) ?>"?</p>
            <form method="POST">
                <button type="submit" name="delete_table" class="confirm-btn">Xóa</button>
                <button type="button" class="cancel-btn" onclick="window.location.href='table_info.php?id=<?= $table_id ?>'">Hủy</button>
            </form>
        </div>
    <?php endif; ?>

    <form id="edit-table-form" method="POST">
      <h3 style="margin-bottom: 10px;">Sửa Thông Tin Bàn</h3>

      <label for="table_name">Tên Bàn:</label>
      <input type="text" name="table_name"
             value="<?= htmlspecialchars($table['table_name']) ?>"
             required>

      <label for="table_desc">Mô Tả Bàn:</label>
      <textarea name="table_desc"><?= htmlspecialchars($table['table_desc']) ?></textarea>

      <label for="active">Sử Dụng?</label>
      <input type="checkbox" name="active"
             <?= $table['active'] ? 'checked' : '' ?>>

      <button type="submit" name="update_table">Cập Nhật</button>

      <a href="table_info.php?id=<?= $table_id ?>&confirm_delete=1">
        <button type="button" style="background:red;color:white;margin-top:10px;">
          Xóa Bàn
        </button>
      </a>

      <a href="table_management_admin.php?category=<?= $current_category_id ?>">
        <button type="button">Hủy</button>
      </a>
    </form>
  </div>
  <script src="script.js"></script>
</body>
</html>
