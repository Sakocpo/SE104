<?php
include 'db.php';

$product_id = $_POST['id'];

$stmt = $conn->prepare("UPDATE products SET deleted = 1 WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$stmt->close();

echo "success";
?>
