<?php
session_start();
require_once 'config.php';

$categoryEntities = [
  'products' => [
    'table'    => 'product_categories',
    'label'    => 'Product Categories',
    'redirect' => 'product_management.php?'
  ],
  'tables' => [
    'table'    => 'table_categories',
    'label'    => 'Table Categories',
    'redirect' => 'table_management_admin.php?'
  ],
  'options' => [
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

$entity = $_GET['entity'] ?? '';
if (!isset($categoryEntities[$entity])) {
    exit("Unknown category type.");
}

$config     = $categoryEntities[$entity];
$tableName  = $config['table'];
$pageLabel  = $config['label'];
$cancelHref = $config['redirect'];

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

if (isset($_GET['delete_id'])) {
    $did = intval($_GET['delete_id']);
    $stmt = $connection->prepare("DELETE FROM `$tableName` WHERE id = ?");
    $stmt->bind_param("i", $did);
    $stmt->execute();
    $stmt->close();
    header("Location: categories.php?entity=$entity");
    exit();
}

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
    .form-container {
      max-width: 700px;
      margin: 30px auto;
      padding: 20px;
      background: #f9f9f9;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .form-container h3 {
      margin-bottom: 10px;
      color: black;
    }
    .form-container form {
      display: flex;
      gap: 12px;
      flex-direction: column;
      margin-bottom: 20px;
    }
    .form-container input {
      flex: 1;
      padding: 8px;
      font-size: 1em;
    }
    .form-container input[type="text"] {
      width: 100%;
    }
    .form-container button {
      padding: 8px 16px;
      margin-bottom: 10px;
      font-size: 1em;
      cursor: pointer;
    }

    .category-list {
      border-top: 1px solid #ccc;
      padding-top: 20px;
    }
    .category-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      margin-top: 10px;
      border-bottom: 1px solid #eee;
    }
    .category-name, .rename-input {
      font-size: 1em;
      width: 200px;
    }
    .rename-input {
      padding: 4px;
    }
    .category-actions {
      display: flex;
      gap: 8px;
    }
    .rename-btn, .save-btn {
      background: #28a745;
      color: white;
      font-size: 1em;
      border: none;
      padding: 4px 8px;
      border-radius: 4px;
      margin-bottom: 1px;
      cursor: pointer;
    }
    .delete-btn {
      background: #dc3545;
      color: white;
      font-size: 1em;
      border: none;
      padding: 4px 8px;
      margin-bottom: 1px;
      border-radius: 4px;
      cursor: pointer;
    }
    .category-list button {
      width: 100px;
    }
  </style>
</head>
<body>

<div class="form-container">
  <h3>Thêm Danh Mục Mới</h3>
  <form method="POST">
    <input type="text" name="category_name" placeholder="Danh Mục Mới" required>
    <button type="submit">Thêm Danh Mục</button>
    <a href="<?= htmlspecialchars($cancelHref) ?>"><button type="button">Hủy</button></a>
  </form>

  <div class="category-list">
    <h4>Danh Mục Hiện Tại</h4>
    <?php if (empty($cats)): ?>
      <p><em>Chưa có nhóm nào.</em></p>
    <?php else: ?>
      <?php foreach ($cats as $c): ?>
        <div class="category-item" data-id="<?= $c['id'] ?>">
          <span class="category-name"><?= htmlspecialchars($c['name']) ?></span>
          <div class="category-actions">
            <button class="rename-btn">Đổi Tên</button>
            <button class="delete-btn" onclick="confirmDelete(<?= $c['id'] ?>)">Xóa</button>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<script>
function confirmDelete(id) {
  if (confirm("Are you sure you want to delete this category?")) {
    window.location = 'categories.php?entity=<?= $entity ?>&delete_id=' + id;
  }
}

document.querySelectorAll('.rename-btn').forEach(button => {
  button.addEventListener('click', () => {
    const row = button.closest('.category-item');
    const id = row.dataset.id;
    const nameSpan = row.querySelector('.category-name');
    const oldName = nameSpan.textContent.trim();

    const input = document.createElement('input');
    input.type = 'text';
    input.value = oldName;
    input.className = 'rename-input';

    const saveBtn = document.createElement('button');
    saveBtn.textContent = '💾';
    saveBtn.className = 'save-btn';

    nameSpan.replaceWith(input);
    button.replaceWith(saveBtn);

    saveBtn.addEventListener('click', () => {
      const newName = input.value.trim();
      if (!newName) return alert('Name cannot be empty.');

      fetch('rename_category.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ entity: '<?= $entity ?>', id, name: newName })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          location.reload();
        } else {
          alert('Rename failed: ' + data.error);
        }
      });
    });
  });
});
</script>
</body>
</html>
