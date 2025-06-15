<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo 'Error 403: Unauthorized access';
    exit();
}

$error = '';
$success = '';
// Which type are we editing? 1 or 2
$type = intval($_GET['type'] ?? 1);
if ($type<1 || $type>2) $type = 1;

// Fetch both QR rows
$qrRows = [];
$res = $connection->query("SELECT type_id, image_path FROM qr_codes ORDER BY type_id");
while ($row = $res->fetch_assoc()) {
  $qrRows[intval($row['type_id'])] = $row['image_path'];
}

// Handle update or clear
if ($_SERVER['REQUEST_METHOD']==='POST') {
  // update?
  if (isset($_POST['update_qr'])) {
    if (!empty($_FILES['qr_image']['name'])) {
      $dst = 'uploads/'.basename($_FILES['qr_image']['name']);
      move_uploaded_file($_FILES['qr_image']['tmp_name'], $dst);
    } else {
      $error = 'Vui lòng chọn hình ảnh.';
    }
    if (!$error) {
      $stmt = $connection->prepare("UPDATE qr_codes SET image_path = ? WHERE type_id = ?");
      $stmt->bind_param('si', $dst, $type);
      $stmt->execute(); $stmt->close();
      $_SESSION['flash'] = 'Cập nhật QR thành công.';
      header("Location: qr_management.php?type={$type}");
      exit;
    }
  }
  // clear?
  if (isset($_POST['clear_qr'])) {
    $stmt = $connection->prepare("UPDATE qr_codes SET image_path = '' WHERE type_id = ?");
    $stmt->bind_param('i', $type);
    $stmt->execute(); $stmt->close();
    $_SESSION['flash'] = 'QR đã được xóa.';
    header("Location: qr_management.php?type={$type}");
    exit;
  }
}

// Pull flash
if (isset($_SESSION['flash'])) {
  $success = $_SESSION['flash'];
  unset($_SESSION['flash']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>QR Management</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .success-popup {
      position: fixed; top:20px; left:50%; transform:translateX(-50%);
      background:#d4edda; color:#155724;
      padding:12px 20px; border:1px solid #c3e6cb; border-radius:6px;
      z-index:3000;
    }
    .confirm-popup {
      position: fixed; top:50%; left:50%; transform:translate(-50%,-50%);
      background:#fff; padding:20px; border-radius:8px;
      box-shadow:0 0 10px rgba(0,0,0,0.3); z-index:4000;
    }
    .confirm-btn { background:#28a745;color:#fff;border:none;padding:6px 12px;border-radius:4px;cursor:pointer; }
    .cancel-btn  { background:#ccc;color:#333;border:none;padding:6px 12px;border-radius:4px;cursor:pointer; }
    .qr-container {
      max-width: 400px; margin: 20px auto; background: rgba(255,255,255, 0.7); padding:20px; border-radius:8px;
      box-shadow:0 2px 8px rgba(0,0,0,0.1); text-align:center;
    }
    .qr-container h2 { margin-top:0; }
    .qr-img { width:100%; max-height:300px; object-fit:contain; margin: 5px auto;}
    .toggle-btns { display:flex; justify-content:center; gap:12px;}
    .toggle-btn { padding:8px 16px; cursor:pointer; border:none; border-radius:4px; margin-bottom: 10px;}
    .toggle-btn.active { background:#28a745;color:#fff; }
    .actions {
    max-width: 400px;        /* match .qr-container’s width */
    margin: 20px auto 0;     /* center it and push it 20px below the card */
    display: flex;
    gap: 12px;               /* space between buttons */
    }
    .actions .update { background:#007bff;color:#fff; }
    .actions .delete { background:#dc3545;color:#fff; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>

  <?php if ($error): ?>
    <div class="error-popup" id="serverError"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

    <div id="confirmPopup" class="confirm-popup" style="display:none;">
    <h3>Xác Nhận Xóa QR</h3>
    <p>Bạn có chắc chắn muốn xóa mã QR này?</p>
    <button id="confirmBtn" class="confirm-btn">Xác Nhận</button>
    <button id="cancelBtn" class="cancel-btn">Hủy</button>
  </div>


  <div class="qr-container">
    <div class="toggle-btns">
      <button class="toggle-btn <?= $type===1?'active':'' ?>" onclick="switchType(1)">QR Ngân Hàng</button>
      <button class="toggle-btn <?= $type===2?'active':'' ?>" onclick="switchType(2)">QR MoMo</button>
    </div>
    <img
      src="<?= $qrRows[$type] ?: 'placeholder.png' ?>"
      alt="QR Code"
      class="qr-img"
      id="qrImage"
    >
    <form method="POST" enctype="multipart/form-data">
      <input type="file" name="qr_image" accept="image/*" required>
      <input type="hidden" name="update_qr" value="1">
      <button type="submit" class="update">Cập nhật QR</button>
    </form>
    <form id="clearForm" method="POST">
      <input type="hidden" name="clear_qr" value="1">
      <button type="submit" style="background: red;" class="delete">Xóa QR</button>
    </form>
</div>


  <script>
    document.addEventListener('DOMContentLoaded', () => {
    const e = document.getElementById('serverError'),
          s = document.getElementById('serverSuccess');
    if (e) setTimeout(()=> e.remove(), 4000);
    if (s) setTimeout(()=> s.remove(), 4000);

    // --- DELETE CONFIRMATION LOGIC ---
    const clearForm    = document.getElementById('clearForm'),
          confirmModal = document.getElementById('confirmPopup'),
          btnConfirm   = document.getElementById('confirmBtn'),
          btnCancel    = document.getElementById('cancelBtn');

    clearForm.addEventListener('submit', function(evt) {
      // prevent immediate POST
      evt.preventDefault();
      // show the modal
      confirmModal.style.display = 'block';
    });

    btnConfirm.addEventListener('click', function() {
      // actually submit the form
      confirmModal.style.display = 'none';
      clearForm.submit();
    });

    btnCancel.addEventListener('click', function() {
      // simply hide the modal
      confirmModal.style.display = 'none';
    });
  });

  function switchType(t){
    window.location.href = `qr_management.php?type=${t}`;
  }
    document.addEventListener('DOMContentLoaded', () => {
      const e = document.getElementById('serverError'),
            s = document.getElementById('serverSuccess');
      if (e) setTimeout(()=> e.remove(), 4000);
      if (s) setTimeout(()=> s.remove(), 4000);
    });
    function switchType(t){
      window.location.href = `qr_management.php?type=${t}`;
    }
  </script>
  <script src="script.js"></script>
</body>
</html>
