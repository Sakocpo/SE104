<?php
// categories.php
session_start();
require_once 'config.php';  // make sure this defines $connection

// 1️⃣ configure your three category‐types here:
$categoryEntities = [
  'products' => [
    'table'    => 'product_categories',
    'label'    => 'Product Categories',
    'redirect' => 'product_management.php?' #category=
  ],
  'tables'   => [
    'table'    => 'table_categories',
    'label'    => 'Table Categories',
    'redirect' => 'table_management_admin.php?'
  ],
  'options'  => [
    'table'    => 'option_categories',
    'label'    => 'Option Categories',
    'redirect' => 'product_options_management.php?'
  ],
  'ingredients' => [
    'table'    => 'ingredient_categories',
    'label'    => 'Ingredient Categories',
    'redirect' => 'ingredients_management.php?'
  ]
];

// 2️⃣ pick the entity
$entity = $_GET['entity'] ?? '';
if (!isset($categoryEntities[$entity])) {
    exit("Unknown category type.");
}
$config       = $categoryEntities[$entity];
$tableName    = $config['table'];
$pageLabel    = $config['label'];
$cancelHref   = $config['redirect'];

// 3️⃣ handle POST → insert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_name'])) {
    $name = trim($_POST['category_name']);
    if ($name !== '') {
        $stmt = $connection->prepare("INSERT INTO `$tableName` (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $stmt->close();
        header("Location: categories.php?entity=$entity");
        exit();
    }
}

// 4️⃣ handle delete via GET
if (isset($_GET['delete_id'])) {
    $did = intval($_GET['delete_id']);
    $stmt = $connection->prepare("DELETE FROM `$tableName` WHERE id = ?");
    $stmt->bind_param("i", $did);
    $stmt->execute();
    $stmt->close();
    header("Location: categories.php?entity=$entity");
    exit();
}

// 5️⃣ fetch all
$cats = [];
$res = $connection->query("SELECT * FROM `$tableName` ORDER BY id");
while ($row = $res->fetch_assoc()) {
    $cats[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($pageLabel) ?></title>
  <link rel="stylesheet" href="style.css">
  <style>
    .category-list {
      border-top: 1px solid #ccc;
      padding-top: 20px;
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
    }
    .category-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      margin-top: 10px;
      border-bottom: 1px solid #eee;
    }
    .category-item span {
      font-size: 1em;
      width: 200px;
    }
    .category-item button {
      color: white;
      background: red;
      width: 100px;
      font-size: 1.2em;
      cursor: pointer;
      margin-bottom: 0px;
    }
    .category-item button:hover {
      color: yellow;
    }
  </style>
  <script>
    function confirmDelete(id) {
      if (confirm("Are you sure you want to delete this category?")) {
        window.location = 'categories.php?entity=<?= $entity ?>&delete_id=' + id;
      }
    }
  </script>
</head>
<body>
  <div class="form-container">
    <h3><?= htmlspecialchars($pageLabel) ?></h3>
    <form method="POST">
      <input type="text" name="category_name" placeholder="New <?= htmlspecialchars($pageLabel) ?>" required>
      <button type="submit">Add Category</button>
      <a href="<?= htmlspecialchars($cancelHref) ?>"><button type="button">Cancel</button></a>
    </form>

    <div class="category-list">
      <h4>Existing <?= htmlspecialchars($pageLabel) ?></h4>
      <?php if (empty($cats)): ?>
        <p><em>No categories yet.</em></p>
      <?php else: ?>
        <?php foreach ($cats as $c): ?>
          <div class="category-item">
            <span><?= htmlspecialchars($c['name']) ?></span>
            <button onclick="confirmDelete(<?= $c['id'] ?>)">Delete</button>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
