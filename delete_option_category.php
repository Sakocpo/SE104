<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo 'Error 403: Unauthorized access';
    exit();
}

require_once 'config.php';

if (!isset($_GET['id'])) {
    header('Location: add_option_category.php');
    exit();
}

$type_id = intval($_GET['id']);

$stmt = $connection->prepare("DELETE FROM option_categories WHERE id = ?");
$stmt->bind_param("i", $type_id);
$stmt->execute();
$stmt->close();

header('Location: add_option_category.php');
exit();
