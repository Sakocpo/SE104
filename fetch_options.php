<?php
require_once 'config.php';

$result = [];

$types_query = mysqli_query($connection, "SELECT * FROM option_categories");
while ($type = mysqli_fetch_assoc($types_query)) {
    $type_id = $type['id'];
    $type_name = $type['name'];
    
    $options = [];
    $opts_query = mysqli_query($connection, "SELECT id, label FROM options WHERE type_id = $type_id");
    while ($opt = mysqli_fetch_assoc($opts_query)) {
        $options[] = $opt;
    }

    $result[$type_name] = $options;
}

header('Content-Type: application/json');
echo json_encode($result);
