<?php
session_start();
require_once 'config.php';

if (!isset($_GET['id'])) {
    echo "No option selected.";
    exit();
}

$option_id = intval($_GET['id']);

// Fetch option info
$stmt = $connection->prepare("SELECT * FROM options WHERE id = ?");
$stmt->bind_param("i", $option_id);
$stmt->execute();
$result = $stmt->get_result();
$option = $result->fetch_assoc();
$stmt->close();

if (!$option) {
    echo "Option not found.";
    exit();
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_option'])) {
    // check for product_options referencing this option
    $chk = $connection->prepare("
      SELECT COUNT(*) AS cnt
        FROM product_options
       WHERE option_id = ?
    ");
    $chk->bind_param("i", $option_id);
    $chk->execute();
    $cnt = $chk->get_result()->fetch_assoc()['cnt'];
    $chk->close();

    if ($cnt > 0) {
        $error = "Cannot delete \"{$option['label']}\" because it is assigned to products.";
    } else {
        // Soft delete - just mark as deleted
        $stmt = $connection->prepare("UPDATE options SET deleted = 1 WHERE id = ?");
        $stmt->bind_param("i", $option_id);
        $stmt->execute();
        $stmt->close();
        header("Location: product_options_management.php?category=" . $option['type_id']);
        exit();
    }
}

// Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['label'])) {
    $new_label = trim($_POST['label']);
    if ($new_label === '') {
        $error = "Label cannot be empty.";
    } else {
        $stmt = $connection->prepare("UPDATE options SET label = ? WHERE id = ?");
        $stmt->bind_param("si", $new_label, $option_id);
        $stmt->execute();
        $stmt->close();
        header("Location: product_options_management.php?category=" . $option['type_id']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Option</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container">
    <?php if ($error): ?>
        <div class="error-popup">
            <?= htmlspecialchars($error) ?>
            <button style="margin-top: 10px; margin-bottom: 5px; padding: 5px;" onclick="this.parentElement.style.display='none'">Close</button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['confirm_delete'])): ?>
        <div class="confirm-popup" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.3); z-index: 1000;">
            <h3>Confirm Delete Option</h3>
            <p>Are you sure you want to delete option "<?= htmlspecialchars($option['label']) ?>"?</p>
            <form method="POST">
                <button type="submit" name="delete_option" class="confirm-btn">Confirm</button>
                <button type="button" class="cancel-btn" onclick="window.location.href='option_info.php?id=<?= $option_id ?>'">Cancel</button>
            </form>
        </div>
    <?php endif; ?>

    <form method="POST">
        <h3 style="color: black;">Sửa Tùy Chọn</h3>
        <input type="text" name="label" value="<?= htmlspecialchars($option['label']) ?>" required>
        <button type="submit">Lưu</button>        
    </form>

    <button type="button" onclick="window.location.href='option_info.php?id=<?= $option_id ?>&confirm_delete=1'" style="background: #dc3545; color: white;">Xóa Tùy Chọn</button>

    <a href="product_options_management.php?category=<?= $option['type_id'] ?>"><button type="button">Hủy</button></a>
</div>
</body>
</html>
