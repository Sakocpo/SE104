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
      background-image: url("uploads/waiter-page.jpg");
      background-color: transparent;
      background-repeat: no-repeat;
      background-position: center;
      background-size: cover;
    }
  </style>
</head>

<body>
    <div id="sidebar" class="sidebar">
        <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
        <ul>
            <li><a href="waiter.php">Trang Phục Vụ</a></li>
            <li><a href="table_management_waiter.php">Order Tại Bàn</a></li>
        </ul>
    </div>

    <div class="content">
        <div class="box">
            <h1>Welcome to Waiter page</h1>
            <p>Waiter page test</p>
            <button onclick="window.location.href='logout.php'">Logout</button>
        </div>
    </div>

    <div id="notification" class="notification-popup"></div>
    <audio id="bell-sound" src="bell.mp3" preload="auto"></audio>

    <script src="script.js"></script>
</body>
</html>
