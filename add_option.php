<?php
session_start();
require_once 'config.php';

// Ensure admin is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Get all option categories to populate the dropdown

$categories_result = mysqli_query($connection, "SELECT * FROM option_categories");
$categories = [];
while ($row = mysqli_fetch_assoc($categories_result)) {
    $categories[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['label'], $_POST['type_id'])) {
    $label = trim($_POST['label']);
    $type_id = intval($_POST['type_id']);

    if ($label !== '' && $type_id > 0) {
        $stmt = $connection->prepare("INSERT INTO options (label, type_id) VALUES (?, ?)");
        $stmt->bind_param("si", $label, $type_id);
        $stmt->execute();
        $stmt->close();
        header("Location: product_options_management.php?category=$type_id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add New Option</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container">
    <form method="POST">
        <h3>Add New Option</h3>
        <input type="text" name="label" placeholder="Option label" required>

        <select name="type_id" required>
            <option value="">-- Select Option Category --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Add Option</button>
        <a href="product_options_management.php<?= isset($type_id) ? '?category=' . $type_id : '' ?>">
            <button type="button">Cancel</button>
        </a>

    </form>
</div>
</body>
</html>
