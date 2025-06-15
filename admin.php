<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo 'Error 403: Unauthorized access';
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
    <style>
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
            <h1 style="margin-bottom: 10px; color: white;">Welcome to Admin page</h1>
            <button onclick="window.location.href='logout.php'">Logout</button>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
