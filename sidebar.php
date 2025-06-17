<?php
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$role = $_SESSION['user']['role'];
?>

<div id="sidebar" class="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
    <ul>
        <?php if ($role === 'admin'): ?>
            <li><a href="admin.php">Trang Admin</a></li>
            <li><a href="product_management.php">Quản Lý Thực Đơn</a></li>
            <li><a href="ingredients_management.php">Quản Lý Nguyên Liệu</a></li>
            <li><a href="user_management.php">Quản Lý Người Dùng</a></li>
            <li><a href="table_management_admin.php">Quản Lý Bàn</a></li>
            <li><a href="product_options_management.php">Quản Lý Tùy Chọn</a></li>
            <li><a href="report.php">Báo Cáo Cuối Ngày</a></li>
            <li><a href="order_logs.php">Danh Sách Đơn</a></li>
            <li><a href="qr_management.php">Quản Lý Mã QR</a></li>
        <?php elseif ($role === 'waiter'): ?>
            <li><a href="waiter.php">Trang Phục Vụ</a></li>
            <li><a href="table_management_waiter.php">Order Tại Bàn</a></li>
            <!-- <li><a href="take_away_order.php">Order Mang Đi</a></li> -->
        <?php elseif ($role === 'kitchen'): ?>
            <li><a href="kitchen.php">Trang Bếp</a></li>
            <li><a href="kitchen_orders.php">Nhận Đơn</a></li>
            <li><a href="kitchen_ingredients.php">Quản Lý Nguyên Liệu</a></li>
        <?php endif; ?>
    </ul>
</div> 