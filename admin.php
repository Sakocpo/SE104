<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin page</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div id="sidebar" class="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
    <ul>
        <li><a href="admin.php">Trang Admin</a></li>
        <li><a href="product_management.php">Quản Lý Hàng</a></li>
        <li><a href="ingredients_management.php">Quản Lý Nguyên Liệu</a></li>
        <li><a href="user_management.php">Quản Lý Người Dùng</a></li>
        <li><a href="table_management_admin.php">Quản Lý Bàn</a></li>
        <li><a href="product_options_management.php">Quản Lý Options</a></li>
        <li><a href="report.php">Báo Cáo Cuối Ngày</a></li>
        <li><a href="order_logs.php">Danh Sách Đơn</a></li>
    </ul>
    </div>

    <div class="content">
        <div class="box">
            <h1>Welcome to Admin page</h1>
            <p>Admin page test</p>
            <button onclick="window.location.href='logout.php'">Logout</button>
        </div>
    </div>


    <script src="script.js"></script>
</body>
</html>
