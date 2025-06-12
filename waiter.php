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
    .admin-image {
        width: 300px;
        height: 300px;
        display: block;
        margin: 0 auto;
        margin-bottom: 20px;
    }
    .box {
        text-align: center;
        padding: 20px;
    }
  </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <div class="box">
            <img src="uploads/coffee_logo.png" alt="Admin Page" class="admin-image">
            <h1>Welcome to Waiter page</h1>
            <button onclick="window.location.href='logout.php'">Logout</button>
        </div>
    </div>

    <div id="notification" class="notification-popup"></div>
    <audio id="bell-sound" src="uploads/bell.mp3" preload="auto"></audio>

    <script src="script.js"></script>
</body>
</html>
