<?php
include 'db.php';

$name = $_POST['name'];
$price = $_POST['price'];
$category_id = $_POST['category_id'];
$options = $_POST['options'] ?? [];

// Insert product
$stmt = $conn->prepare("INSERT INTO products (name, price, category_id) VALUES (?, ?, ?)");
$stmt->bind_param("sdi", $name, $price, $category_id);
$stmt->execute();
$product_id = $stmt->insert_id;
$stmt->close();

// Insert options
$stmt_opt = $conn->prepare("INSERT INTO product_options (product_id, option_name) VALUES (?, ?)");
foreach ($options as $opt) {
    $stmt_opt->bind_param("is", $product_id, $opt);
    $stmt_opt->execute();
}
$stmt_opt->close();

echo "success";
?>
