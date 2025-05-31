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
    <title>Waiter page</title>
    <link rel="stylesheet" href="style.css">
    <style>
    body {
      background-image: url("uploads/kitchen-page.jpg");
      background-color: transparent;
      background-repeat: no-repeat;
      background-attachment: fixed;
      background-position: center;
      background-size: cover;
    }
    </style>
</head>

<body>
    <div id="sidebar" class="sidebar">
        <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
        <ul>
            <li><a href="kitchen.php">Trang Bếp</a></li>
            <li><a href="kitchen_orders.php">Nhận Đơn</a></li>
        </ul>
    </div>

    <div class="content">
        <div class="box">
            <h1>Welcome to Kitchen page</h1>
            <p>Kitchen page test</p>
            <button onclick="window.location.href='logout.php'">Logout</button>
        </div>
    </div>


    <script src="script.js"></script>
</body>
</html>
