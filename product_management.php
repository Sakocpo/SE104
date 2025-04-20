<?php
session_start();
require_once 'config.php';



if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$current_category_id = isset($_GET['category']) ? intval($_GET['category']) : null;
$categories_result = $connection->query("SELECT * FROM categories");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

$products = [];
if ($current_category_id !== null) {
    $stmt = $connection->prepare("SELECT * FROM products WHERE category = ?");
    $stmt->bind_param("s", $current_category_id);
    $stmt->execute();
    $succes = True;
    $products_result = $stmt->get_result();
    $products = $products_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}


// Product addition logic (same as before)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $product_name = $_POST['product_name'];
        $category_id = $_POST['category_id']; // still assumed to be coming from your form if needed
        $price = $_POST['product_price'];
        $description = isset($_POST['product_desc']) ? $_POST['product_desc'] : '';

        // Instead of using fixed value checks, we'll collect selected options from each group.
        $selected_options = array();
        if (isset($_POST['temp_options'])) {
            $selected_options = array_merge($selected_options, $_POST['temp_options']);
        }
        if (isset($_POST['sugar_options'])) {
            $selected_options = array_merge($selected_options, $_POST['sugar_options']);
        }
        // If you add more option groups in the future, process them similarly.

        $options = implode(',', $selected_options);

        $stmt = $connection->prepare("INSERT INTO products (name, category, price, options, description) VALUES (?, ?, ?, ?, ?)");
        // Adjusted bind_param:
        // "s" for product_name, "s" for category_id (if it's a string; change to "i" if it's int),
        // "i" for price, "s" for options, and "s" for description.
        $stmt->bind_param("ssiss", $product_name, $category_id, $price, $options, $description);
        $stmt->execute();
        $stmt->close();
    }
}


$options_query = "SELECT * FROM options";
$options_result = mysqli_query($connection, $options_query);
$options_by_type = [];
while ($row = mysqli_fetch_assoc($options_result)) {
    $options_by_type[$row['type']][] = $row;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <div id="sidebar" class="sidebar">
            <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
            <ul>
                <li><a href="admin.php">Admin Page</a></li>
                <li><a href="product_management.php">Product Management</a></li>
                <li><a href="inventory_management.php">Inventory Management</a></li>
                <li><a href="user_management.php">Users Management</a></li>
                <li><a href="table_management_admin.php">Tables Management</a></li>
            </ul>
    </div>


    <?php if (isset($success) && $success): ?>
    <script>
        alert("Product added successfully!");
        window.location.href = "product_management.php?category=<?= $current_category_id ?>";
    </script>
    <?php endif; ?>
        <!-- Horizontal category bar -->
        <div class="top-category-bar" style="position: fixed; top: 0; left: 50px; right: 0; background: #f0f0f0; padding: 10px 20px; display: flex; align-items: center; justify-content: space-between; z-index: 1000; border-bottom: 1px solid #ccc;">

        <!-- Category list container -->
        <div style="display: flex; gap: 12px; overflow-x: auto;">
            <?php foreach ($categories as $cat): ?>
                <a href="product_management.php?category=<?= $cat['id'] ?>"
                    style="
                        padding: 8px 14px;
                        border-radius: 18px;
                        text-decoration: none;
                        white-space: nowrap;
                        background-color: <?= ($current_category_id == $cat['id']) ? '#007bff' : '#e0e0e0' ?>;
                        color: <?= ($current_category_id == $cat['id']) ? 'white' : '#333' ?>;
                        font-weight: <?= ($current_category_id == $cat['id']) ? 'bold' : 'normal' ?>;
                    ">
                    <?= htmlspecialchars($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Add button container -->
        <div>
            <a href="add_product_category.php" title="Add new category" style="font-size: 24px; text-decoration: none; font-weight: bold; color: #333;">➕</a>
        </div>

</div>

        <div class="product-grid" style="display: flex; flex-wrap: wrap; margin-top: 80px; gap: 16px; padding: 20px;">
            <?php foreach ($products as $product): ?>
                <div class="product-card"
                    onclick="window.location.href='product_info.php?id=<?= $product['id'] ?>'">
                    <div class="product-block" >
                        <img src="<?= htmlspecialchars($product['image'] ?? 'placeholder.png') ?>" alt=""
                            style="max-height: 100%; max-width: 100%;">
                    </div>
                    <div style="padding: 10px;">
                        <h4 style="margin: 0 0 8px;"><?= htmlspecialchars($product['name']) ?></h4>
                        <p style="margin: 0; font-weight: bold; color: #007bff;">₫<?= number_format($product['price']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
            


    <div class="add-button">

        <!-- <button onclick="document.getElementById('add-product-form').style.display='block'">Add Product</button> -->
        <?php if($current_category_id): ?>
        <div style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
            <a href="add_product.php?category=<?= $current_category_id ?>">
            <button style="font-size: 28px; padding: 10px 18px; border-radius: 50%; background: #007bff; color: white; border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">+</button>
        </div>
    <?php endif; ?>


    </div>


    <script src="script.js"></script>



</body>
</html>