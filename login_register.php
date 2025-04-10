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

if (isset($_POST['login'])) { // check if login button clicked
    $email = $_POST['email'];
    $password = $_POST['password'];

    $result = $connection->query("SELECT * FROM users WHERE email = '$email'");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];


            if ($user['role'] === 'admin') {
                header("Location: admin.php"); 
            } else {
                header("Location: waiter.php"); 
            }
            exit(); 
        } else {
            $_SESSION['login_error'] = "Invalid password";
            $_SESSION['active_form'] = 'login';
            header("Location: index.php");
            exit(); 
        }
    } else {
        $_SESSION['login_error'] = "Email not registered";
        $_SESSION['active_form'] = 'login';
        header("Location: index.php");
        exit(); 
    }
}

?>