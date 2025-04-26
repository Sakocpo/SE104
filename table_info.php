<?php
session_start();

require_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$table_id = isset($_GET['id']) ? intval($_GET['id']) : null;
if (!$table_id) {
    echo "No table selected.";
    exit();
}

$table_query = $connection->prepare("SELECT * FROM tables WHERE id = ?");
$table_query->bind_param("i",$table_id);
$table_query->execute();
$table_result = $table_query->get_result();
$table = $table_result->fetch_assoc();

if (!$table){
    echo "Table not found";
    exit();
}

$current_category_id = $table['table_category'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_table'])) {
    $stmt = $connection->prepare("DELETE FROM tables WHERE id = ?");
    $stmt->bind_param("i", $table_id);
    $stmt->execute();
    $stmt->close();

    header("Location: product_management.php?category=$current_category_id&deleted=1");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_table'])) {
    $name = $_POST['table_name'];
    $description = $_POST['table_desc'] ?? '';
    $active = isset($_POST['active']) ? 1 : 0;

    $stmt = $connection->prepare("UPDATE tables SET table_name = ?, table_desc = ?, active = ? WHERE id = ?");
    $stmt->bind_param("ssii ", $name, $description, $active, $table_id);
    $stmt->execute();
    $stmt->close();

    header("Location: table_management_admin.php?category=$current_category_id&updated=1");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Info</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
<div class="forms-container">
    <form id="edit-table-form" method="POST">
        <input type="hidden" name="category_id" value="<?= htmlspecialchars($current_category_id) ?>">

        <h3>Edit Table</h3>

        <label for="table_name"><Table></Table> Name:</label>
        <input type="text" name="table_name" value="<?= htmlspecialchars($table['table_name']) ?>" required>

        <label for="table_desc">Table Description:</label>
        <textarea name="table_desc"><?= htmlspecialchars($table['table_desc']) ?></textarea>

        <label for="table_state">Table State</label>
        <input type="checkbox" id="table_state" name="active" <?= $table['active'] ? 'checked' : '' ?>>

        <button type="submit" name="update_table">Update </button>

        <button type="submit"
                name="delete_table"
                style="background-color: red; color: white; margin-top: 10px;"
                onclick="return confirm('Are you sure you want to delete this table?');">
            Delete Table
        </button>


        <a href="table_management_admin.php?category=<?= $current_category_id ?>">
            <button type="button">Cancel</button>
        </a>
    </form>
</div>
    <script src="script.js"></script>

</body>
</html>


