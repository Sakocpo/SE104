<?php
session_start();
require_once 'config.php';


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
  $active = isset($_POST['active']) ? 1 : 0; // Handle checkbox value

  // Check for duplicate name only among non-deleted tables
  $stmt = $connection->prepare("SELECT COUNT(*) as cnt FROM tables WHERE table_name = ? AND deleted = 0");
  $stmt->bind_param("s", $name);
  $stmt->execute();
  $result = $stmt->get_result();
  $count = $result->fetch_assoc()['cnt'];
  $stmt->close();

  if ($count > 0) {
    $error = "Bàn \"{$name}\" đã tồn tại, vui lòng chọn tên khác";
  }
  else
  {
    $stmt = $connection->prepare("INSERT INTO tables (table_name, table_category, table_desc, active) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sisi", $name, $current_category_id, $description, $active);
    $stmt->execute();
    $stmt->close();

    header("Location: table_management_admin.php?category=$current_category_id&added=1");
    exit();
  }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Table</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="forms-container">
  <!-- Show the error, if any -->
  <?php if ($error): ?>
        <div class="error-popup">
        <?= htmlspecialchars($error) ?>
        <button style="margin-top: 10px; margin-bottom: 5px; padding: 5px; " onclick="this.parentElement.style.display='none'">Close</button>
        </div>
  <?php endif; ?>
  <form id="add-table-form" method="POST">
    <input type="hidden" name="table_category" value="<?= htmlspecialchars($current_category_id) ?>">

    <h3>Add Table</h3>

    <label for="table_name">Table Name</label>
    <input type="text" name="table_name" style="width: 300px" required>

    <label for="table_desc">Table Information</label>
    <textarea name="table_desc"></textarea>

    <label for="table_state">Table State</label>
    <input type="checkbox" id="table_state" name="active">

    <button type="submit" name="add_table">Add Table</button>
    <a href="table_management_admin.php?category=<?= $current_category_id ?>">
      <button type="button">Cancel</button>
    </a>
  </form>

  </div>


  <script src="script.js"></script>

</body>
</html>