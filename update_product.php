<?php
include 'db.php';

$product_id = $_POST['id'];
$name = $_POST['name'];
$price = $_POST['price'];
$options = $_POST['options'] ?? [];

// Update product
$stmt = $conn->prepare("UPDATE products SET name=?, price=? WHERE id=?");
$stmt->bind_param("sdi", $name, $price, $product_id);
$stmt->execute();
$stmt->close();

// Delete old options
$conn->query("DELETE FROM product_options WHERE product_id = $product_id");

// Insert new options
$stmt_opt = $conn->prepare("INSERT INTO product_options (product_id, option_name) VALUES (?, ?)");
foreach ($options as $opt) {
    $stmt_opt->bind_param("is", $product_id, $opt);
    $stmt_opt->execute();
}
$stmt_opt->close();

echo "success";
?>
