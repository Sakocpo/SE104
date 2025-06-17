<?php
session_start();
require_once 'config.php';

// Ensure admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo 'Error 403: Unauthorized access';
    exit();
}

// Get user ID
$user_id = isset($_GET['id']) ? intval($_GET['id']) : null;
if (!$user_id) {
    echo "No user selected. ID: " . htmlspecialchars($_GET['id'] ?? 'NULL');
    exit();
}

// Fetch user (non-deleted)
$stmt = $connection->prepare("SELECT * FROM users WHERE id = ? AND deleted = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$user) {
    echo "Không Tìm Thấy Người Dùng";
    exit();
}

$current_category_id = $user['user_category'];
$error = '';

// Soft-delete on request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $stmt = $connection->prepare("UPDATE users SET deleted = 1 WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: user_management.php?category=$current_category_id&deleted=1");
    exit();
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $username         = trim($_POST['username'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate non-empty username
    if ($username === '') {
        $error = 'Tên người dùng không được để trống.';
    }
    // Duplicate check
    if (!$error) {
        $chk = $connection->prepare(
            "SELECT COUNT(*) FROM users WHERE username = ? AND deleted = 0 AND id <> ?"
        );
        $chk->bind_param("si", $username, $user_id);
        $chk->execute();
        $chk->bind_result($count);
        $chk->fetch();
        $chk->close();
        if ($count > 0) {
            $error = "Tên người dùng '{$username}' đã tồn tại.";
        }
    }
    // Password match
    if (!$error && $password !== '') {
        if ($password !== $confirm_password) {
            $error = 'Mật Khẩu Không Trùng Khớp.';
        }
    }

    if (!$error) {
        if ($password !== '') {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $upd = $connection->prepare(
                "UPDATE users SET username = ?, password = ? WHERE id = ?"
            );
            $upd->bind_param("ssi", $username, $hashed, $user_id);
        } else {
            $upd = $connection->prepare(
                "UPDATE users SET username = ? WHERE id = ?"
            );
            $upd->bind_param("si", $username, $user_id);
        }
        $upd->execute();
        $upd->close();
        header("Location: user_management.php?category=$current_category_id&updated=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="forms-container">
    <?php if ($error): ?>
        <div class="error-popup">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['confirm_delete'])): ?>
        <div class="confirm-popup" style="position: fixed; top:50%; left:50%; transform:translate(-50%,-50%); background: white; padding:20px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.3); z-index:1000;">
            <h3>Xác Nhận Xóa Người Dùng</h3>
            <p>Bạn có chắc chắn muốn xóa người dùng "<?= htmlspecialchars($user['username']) ?>"?</p>
            <form method="POST">
                <button type="submit" name="delete_user" class="confirm-btn">Xác Nhận</button>
                <button type="button" class="cancel-btn" onclick="window.location.href='user_info.php?id=<?= $user_id ?>'">Hủy</button>
            </form>
        </div>
    <?php endif; ?>

    <form id="edit-user-form" method="POST">
        <h3>Sửa Người Dùng</h3>

        <label for="username">Tên Người Dùng:</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>

        <label for="password">Mật Khẩu Mới (Để Trống Nếu Không Đổi):</label>
        <input type="password" name="password" placeholder="Mật Khẩu Mới" autocomplete="new-password">

        <label for="confirm_password">Xác Nhận Mật Khẩu:</label>
        <input type="password" name="confirm_password" placeholder="Xác Nhận Mật Khẩu" autocomplete="new-password">

        <button type="submit" name="update_user">Cập Nhật</button>
        <button type="button" onclick="window.location.href='user_info.php?id=<?= $user_id ?>&confirm_delete=1'" style="background:red;color:white; ">Xóa Người Dùng</button>
        <button type="button" onclick="history.back()">Hủy</button>

    </form>
</div>
<script src="script.js"></script>
</body>
</html>
