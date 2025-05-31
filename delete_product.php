<?php
include 'db.php';

$product_id = $_POST['id'];

// Soft delete - just mark as deleted
$stmt = $conn->prepare("UPDATE products SET deleted = 1 WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$stmt->close();

echo "success";
?>
