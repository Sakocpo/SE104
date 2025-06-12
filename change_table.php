<?php
session_start();
require 'config.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'waiter') {
    header('Location:index.php');
    exit;
}

// source table
$src = intval($_GET['src'] ?? $_POST['src'] ?? 0);
if (!$src) exit('No source table.');

// category filter
$catFilter = intval($_GET['category'] ?? 0);

// load categories for top bar
$categories = $connection
    ->query("SELECT * FROM table_categories")
    ->fetch_all(MYSQLI_ASSOC);

// fetch source’s order
$stmt = $connection->prepare("SELECT current_order_id FROM tables WHERE id = ?");
$stmt->bind_param("i", $src);
$stmt->execute();
$tbl = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

if (empty($tbl['current_order_id'])) {
    exit('Source table has no active order.');
}
$order_id = intval($tbl['current_order_id']);

// handle move
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dest = intval($_POST['dest'] ?? 0);
    if (!$dest) exit('No destination.');

    // ensure dest is empty
    $check = $connection->prepare("SELECT current_order_id FROM tables WHERE id = ?");
    $check->bind_param("i", $dest);
    $check->execute();
    $crow = $check->get_result()->fetch_assoc() ?: [];
    $check->close();
    if (!empty($crow['current_order_id'])) {
        exit('Destination not empty.');
    }

    $connection->begin_transaction();
    try {
        // A) reassign the order record
        $u = $connection->prepare("UPDATE orders SET table_id = ? WHERE id = ?");
        $u->bind_param("ii", $dest, $order_id);
        $u->execute(); $u->close();

        // B) clear src
        $u = $connection->prepare("UPDATE tables SET current_order_id = NULL WHERE id = ?");
        $u->bind_param("i", $src);
        $u->execute(); $u->close();
        
        // C) set dest
        $u = $connection->prepare("UPDATE tables SET current_order_id = ? WHERE id = ?");
        $u->bind_param("ii", $order_id, $dest);
        $u->execute(); $u->close();
        // commit
        $connection->commit();

        // notify via WebSocket
        $n = $connection->prepare("SELECT table_name FROM tables WHERE id = ?");
        $n->bind_param("i", $dest);
        $n->execute();
        $new = $n->get_result()->fetch_assoc();
        $n->close();
        $new_table_name = $new['table_name'] ?? '';

        $msg = json_encode([
          'type'         => 'change_table',
          'order_id'     => $order_id,
          'old_table_id' => $src,
          'new_table_id' => $dest,
          'new_table'    => $new_table_name
        ]);

        // send to local WebSocket server
        $arg = escapeshellarg($msg);
        shell_exec("echo $arg | nc localhost 8080");

        header("Location: table_management_waiter.php?category={$catFilter}");
        exit;
    } catch (Exception $e) {
        $connection->rollback();
        exit("Change failed: " . $e->getMessage());
    }
}

