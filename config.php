<?php

$host = 'localhost'; // Database host
$user = 'root'; // Database username

$password = ''; //due to local, no password

$database = "users_db";

$connection = new mysqli($host, $user, $password, $database);

$error = '';

date_default_timezone_set('Asia/Ho_Chi_Minh');

function alreadyExists(mysqli $conn, string $table, string $column, string $value): bool {
  $sql = "SELECT 1 FROM `{$table}` WHERE `{$column}` = ? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $value);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_row() !== null;
  $stmt->close();
  return $res;
}



if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
} 



?>