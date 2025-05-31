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
            $error = "Passwords do not match.";
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

    <form id="edit-user-form" method="POST">
        <input type="hidden" name="category_id" value="<?= htmlspecialchars($current_category_id) ?>">

        <h3>Edit User</h3>

        <label for="username">Username:</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>

        <label for="password">New Password (leave blank to keep current):</label>
        <input type="password" name="password" placeholder="New Password" autocomplete="new-password">

        <label for="confirm_password">Confirm Password:</label>
        <input type="password" name="confirm_password" placeholder="Confirm Password" autocomplete="new-password">

        <button type="submit" name="update_user">Update</button>

        <button type="submit"
                name="delete_user"
                style="background-color: red; color: white; margin-top: 10px;"
                onclick="return confirm('Are you sure you want to delete this user?');">
            Delete User
        </button>

        <a href="user_management.php?category=<?= $current_category_id ?>">
            <button type="button">Cancel</button>
        </a>
    </form>
</div>

<script src="script.js"></script>
</body>
</html>
