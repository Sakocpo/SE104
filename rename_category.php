<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$categoryEntities = [
  'products' => 'product_categories',
  'tables' => 'table_categories',
  'options' => 'option_categories',
  'ingredients' => 'ingredient_categories'
];

$data = json_decode(file_get_contents('php://input'), true);
$entity = $data['entity'] ?? '';
$id     = intval($data['id'] ?? 0);
$name   = trim($data['name'] ?? '');

if (!isset($categoryEntities[$entity])) {
    echo json_encode(['success' => false, 'error' => 'Invalid entity']);
    exit;
}

if (!$id || $name === '') {
    echo json_encode(['success' => false, 'error' => 'Missing ID or name']);
    exit;
}

$table = $categoryEntities[$entity];
$stmt = $connection->prepare("UPDATE `$table` SET name = ? WHERE id = ?");
$stmt->bind_param("si", $name, $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
$stmt->close();
