<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

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
