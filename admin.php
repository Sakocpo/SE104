<?php
session_start();
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
            <li><a href="product_management.php">Product Management</a></li>
            <li><a href="#">Inventory Management</a></li>
            <li><a href="#">Users Management</a></li>
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
