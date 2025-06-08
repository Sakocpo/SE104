<?php
session_start();
require 'config.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') {
  header('Location:index.php'); exit;
}

$current_cat = intval($_GET['category'] ?? 0);
if (!$current_cat) {
  echo "No category selected."; exit;
}

// fetch categories + units
$cats  = $connection->query("SELECT * FROM ingredient_categories")->fetch_all(MYSQLI_ASSOC);
$units = $connection->query("SELECT * FROM unit_options")->fetch_all(MYSQLI_ASSOC);

$error = '';
// handle submission
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $name     = trim($_POST['name'] ?? '');
  $category = intval($_POST['category_id'] ?? 0);
  $unit     = intval($_POST['unit_id'] ?? 0);
  $qty      = floatval($_POST['quantity'] ?? 0);
  $imgpath  = '';

  // duplicate check
  $stmt = $connection->prepare("SELECT COUNT(*) as cnt FROM ingredients WHERE name = ? AND deleted = 0");
  $stmt->bind_param("s", $name);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_assoc();
  $count = $res['cnt'];
  $stmt->close();

  // validations
  if (!$name || !$category || !$unit) {
    $error = 'Vui lòng điền đầy đủ thông tin.';
  } elseif ($qty < 0) {
    $error = 'Số lượng không được âm.';
  } elseif ($count > 0) {
    $error = "Nguyên liệu \"{$name}\" đã tồn tại, vui lòng chọn tên khác.";
  } else {
    // image upload
    if (!empty($_FILES['image']['name'])) {
      $imgpath = 'uploads/'.basename($_FILES['image']['name']);
      move_uploaded_file($_FILES['image']['tmp_name'],$imgpath);
    }
    $stmt = $connection->prepare("INSERT INTO ingredients(name,category,unit_id,quantity,image) VALUES(?,?,?,?,?)");
    $stmt->bind_param("siiis", $name, $category, $unit, $qty, $imgpath);
    $stmt->execute();
    $stmt->close();
    header("Location: ingredients_management.php?category={$category}");
    exit;
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Thêm Nguyên Liệu</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .error-popup button {
      margin-top: 10px;
      padding: 5px;
      background: #721c24;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>

  <?php if ($error): ?>
    <div class="error-popup" id="serverError">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <div class="forms-container">
    <h3>Thêm Nguyên Liệu</h3>
    <form method="POST" enctype="multipart/form-data">
      <label>Tên Nguyên Liệu:</label>
      <input name="name" required>

      <label>Danh Mục:</label>
      <select name="category_id">
        <?php foreach($cats as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $c['id']==$current_cat?'selected':''?>>
            <?= htmlspecialchars($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Đơn Vị:</label>
      <div style="display:flex; gap:8px; align-items:center;">
        <select id="unit-select" name="unit_id">
          <?php foreach($units as $u): ?>
            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" id="add-unit">+ Thêm</button>
      </div>

      <label>Số Lượng:</label>
      <div style="display:flex; align-items:center; gap:8px;">
        <button type="button" onclick="q(-1)">−</button>
        <input id="qty" name="quantity" type="number" min="0" step="0.01" value="0">
        <button type="button" onclick="q(+1)">+</button>
      </div>

      <label>Hình Ảnh:</label>
      <input type="file" name="image" accept="image/*">

      <button type="submit">Lưu</button>
      <a href="ingredients_management.php?category=<?= $current_cat ?>">
        <button type="button">Hủy</button>
      </a>
    </form>
  </div>

  <!-- UNIT ADD MODAL -->
  <div id="unit-modal">
    <div class="modal-box">
      <h4 style="text-align:center; padding-bottom:10px;">Thêm Đơn Vị Mới</h4>
      <input type="text" id="new-unit-name" placeholder="kg, cái,..." />
      <div style="text-align:right; margin-top:10px;">
        <button id="confirm-unit">Xác Nhận</button>
        <button id="cancel-unit">Quay Lại</button>
      </div>
    </div>
  </div>

  <script>
    // auto-hide server error
    window.addEventListener('DOMContentLoaded', () => {
      const srv = document.getElementById('serverError');
      if (srv) setTimeout(() => srv.remove(), 5000);
    });
    // quantity controls
    function q(d) {
      const i = document.getElementById('qty');
      let v = parseFloat(i.value) || 0;
      v = Math.max(0, v + d);
      i.value = v.toFixed(2);
    }
    // unit modal logic unchanged
    const addBtn = document.getElementById('add-unit'), modal = document.getElementById('unit-modal');
    const confirmEl = document.getElementById('confirm-unit'), cancelEl = document.getElementById('cancel-unit');
    const inputEl = document.getElementById('new-unit-name'), selectEl = document.getElementById('unit-select');
    addBtn.addEventListener('click', ()=>{inputEl.value=''; modal.style.display='flex'; inputEl.focus();});
    cancelEl.addEventListener('click', ()=>modal.style.display='none');
    confirmEl.addEventListener('click', ()=>{
      const name = inputEl.value.trim();
      if (!name) return alert('Vui lòng nhập tên đơn vị.');
      fetch('add_unit_option.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({name})})
      .then(r=>r.json()).then(j=>{
        if (j.id) {
          const opt=new Option(j.name,j.id); selectEl.add(opt); selectEl.value=j.id; modal.style.display='none';
        } else alert('Error:'+ (j.error||'unknown'));
      }).catch(e=>alert('Failed to add unit.'));
    });
    modal.addEventListener('click',e=>{if(e.target===modal)modal.style.display='none';});
  </script>
</body>
</html>
