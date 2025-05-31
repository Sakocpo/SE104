<?php
session_start();
require 'config.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location:index.php');
    exit;
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

// Handle clear image request
if (isset($_POST['clear_image'])) {
    // Delete file if exists
    if (!empty($ing['image']) && file_exists($ing['image'])) {
        unlink($ing['image']);
    }
    // Update DB
    $upd = $connection->prepare("UPDATE ingredients SET image='' WHERE id=?");
    $upd->bind_param("i", $id);
    $upd->execute();
    $upd->close();
    // Refresh
    header("Location: ingredient_info.php?id={$id}");
    exit;
}

// Handle delete request
if (isset($_POST['delete_ingredient'])) {
    $del = $connection->prepare("DELETE FROM ingredients WHERE id = ?");
    $del->bind_param("i", $id);
    $del->execute();
    $del->close();
    header("Location: ingredients_management.php?category={$current_category_id}&deleted=1");
    exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ingredient'])) {
    $name = trim($_POST['name']);
    $category = intval($_POST['category']);
    $unit = intval($_POST['unit_id']);
    $qty = floatval($_POST['quantity'] ?? 0);
    $imgpath = $ing['image'];
    if (!empty($_FILES['image']['name'])) {
        $imgpath = 'uploads/' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $imgpath);
    }
    $upd = $connection->prepare("UPDATE ingredients SET name=?,category=?,unit_id=?,quantity=?,image=? WHERE id=?");
    $upd->bind_param("siiisi", $name, $category, $unit, $qty, $imgpath, $id);
    $upd->execute();
    $upd->close();
    header("Location: ingredients_management.php?category={$category}&updated=1");
    exit;
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Edit Ingredient</title>
<link rel="stylesheet" href="style.css">
<style>
  
  .error-message{
    background:#f8d7da;
    color:#721c24;
    padding:10px;
    border:1px solid #f5c6cb;
    border-radius:4px;
    margin-bottom:12px;
  }

</style>
</head>
<body>
<div class="forms-container">
    <div style="display:flex; gap:24px; align-items:flex-start; padding-top: 20px; max-width: 1200px; margin: 0 auto;">
        <!-- ◀ LEFT PANEL: image + clear ▶ -->
        <div style="width:250px;">
            <?php if ($ing['image']): ?>
                <img src="<?=htmlspecialchars($ing['image'])?>" style="max-width:100%;border:1px solid #ccc;padding:4px;">
                <button form="edit-ingredient-form" name="clear_image" style="margin-top:8px;">
                    Clear Image
                </button>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" style="min-width: 300px; margin: 0 auto;">
            <h3>Edit Ingredient</h3>
            <label>Name:</label>
            <input name="name" value="<?= htmlspecialchars($ing['name']) ?>" required>

            <label>Category:</label>
            <select name="category">
                <?php foreach($connection->query("SELECT * FROM ingredient_categories")->fetch_all(MYSQLI_ASSOC) as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id']==$ing['category']?'selected':'' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Unit:</label>
            <select name="unit_id">
                <?php foreach($connection->query("SELECT * FROM unit_options")->fetch_all(MYSQLI_ASSOC) as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $u['id']==$ing['unit_id']?'selected':'' ?>>
                        <?= htmlspecialchars($u['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Quantity:</label>
            <div style="display:flex;align-items:center;gap:8px">
                <button type="button" onclick="adjustQty(-1)">−</button>
                <input id="qty" name="quantity" type="number" step="0.01" value="<?=htmlspecialchars($ing['quantity'])?>">
                <button type="button" onclick="adjustQty(1)">+</button>
            </div>
            <div style="margin-top:12px;">
                <button type="submit" name="update_ingredient">Update</button>
                <button type="submit" name="delete_ingredient" style="background:red;color:white;" onclick="return confirm('Bạn có chắc muốn xóa nguyên liệu này?');">Delete Ingredient</button>
                <a href="ingredients_management.php?category=<?= $current_category_id ?>"><button type="button">Cancel</button></a>
            </div>
        </form>
    </div>
</div>
<script>
function adjustQty(delta){
  const i=document.getElementById('qty'); let v=parseFloat(i.value)||0;
  i.value = Math.max(0,(v+delta).toFixed(2));
}
</script>
</body></html>

