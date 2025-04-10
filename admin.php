<?php
session_start(); // Start the session
if(!isset($_SESSION['email'])){ // Check if the user is logged in
    header("Location: index.php"); // Redirect to login page if not logged in
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
    
    <div class="box">
        <h1>Admin Page, <span><?= $_SESSION['name'] ?></h1>
        <p>Welcome to the admin page!</p>
        <p>You can manage users and their roles here.</p>
        <p>Use the navigation menu to access different sections.</p>
        <button onclick="window.location.href='logout.php'">Logout</button>
    </div>
</body>


</html>