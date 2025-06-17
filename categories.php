<?php
session_start();
require 'config.php';


if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo 'Error 403: Unauthorized access';
    exit();
}



$categoryEntities = [
    'products'    => ['table' => 'product_categories',    'label' => 'Product Categories',    'redirect' => 'product_management.php?'],
    'tables'      => ['table' => 'table_categories',      'label' => 'Table Categories',      'redirect' => 'table_management_admin.php?'],
    'options'     => ['table' => 'option_categories',      'label' => 'Option Categories',      'redirect' => 'product_options_management.php?'],
    'ingredients' => ['table' => 'ingredient_categories', 'label' => 'Ingredient Categories', 'redirect' => 'ingredients_management.php?'],
];

$entity = $_GET['entity'] ?? '';
if (!isset($categoryEntities[$entity])) {
    exit("Unknown category type.");
}

$config      = $categoryEntities[$entity];
$tableName   = $config['table'];
$pageLabel   = $config['label'];
$cancelHref  = $config['redirect'];

$error = '';

// Handle new category creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_name'])) {
    $name = trim($_POST['category_name']);
    if ($name === '') {
        $error = 'Tên không được để trống.';
    } else {
        // Duplicate check
        $chk = $connection->prepare("SELECT COUNT(*) FROM `$tableName` WHERE name = ? AND deleted = 0");
        $chk->bind_param("s", $name);
        $chk->execute();
        $chk->bind_result($count);
        $chk->fetch();
        $chk->close();
        if ($count > 0) {
            $error = "Danh mục '$name' đã tồn tại.";
        } else {
            $stmt = $connection->prepare("INSERT INTO `$tableName` (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $stmt->close();
            header("Location: categories.php?entity=$entity");
            exit;
        }
    }
}

// Handle soft delete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category_id'])) {
    $did = intval($_POST['delete_category_id']);
    $stmt = $connection->prepare("UPDATE `$tableName` SET deleted = 1 WHERE id = ?");
    $stmt->bind_param("i", $did);
    $stmt->execute();
    $stmt->close();
    header("Location: categories.php?entity=$entity");
    exit;
}

