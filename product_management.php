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
        $category_id = $_POST['category_id'];
        $price = $_POST['product_price'];

        $options = '';
        if ($category_id === 'temp' && isset($_POST['temp_options'])) {
            $options = implode(',', $_POST['temp_options']);
        } elseif ($category_id === 'sugar' && isset($_POST['sugar_options'])) {
            $options = implode(',', $_POST['sugar_options']);
        }

        $stmt = $connection->prepare("INSERT INTO products (name, category, price, options) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $product_name, $category_id, $price, $options);
        $stmt->execute();
        $stmt->close();
    }
}

$type_query = "SELECT DISTINCT type FROM product_options";
$type_result = mysqli_query($connection, $type_query);

// Get all options grouped by type
$options_query = "SELECT * FROM product_options";
$options_result = mysqli_query($connection, $options_query);
$options_by_type = [];

while ($row = mysqli_fetch_assoc($options_result)) {
    $options_by_type[$row['type']][] = $row['label'];
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

    <?php if (isset($success) && $success): ?>
    <script>
        alert("Product added successfully!");
        window.location.href = "product_management.php?category=<?= $current_category_id ?>";
    </script>
    <?php endif; ?>

        <!-- Horizontal category bar -->
        <div class="top-category-bar" style="position: fixed; top: 0; left: 0; right: 0; background: #f0f0f0; padding: 10px 20px; display: flex; align-items: center; gap: 12px; overflow-x: auto; z-index: 1000; border-bottom: 1px solid #ccc;">
            <a href="add_category.php" title="Add new category" style="font-size: 24px; text-decoration: none; font-weight: bold; color: #333;">➕</a>
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
        
        <div class="product-grid" style="display: flex; flex-wrap: wrap; margin-top: 80px; gap: 16px; padding: 20px;">
            <?php foreach ($products as $product): ?>
                <div class="product-card"
                    onclick='loadProduct(<?= json_encode($product) ?>)'
                    style="width: 180px; border: 1px solid #ccc; border-radius: 12px; overflow: hidden; cursor: pointer; background: #fff;">
                    <div style="height: 120px; background: #eee; display: flex; align-items: center; justify-content: center;">
                        <img src="<?= htmlspecialchars($product['image_url'] ?? 'placeholder.png') ?>" alt=""
                            style="max-height: 100%; max-width: 100%;">
                    </div>
                    <div style="padding: 10px;">
                        <h4 style="margin: 0 0 8px;"><?= htmlspecialchars($product['name']) ?></h4>
                        <p style="margin: 0; font-weight: bold; color: #007bff;">₫<?= number_format($product['price']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>


    <div class="forms-container">


        <?php if($current_category_id): ?>
        <form id="add-product-form" method="POST" style="display: none;">
            <h3>Add Product</h3>
            <label for="product_name">Product Name:</label>
            <input type="text" name="product_name" required>

            <label for="product_price">Price:</label>
            <input type="number" name="product_price" required>

            <!-- Select option type -->
            <label for="edit_category_id">Chọn Loại Tùy Chọn:</label>
            <select id="edit_category_id" name="category_id" required onchange="handleEditCategoryChange()">
                <option value="">-- Chọn loại --</option>
                <?php while ($row = mysqli_fetch_assoc($type_result)): ?>
                    <option value="<?= htmlspecialchars($row['type']) ?>">
                        <?= ucfirst($row['type']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <!-- Options will be shown here -->
            <?php foreach ($options_by_type as $type => $labels): ?>
                <div class="checkbox-wrapper" id="checkbox_<?= $type ?>" style="display:none; margin-top:10px;">
                    <label><strong><?= ucfirst($type) ?> Options:</strong></label>
                    <div class="checkbox-grid">
                        <?php foreach ($labels as $label): ?>
                            <label><input type="checkbox" name="<?= $type ?>_options[]" value="<?= $label ?>"> <?= $label ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>


            <label for="product_desc">Product Description:</label>
            <textarea name="product_desc"></textarea>

            <button type="submit" name="add_product">Add Product</button>
            <button type="back" onclick="document.getElementById('add-product-form').style.display='none'">Cancel</button>

        </form>
        <?php endif; ?>




        <!-- Product Info Form (for editing/deleting a selected product) -->
        <form id="product-info-form" method="POST" style="display: none; margin-top: 20px;">
            <h3>Edit Product</h3>
            <input type="hidden" name="product_id" id="edit_product_id">

            <label for="edit_product_name">Product Name:</label>
            <input type="text" name="product_name" id="edit_product_name" required>

            <label for="edit_product_price">Price:</label>
            <input type="number" name="product_price" id="edit_product_price" required>

            <label for="edit_category_id">Category:</label>
            <select id="edit_category_id" name="category_id" required onchange="handleEditCategoryChange()">
                <option value="temp">Nhiệt Độ</option>
                <option value="sugar">Đường</option>
            </select>

            <label for="edit_product_desc">Description:</label>
            <textarea name="product_desc" id="edit_product_desc"></textarea>

            <button type="submit" name="update_product">Update</button>
            <button type="submit" name="delete_product" style="background-color: red; color: white;">Delete</button>
            <button type="button" onclick="document.getElementById('product-info-form').style.display='none'">Cancel</button>
        </form>



    </div>

    <div id="sidebar" class="sidebar">
        <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
        <ul>
            <li><a href="product_management.php">Product Management</a></li>
            <li><a href="inventory_management.php">Inventory Management</a></li>
            <li><a href="user_management.php">Users Management</a></li>
        </ul>
    </div>

    <div class="add-button">

        <!-- <button onclick="document.getElementById('add-product-form').style.display='block'">Add Product</button> -->
        <?php if($current_category_id): ?>
        <div style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
            <button onclick="confirmAndShowForm()" style="font-size: 28px; padding: 10px 18px; border-radius: 50%; background: #007bff; color: white; border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">+</button>
        </div>
    <?php endif; ?>

    </div>

    <script src="script.js"></script>



</body>
</html>