<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : null;
if (!$user_id) {
    echo "No user selected. ID: " . htmlspecialchars($_GET['id'] ?? 'NULL');
    exit();
}

$user_query = $connection->prepare("SELECT * FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user = $user_result->fetch_assoc();
$user_query->close();

if (!$user) {
    echo "User not found.";
    exit();
}

$current_category_id = $user['user_category'];
$error = '';

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $stmt = $connection->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: user_management.php?category=$current_category_id&deleted=1");
    exit();
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($password)) {
        if ($password !== $confirm_password) {
            $error = "Mật Khẩu Không Trùng Khớp.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $connection->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssi", $username, $hashed_password, $user_id);
            $stmt->execute();
            $stmt->close();

            header("Location: user_management.php?category=$current_category_id&updated=1");
            exit();
        }
    } else {
        $stmt = $connection->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->bind_param("si", $username, $user_id);
        $stmt->execute();
        $stmt->close();

        header("Location: user_management.php?category=$current_category_id&updated=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link rel="stylesheet" href="style.css">
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
        <div class="confirm-popup" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.3); z-index: 1000;">
            <h3>Xác Nhận Xóa Người Dùng</h3>
            <p>Bạn Có Chắc Chắn Muốn Xóa Người Dùng "<?= htmlspecialchars($user['username']) ?>"?</p>
            <form method="POST">
                <button type="submit" name="delete_user" class="confirm-btn">Xác Nhận</button>
                <button type="button" class="cancel-btn" onclick="window.location.href='user_info.php?id=<?= $user_id ?>'">Hủy</button>
            </form>
        </div>
    <?php endif; ?>

    <form id="edit-user-form" method="POST">
        <input type="hidden" name="category_id" value="<?= htmlspecialchars($current_category_id) ?>">

        <h3>Sửa Người Dùng</h3>

        <label for="username">Tên Người Dùng:</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>

        <label for="password">Mật Khẩu Mới (Để Trống Nếu Không Đổi):</label>
        <input type="password" name="password" placeholder="Mật Khẩu Mới" autocomplete="new-password">

        <label for="confirm_password">Xác Nhận Mật Khẩu:</label>
        <input type="password" name="confirm_password" placeholder="Xác Nhận Mật Khẩu" autocomplete="new-password">

        <button type="submit" name="update_user">Cập Nhật</button>

        <button type="button"
                onclick="window.location.href='user_info.php?id=<?= $user_id ?>&confirm_delete=1'"
                style="background-color: red; color: white; margin-top: 10px;">
            Xóa Người Dùng
        </button>

        <a href="user_management.php?category=<?= $current_category_id ?>">
            <button type="button">Hủy</button>
        </a>
    </form>
</div>

<script src="script.js"></script>
</body>
</html>
