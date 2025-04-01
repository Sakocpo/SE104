<?php

session_start();
require_once 'config.php';

if(isset($_POST['register'])) { // check if register button clicked
    $name = $_POST['name'];
    $email = $_POST['email'];
    $sdt = $_POST['sdt'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password){
        $_SESSION['register_error'] = "Passwords do not match";
        $_SESSION['active_form'] = 'register';
    }
    $role = $_POST['role'];
    $checkEmail = $connection->query(("SELECT email FROM users WHERE email = '$email'"));
    if ($checkEmail->num_rows > 0) {
        $_SESSION['register_error'] = "Email already exists";
        $_SESSION['active_form'] = 'register';
    } else {
        $connection->query("INSERT INTO users (name,email,sdt,password,role) VALUES ('$name','$email','$sdt', $password','$role')");
    }
    header(("Location: index.php")); //redirect to index.php(main page)
    exit();
}

?>