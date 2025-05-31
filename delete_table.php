<?php
session_start();
require_once 'config.php';
if(!isset($_SESSION['user'])||$_SESSION['user']['role']!=='admin'){
  header("Location:index.php"); exit();
}
if($_SERVER['REQUEST_METHOD']==='POST' && !empty($_POST['table_ids'])){
  $ids=$_POST['table_ids'];
  $cat_id=$_POST['category_id'];
  $placeholders=implode(',',array_fill(0,count($ids),'?'));
  $types=str_repeat('i',count($ids));
  $stmt=$connection->prepare("UPDATE `tables` SET deleted = 1 WHERE id IN($placeholders)");
  $stmt->bind_param($types,...$ids);
  $stmt->execute();
  $stmt->close();
}
header("Location:table_management.php?category=$cat_id");
exit();
