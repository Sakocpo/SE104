<?php

session_start();
require_once 'config.php';

if (isset($_POST['register'])) { // check if register button clicked
    $name = $_POST['name'];
    $email = $_POST['email'];
    $sdt = $_POST['sdt'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $_SESSION['register_error'] = "Passwords do not match";
        $_SESSION['active_form'] = 'register';
        header("Location: index.php");
        exit(); 
    }

    $password_hashed = password_hash($password, PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $checkEmail = $connection->query("SELECT email FROM users WHERE email = '$email'");

    if ($checkEmail->num_rows > 0) {
        $_SESSION['register_error'] = "Email already registered";
        $_SESSION['active_form'] = 'register';
        header("Location: index.php");
        exit(); 
    } else {
        $connection->query("INSERT INTO users (name, email, sdt, password, role) VALUES ('$name', '$email', '$sdt', '$password_hashed', '$role')");
    }

    header("Location: index.php"); // Redirect to index.php (main page)
    exit();
}

?>