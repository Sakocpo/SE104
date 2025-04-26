<?php
session_start();
require_once 'config.php';

if (!isset($_GET['id'])) {
    echo "No option selected.";
    exit();
}

$option_id = intval($_GET['id']);

// Fetch option info
$stmt = $connection->prepare("SELECT * FROM options WHERE id = ?");
$stmt->bind_param("i", $option_id);
$stmt->execute();
$result = $stmt->get_result();
$option = $result->fetch_assoc();
$stmt->close();

if (!$option) {
    echo "Option not found.";
    exit();
}

// Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['label'])) {
    $new_label = trim($_POST['label']);
    if ($new_label !== '') {
        $stmt = $connection->prepare("UPDATE options SET label = ? WHERE id = ?");
        $stmt->bind_param("si", $new_label, $option_id);
        $stmt->execute();
        $stmt->close();
        header("Location: product_options_management.php?category=" . $option['type_id']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Option</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container">
    <form method="POST">
        <h3>Edit Option</h3>
        <input type="text" name="label" value="<?= htmlspecialchars($option['label']) ?>" required>
        <button type="submit">Save</button>
        <a href="product_options_management.php?category=<?= $option['type_id'] ?>"><button type="button">Cancel</button></a>
    </form>
</div>
</body>
</html>
