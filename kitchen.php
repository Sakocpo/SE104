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
    <title>Kitchen page</title>
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
            <img src="uploads/coffee_logo.png" alt="Kitchen Page" class="admin-image">
            <h1>Welcome to Kitchen page</h1>
            <button onclick="window.location.href='logout.php'">Logout</button>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