// Fetch active categories
$cats = [];
$res = $connection->query("SELECT * FROM `$tableName` WHERE deleted = 0 ORDER BY id");
while ($row = $res->fetch_assoc()) {
    $cats[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?=htmlspecialchars($pageLabel)?></title>
  <link rel="stylesheet" href="style.css">
  <style>
    .error-popup {
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      background: #f8d7da;
      color: red;
      padding: 12px 20px;
      border: 1px solid #f5c6cb;
      border-radius: 6px;
      z-index: 3000;
    }
    .error-popup button {
      margin-top: 10px;
      padding: 5px;
      background: red;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    .confirm-overlay {
      display: none;
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.5);
      align-items: center;
      justify-content: center;
      z-index: 4000;
    }
    .confirm-popup {
      background: #fff;
      padding: 20px;
      border-radius: 6px;
      max-width: 320px;
      text-align: center;
    }
    .confirm-popup h3 {
      margin-top: 0;
      color: #333;
    }
    .confirm-popup form {
      margin-top: 12px;
      display: flex;
      justify-content: space-between;
      gap: 12px;
    }
    .confirm-btn {
      background: #28a745;
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: 4px;
      cursor: pointer;
    }
    .cancel-btn {
      background: #ccc;
      color: #333;
      border: none;
      padding: 6px 12px;
      border-radius: 4px;
      cursor: pointer;
    }
    .form-container {
      max-width: 700px;
      margin: 30px auto;
      padding: 20px;
      background: #f9f9f9;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .form-container h3 { margin-bottom: 10px; color: black; }
    .form-container form { display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px; }
    .form-container input { padding: 8px; font-size: 1em; width: 100%; }
    .form-container button { padding: 8px 16px; font-size: 1em; cursor: pointer; }
    .category-list { border-top: 1px solid #ccc; padding-top: 20px; }
    .category-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eee; margin-top: 10px;}
    .category-name, .rename-input { font-size: 1em; width: 200px; }
    .rename-input { padding: 4px; margin-bottom: 0px;}
    .category-actions { display: flex; gap: 8px; }
    button { margin-bottom: 0;}
    .rename-btn, .save-btn { background: #28a745; color: white; padding: 4px 8px; border: none; border-radius: 4px; cursor: pointer; }
    .delete-btn { background: #dc3545; color: white; padding: 4px 8px; border: none; border-radius: 4px; cursor: pointer; }
  </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<?php if ($error): ?>
  <div class="error-popup" id="serverError"><?=htmlspecialchars($error)?></div>
<?php endif; ?>

<div class="form-container">
  <h3>Thêm Danh Mục Mới</h3>
  <form method="POST">
    <input style="width: 100%;" type="text" name="category_name" placeholder="Tên danh mục" required>
    <button type="submit">Thêm</button>
    <button type="button" onclick="history.back()">Hủy</button>
  </form>
  <div class="category-list">
    <h4>Danh mục hiện tại</h4>
    <?php if (empty($cats)): ?>
      <p><em>Chưa có mục nào.</em></p>
    <?php else: foreach($cats as $c): ?>
      <div class="category-item" data-id="<?=$c['id']?>">
        <span class="category-name"><?=htmlspecialchars($c['name'])?></span>
        <div class="category-actions">
          <button class="rename-btn">Đổi Tên</button>
          <button class="delete-btn">Xóa</button>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<div class="confirm-overlay" id="confirmOverlay">
  <div class="confirm-popup">
    <h3>Xác Nhận Xóa</h3>
    <p id="confirmText"></p>
    <form method="POST">
      <input type="hidden" name="delete_category_id" id="confirmId">
      <button type="submit" class="confirm-btn">Xác Nhận</button>
      <button type="button" class="cancel-btn" onclick="hideConfirm()">Hủy</button>
    </form>
  </div>
</div>

<script>
function hideConfirm() {
  document.getElementById('confirmOverlay').style.display = 'none';
}

function showConfirm(id, name) {
  document.getElementById('confirmText').innerText = `Bạn có chắc muốn xóa '${name}'?`;
  document.getElementById('confirmId').value = id;
  document.getElementById('confirmOverlay').style.display = 'flex';
}

// Auto-hide server error
window.addEventListener('DOMContentLoaded', () => {
  const srv = document.getElementById('serverError');
  if (srv) setTimeout(() => srv.remove(), 5000);
});

// Attach delete handlers
document.querySelectorAll('.delete-btn').forEach(btn => {
  btn.onclick = () => {
    const row = btn.closest('.category-item');
    const id = row.dataset.id;
    const name = row.querySelector('.category-name').textContent;
    showConfirm(id, name);
  };
});

// Inline error handler for rename
function showError(msg) {
  let ex = document.querySelector('.error-popup');
  if (ex) ex.remove();
  const div = document.createElement('div');
  div.className = 'error-popup';
  div.textContent = msg;
  document.body.appendChild(div);
  setTimeout(() => div.remove(), 5000);
}

document.querySelectorAll('.rename-btn').forEach(btn => {
  btn.onclick = () => {
    const row = btn.closest('.category-item');
    const id = row.dataset.id;
    const span = row.querySelector('.category-name');
    const old = span.textContent.trim();
    const inp = document.createElement('input'); inp.value = old; inp.className = 'rename-input';
    const save = document.createElement('button'); save.textContent = '💾'; save.className = 'save-btn';
    span.replaceWith(inp); btn.replaceWith(save);
    save.onclick = () => {
      const nm = inp.value.trim();
      if (!nm) return showError('Tên không được để trống.');
      const dup = Array.from(document.querySelectorAll('.category-item')).some(it => {
        const el = it.querySelector('.category-name');
        return it.dataset.id !== id && el && el.textContent.trim() === nm;
      });
      if (dup) return showError(`Danh mục '${nm}' đã tồn tại.`);
      fetch('rename_category.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({entity:'<?=$entity?>', id, name: nm})
      }).then(r => r.json()).then(d => d.success ? location.reload() : showError(d.error));
    };
  };
});
</script>
<script src="script.js"></script>
</body>
</html>