// show all tables in category
$sql = "SELECT * FROM tables WHERE table_category = ?";
$q = $connection->prepare($sql);
$q->bind_param("i", $catFilter);
$q->execute();
$tbls = $q->get_result()->fetch_all(MYSQLI_ASSOC);
$q->close();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Change Table</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      background-image: url("uploads/waiter-page.jpg");
      background-color: transparent;
      background-repeat: no-repeat;
      background-position: center;
      background-size: cover;
    }
    .table-grid { display:flex; flex-wrap:wrap; gap:16px; padding:20px; margin-top:80px; }
    .table-card {
      width:180px; height:180px;
      border:1px solid #ccc; border-radius:12px;
      display:flex; align-items:center; justify-content:center;
      background:#fff; cursor:pointer; font-size:1.2em; color: brown;
    }
    .cancel-btn {
      position:fixed; top:50%; right:20px;
      transform:translateY(-50%);
      background:#dc3545; color:#fff;
      padding:12px 16px; border:none; border-radius:6px;
      text-decoration:none; font-weight:bold; z-index:1001;
    }
    /* confirmation modal */
    .confirm-popup {
      position: fixed; top:50%; left:50%; transform:translate(-50%,-50%);
      background:#fff; padding:20px; border-radius:8px;
      box-shadow:0 0 10px rgba(0,0,0,0.3); z-index:4000;
      display:none;
    }
    .confirm-popup .confirm-btn { background:#28a745;color:#fff;border:none;padding:6px 12px;border-radius:4px;cursor:pointer; }
    .cancel-confirm-btn { background:#ccc;color:#333;border:none;padding:6px 12px;border-radius:4px;cursor:pointer; }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>

  <!-- confirmation modal -->
  <div id="confirmPopup" class="confirm-popup">
    <h3>Xác Nhận Chuyển Bàn</h3>
    <p id="confirmText"></p>
    <button id="confirmBtn" class="confirm-btn">Xác Nhận</button>
    <button id="cancelBtn" class="cancel-confirm-btn">Hủy</button>
  </div>

  <!-- Top bar -->
  <div class="top-category-bar"
       style="position:fixed; top:0; left:50px; right:0;
              background:#f0f0f0; padding:10px 20px;
              display:flex; align-items:center; justify-content:space-between;
              z-index:1000; border-bottom:1px solid #ccc;">
    <div style="display:flex; gap:12px; overflow-x:auto;">
      <?php foreach ($categories as $cat): ?>
        <a href="change_table.php?src=<?= $src ?>&category=<?= $cat['id'] ?>"
           style="
             padding:8px 14px;
             border-radius:18px;
             text-decoration:none;
             white-space:nowrap;
             background-color:<?= ($cat['id']==$catFilter) ? '#28a745' : '#e0e0e0' ?>;
             color:<?= ($cat['id']==$catFilter) ? 'white' : '#333' ?>;
             font-weight:<?= ($cat['id']==$catFilter) ? 'bold' : 'normal' ?>;
           ">
          <?= htmlspecialchars($cat['name']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="table-grid">
    <?php foreach ($tbls as $t):
      if (!empty($t['current_order_id'])) continue;
    ?>
      <form class="move-form" method="POST" style="display:inline;">
        <input type="hidden" name="src"      value="<?= $src ?>">
        <input type="hidden" name="dest"     value="<?= $t['id'] ?>">
        <input type="hidden" name="category" value="<?= $catFilter ?>">
        <button type="submit" class="table-card" data-name="<?= htmlspecialchars($t['table_name']) ?>">
          <?= htmlspecialchars($t['table_name']) ?>
        </button>
      </form>
    <?php endforeach; ?>
  </div>

  <a href="table_management_waiter.php?category=<?= $catFilter ?>"
     class="cancel-btn">Quay Lại</a>

  <script>
  // WebSocket for real-time notifications
  const socket = new WebSocket("ws://localhost:8080");
  socket.addEventListener('open', () => console.log('[WS] connected for change_table'));

  // confirmation modal logic
  const confirmPopup = document.getElementById('confirmPopup');
  const confirmText  = document.getElementById('confirmText');
  const confirmBtn   = document.getElementById('confirmBtn');
  const cancelBtn    = document.getElementById('cancelBtn');
  let currentForm;

  document.querySelectorAll('.move-form').forEach(form => {
    form.addEventListener('submit', e => {
      e.preventDefault();
      currentForm = form;
      const name = form.querySelector('button').dataset.name;
      confirmText.textContent = `Bạn có chắc chắn muốn chuyển đến ${name}?`;
      confirmPopup.style.display = 'block';
    });
  });

  confirmBtn.addEventListener('click', () => {
    confirmPopup.style.display = 'none';
    if (currentForm) {
      // send WS notification
      const orderId    = <?= json_encode($order_id) ?>;
      const oldTableId = <?= json_encode($src) ?>;
      const newTableId = currentForm.querySelector('input[name="dest"]').value;
      const newTable   = currentForm.querySelector('button').dataset.name;
      const msg = { type:'change_table', order_id:orderId, old_table_id:oldTableId, new_table_id:newTableId, new_table:newTable };
      socket.send(JSON.stringify(msg));
      // submit form
      currentForm.submit();
    }
  });

  cancelBtn.addEventListener('click', () => {
    confirmPopup.style.display = 'none';
    currentForm = null;
  });
  </script>
</body>
</html>
