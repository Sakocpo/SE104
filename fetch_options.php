<?php
require_once 'config.php';

$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
if (!$product_id) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$result = [];

$types_query = mysqli_query($connection, "SELECT * FROM option_categories");
while ($type = mysqli_fetch_assoc($types_query)) {
    $type_id = $type['id'];
    $type_name = $type['name'];
    
    $options = [];
    $opts_query = mysqli_query($connection, "
        SELECT o.id, o.label 
        FROM options o
        JOIN product_options po ON o.id = po.option_id
        WHERE o.type_id = $type_id 
        AND po.product_id = $product_id
        AND o.deleted = 0
        ORDER BY o.label
    ");
    while ($opt = mysqli_fetch_assoc($opts_query)) {
        $options[] = $opt;
    }

    if (!empty($options)) {
        $result[$type_name] = $options;
    }
}

header('Content-Type: application/json');
echo json_encode($result);
