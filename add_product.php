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

// Fetch options grouped by type
$options_query = "SELECT * FROM options";
$options_result = mysqli_query($connection, $options_query);
$options_by_type = [];
while ($row = mysqli_fetch_assoc($options_result)) {
    $options_by_type[$row['type']][] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['product_name'];
    $price = $_POST['product_price'];
    $description = $_POST['product_desc'] ?? '';

    $selected_options = [];
    foreach ($options_by_type as $type => $_) {
        if (isset($_POST[$type . '_options'])) {
            $selected_options = array_merge($selected_options, $_POST[$type . '_options']);
        }
    }

    $options_str = implode(',', $selected_options);

    $stmt = $connection->prepare("INSERT INTO products (name, category, price, options, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiss", $name, $current_category_id, $price, $options_str, $description);
    $stmt->execute();
    $stmt->close();

    header("Location: product_management.php?category=$current_category_id&added=1");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .option-block {
            background-color: #f0f0f0;
            padding: 5px;
            margin: 2px;
            display: inline-block;
            border-radius: 5px;
            cursor: pointer;
        }
        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 5px;
        }
    </style>
    <script>
        function toggleCheckboxes(type) {
            var box = document.getElementById('checkboxes_' + type);
            if (box.style.display === 'none') {
                box.style.display = 'block';
            } else {
                box.style.display = 'none';
            }
        }
    </script>
</head>
<body>
<div class="forms-container">
    <form id="add-product-form" method="POST">
        <input type="hidden" name="category_id" value="<?= htmlspecialchars($current_category_id) ?>">

        <h3>Add Product</h3>

        <label for="product_name">Product Name:</label>
        <input type="text" name="product_name" required>

        <label for="product_price">Price:</label>
        <input type="number" name="product_price" required>

        <!-- Option Blocks -->
        <div id="option-blocks" style="margin: 5px 0;">
            <?php foreach ($options_by_type as $type => $optionList): ?>
                <div class="option-block" onclick="toggleCheckboxes('<?= $type ?>')">
                    <?= ucfirst($type) ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Checkboxes for Options -->
        <?php foreach ($options_by_type as $type => $optionList): ?>
            <div id="checkboxes_<?= $type ?>" class="checkbox-wrapper" style="display:none; margin-top:10px;">
                <label style="font-weight:bold;"><?= ucfirst($type) ?> Options:</label>
                <div class="checkbox-grid">
                    <?php foreach ($optionList as $opt): ?>
                        <label>
                            <input type="checkbox" name="<?= $type ?>_options[]" value="<?= $opt['id'] ?>">
                            <?= htmlspecialchars($opt['label']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <label for="product_desc">Product Description:</label>
        <textarea name="product_desc"></textarea>

        <button type="submit" name="add_product">Add Product</button>
        <a href="product_management.php?category=<?= $current_category_id ?>">
            <button type="button">Cancel</button>
        </a>
    </form>
</div>
</body>
</html>
