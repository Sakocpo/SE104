<?php
include 'db.php';

$product_id = $_POST['id'];

// Delete product (options will cascade)
$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$stmt->close();

echo "success";
?>
