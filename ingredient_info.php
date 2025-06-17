<?php
session_start();
require 'config.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location:index.php'); exit;
}

$id = intval($_GET['id'] ?? 0);
if (!$id) exit("No ingredient selected.");

// Fetch ingredient
$stmt = $connection->prepare("SELECT * FROM ingredients WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$ing = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$ing) exit("Ingredient not found.");

$current_category_id = $ing['category'];
$error = '';

// Handle clear image
if (isset($_POST['clear_image'])) {
    if (!empty($ing['image']) && file_exists($ing['image'])) unlink($ing['image']);
    $upd = $connection->prepare("UPDATE ingredients SET image='' WHERE id=?");
    $upd->bind_param("i", $id);
    $upd->execute(); $upd->close();
    header("Location: ingredient_info.php?id={$id}"); exit;
}

// Handle delete request (soft delete)
if (isset($_POST['delete_ingredient'])) {
    $del = $connection->prepare("UPDATE ingredients SET deleted = 1 WHERE id = ?");
    $del->bind_param("i", $id);
    $del->execute();
    $del->close();
    header("Location: ingredients_management.php?category={$current_category_id}&deleted=1"); exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ingredient'])) {
    $name     = trim($_POST['name'] ?? '');
    $category = intval($_POST['category']);
    $unit     = intval($_POST['unit_id']);
    $qty      = floatval($_POST['quantity'] ?? 0);
    $imgpath  = $ing['image'];

    // Server-side validation
    if ($qty < 0) {
        $error = 'Số lượng không được âm.';
    } else {
        // Duplicate name excluding current
        $stmt = $connection->prepare(
            "SELECT COUNT(*) as cnt FROM ingredients WHERE name = ? AND deleted = 0 AND id <> ?"
        );
        $stmt->bind_param("si", $name, $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $cnt = $res['cnt'];
        $stmt->close();
        if ($cnt > 0) {
            $error = "Nguyên liệu \"{$name}\" đã tồn tại.";
        }
    }

    if (empty($error)) {
        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $upload_dir = 'uploads/';
            $imgpath = $upload_dir . basename($_FILES['image']['tmp_name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $imgpath);
        }
        // Update record
        $upd = $connection->prepare(
            "UPDATE ingredients SET name=?, category=?, unit_id=?, quantity=?, image=? WHERE id=?"
        );
        $upd->bind_param("siiisi", $name, $category, $unit, $qty, $imgpath, $id);
        $upd->execute(); $upd->close();
        header("Location: ingredients_management.php?category={$category}&updated=1"); exit;
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh Sửa Nguyên Liệu</title>
    <link rel="stylesheet" href="style.css">
    <style>
      .confirm-popup {
    position: fixed; top:50%; left:50%; transform:translate(-50%,-50%);
    background:#fff; padding:20px; border-radius:8px;
    box-shadow:0 0 10px rgba(0,0,0,0.3); z-index:1000;
  }
  .confirm-popup h3 { margin-top:0; }
  .confirm-popup form {gap:12px; justify-content:space-between; }
  .confirm-btn { background:#28a745; color:#fff; border:none; padding:6px 12px; border-radius:4px; cursor:pointer; }
  .cancel-btn { background:#ccc; color:#333; border:none; padding:6px 12px; border-radius:4px; cursor:pointer; }
</style>
</head><body>
  <?php if ($error): ?>
    <div class="error-popup" id="serverError"><?=htmlspecialchars($error)?></div>
  <?php endif; ?>
  <?php if (isset($_GET['confirm_delete'])): ?>
    <div class="confirm-popup">
      <h3>Xác Nhận Xóa Nguyên Liệu</h3>
      <p>Bạn có chắc chắn muốn xóa "<?=htmlspecialchars($ing['name'])?>"?</p>
      <form method="POST">
        <button type="submit" name="delete_ingredient" class="confirm-btn">Xác Nhận</button>
        <button type="button" class="cancel-btn" onclick="window.location.href='ingredient_info.php?id=<?=$id?>'">Hủy</button>
      </form>
    </div>
  <?php endif; ?>
  <div class="forms-container" style="display:flex; gap:24px; align-items:flex-start; padding-top:20px; max-width:1200px; margin:0 auto;">
    <div style="width:250px;">
      <?php if ($ing['image']): ?>
        <img src="<?=htmlspecialchars($ing['image'])?>" style="width:100%;border:1px solid #ccc;padding:4px;max-height:300px;">
        <form method="POST" style="margin-top:8px;"><button type="submit" name="clear_image">Xóa Ảnh</button></form>
      <?php endif; ?>
    </div>
    <form method="POST" enctype="multipart/form-data" style="min-width:300px;">
      <h3>Sửa Nguyên Liệu</h3>
      <label>Tên:</label>
      <input name="name" value="<?=htmlspecialchars($ing['name'])?>" required>
      <label>Danh Mục:</label>
      <select name="category">
        <?php foreach($connection->query("SELECT * FROM ingredient_categories")->fetch_all(MYSQLI_ASSOC) as $c): ?>
          <option value="<?=$c['id']?>" <?=$c['id']==$ing['category']?'selected':''?>><?=htmlspecialchars($c['name'])?></option>
        <?php endforeach; ?>
      </select>
      <label>Đơn Vị:</label>
      <select name="unit_id">
        <?php foreach($connection->query("SELECT * FROM unit_options")->fetch_all(MYSQLI_ASSOC) as $u): ?>
          <option value="<?=$u['id']?>" <?=$u['id']==$ing['unit_id']?'selected':''?>><?=htmlspecialchars($u['name'])?></option>
        <?php endforeach; ?>
      </select>
      <label>Số Lượng:</label>
      <div style="display:flex;align-items:center;gap:8px;">
        <button type="button" onclick="adjustQty(-1)">−</button>
        <input id="qty" name="quantity" type="number" min="0" step="0.01" value="<?=htmlspecialchars($ing['quantity'])?>">
        <button type="button" onclick="adjustQty(1)">+</button>
      </div>
      <label>Hình Ảnh:</label>
      <input type="file" name="image" accept="image/*">
      <div style="margin-top:12px;">
        <button type="submit" name="update_ingredient">Cập Nhật</button>
        <button type="button" onclick="window.location.href='ingredient_info.php?id=<?=$id?>&confirm_delete=1'" style="background:red;color:white;">Xóa Nguyên Liệu</button>
        <a href="ingredients_management.php?category=<?=$current_category_id?>"><button type="button">Hủy</button></a>
      </div>
    </form>
  </div>
  <script>
    // Auto-hide error after 4s
    window.addEventListener('DOMContentLoaded', () => {
      const err = document.getElementById('serverError');
      if (err) setTimeout(() => err.remove(), 4000);
    });
    // Quantity adjust
    function adjustQty(delta) {
      const i = document.getElementById('qty');
      let v = parseFloat(i.value) || 0;
      v = Math.max(0, v + delta);
      i.value = v.toFixed(2);
    }
  </script>
  <script src="script.js"></script>
</body>
</html>
