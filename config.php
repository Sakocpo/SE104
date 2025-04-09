<?php

$host = 'localhost'; // Database host
$user = 'root'; // Database username

$password = ''; //due to local, no password

$database = "users_db";

$connection = new mysqli($host, $user, $password, $database);

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
} 



?>