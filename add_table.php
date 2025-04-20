<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
  header("Location: index.php");
  exit();
}

$current_category_id = isset($_GET['category']) ? intval($_GET['category']) : null;
if (!$current_category_id) {
    echo "No category selected.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_table'])) {
  $name = $_POST['table_name'];
  $description = $_POST['table_desc'] ?? '';
  

  $stmt = $connection->prepare("INSERT INTO tables (table_name, table_category, table_desc, active) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("ssisss", $name, $current_category_id, $price, $description, $active);
  $stmt->execute();
  $stmt->close();

  header("Location: table_management_admin.php?category=$current_category_id&added=1");
  exit();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Table</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="forms-container">
    <form id="add-table-form" method="POST">
      <input type="hidden" name="table_category" value="<?= htmlspecialchars($current_category_id) ?>">

      <h3>Add Table</h3>

      <label for="table_name">Table Name</label>
      <input type="text" name="table_name" required>

      <label for="table_desc">Table Information</label>
      <textarea name="table_desc"></textarea>

      <label for="table_state">Table State</label>
      <input type="checkbox">

      <button type="submit" name="add_table">Add Table</button>
        <a href="table_management_admin.php?category=<?= $current_category_id ?>">
            <button type="button">Cancel</button>
        </a>
    </form>
  </div>


  <script src="script.js"></script>

</body>
</html>