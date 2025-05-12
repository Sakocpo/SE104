<?php
session_start();
require 'config.php';
if (!$_SESSION['user']||$_SESSION['user']['role']!=='admin') {
  http_response_code(403); exit;
}
$data = json_decode(file_get_contents('php://input'),true);
$name = trim($data['name'] ?? '');
if (!$name) {
  echo json_encode(['error'=>'Name empty']);
  exit;
}
$stmt = $connection->prepare("INSERT INTO unit_options(name) VALUES(?)");
$stmt->bind_param("s",$name);
$stmt->execute();
$id = $stmt->insert_id;
$stmt->close();
echo json_encode(['id'=>$id,'name'=>$name]);
