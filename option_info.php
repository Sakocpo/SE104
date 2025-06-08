<?php
session_start();
require_once 'config.php';

if (!isset($_GET['id'])) {
    echo "Không Có Tùy Chọn Nào Được Chọn.";
    exit();
}

$option_id = intval($_GET['id']);

// Fetch option info (only non-deleted)
$stmt = $connection->prepare("SELECT * FROM options WHERE id = ? AND deleted = 0");
$stmt->bind_param("i", $option_id);
$stmt->execute();
$result = $stmt->get_result();
$option = $result->fetch_assoc();
$stmt->close();

if (!$option) {
    echo "Option not found.";
    exit();
}

$error = '';

// Handle delete (soft)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_option'])) {
    // check for references
    $chk = $connection->prepare(
      "SELECT COUNT(*) AS cnt FROM product_options WHERE option_id = ?"
    );
    $chk->bind_param("i", $option_id);
    $chk->execute();
    $cnt = $chk->get_result()->fetch_assoc()['cnt'];
    $chk->close();

    if ($cnt > 0) {
        $error = "Không thể xóa \"{$option['label']}\" đang được gán cho sản phẩm";
    } else {
        $upd = $connection->prepare("UPDATE options SET deleted = 1 WHERE id = ?");
        $upd->bind_param("i", $option_id);
        $upd->execute();
        $upd->close();
        header("Location: product_options_management.php?category=" . $option['type_id']);
        exit();
    }
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['label'])) {
    $new_label = trim($_POST['label']);
    if ($new_label === '') {
        $error = "Tên tùy chọn không được để trống.";
    } else {
        // duplicate check
        $chk = $connection->prepare(
            "SELECT COUNT(*) AS cnt FROM options WHERE label = ? AND type_id = ? AND deleted = 0 AND id <> ?"
        );
        $chk->bind_param("sii", $new_label, $option['type_id'], $option_id);
        $chk->execute();
        $count = $chk->get_result()->fetch_assoc()['cnt'];
        $chk->close();
        if ($count > 0) {
            $error = "Tùy Chọn '{$new_label}' đã tồn tại.";
        } else {
            $upd = $connection->prepare("UPDATE options SET label = ? WHERE id = ?");
            $upd->bind_param("si", $new_label, $option_id);
            $upd->execute();
            $upd->close();
            header("Location: product_options_management.php?category=" . $option['type_id']);
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Option</title>
    <link rel="stylesheet" href="style.css">
    <style>
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
    .error-popup button {
        margin-top: 10px;
        padding: 5px;
        background: #721c24;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    </style>
</head>
<body>
<div class="form-container">
    <?php if ($error): ?>
        <div class="error-popup" id="serverError">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['confirm_delete'])): ?>
        <div class="confirm-popup" style="position: fixed; top:50%; left:50%; transform:translate(-50%,-50%); background: white; padding:20px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.3); z-index:1000;">
            <h3>Xác Nhận Xóa</h3>
            <p>Bạn Có Chắc Chắn Là Muốn Xóa Tùy Chọn "<?= htmlspecialchars($option['label']) ?>"?</p>
            <form method="POST">
                <button type="submit" name="delete_option" class="confirm-btn">Xác Nhận</button>
                <button type="button" class="cancel-btn" onclick="window.location.href='option_info.php?id=<?= $option_id ?>'">Hủy</button>
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

<script>
window.addEventListener('DOMContentLoaded', () => {
    const err = document.getElementById('serverError');
    if (err) setTimeout(() => err.remove(), 4000);
});
</script>
</body>
</html>
