<?php

$host = 'localhost'; // Database host
$user = 'root'; // Database username

$password = ''; //due to local, no password

$database = "user_db";

$connection = new mysqli($host, $user, $password, $database);

if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
} else {
    echo "Connected successfully";
}



?>