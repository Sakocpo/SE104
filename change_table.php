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

        $connection->commit();
        header("Location: table_management_waiter.php?category={$catFilter}");
        exit;
    } catch (Exception $e) {
        $connection->rollback();
        exit("Change failed: " . $e->getMessage());
    }
}

// show only empty tables
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
    .table-grid { display:flex; flex-wrap:wrap; gap:16px; padding:20px; margin-top:80px; }
    .table-card {
      width:180px; height:180px;
      border:1px solid #ccc; border-radius:12px;
      display:flex; align-items:center; justify-content:center;
      background:#fff; cursor:pointer; font-size:1.2em;
    }
    .cancel-btn {
      position:fixed; top:50%; right:20px;
      transform:translateY(-50%);
      background:#dc3545; color:#fff;
      padding:12px 16px; border:none; border-radius:6px;
      text-decoration:none; font-weight:bold; z-index:1001;
    }
  </style>
</head>
<body>
  <div id="sidebar" class="sidebar">…</div>

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
      <form method="POST" style="display:inline;">
        <input type="hidden" name="src"      value="<?= $src ?>">
        <input type="hidden" name="dest"     value="<?= $t['id'] ?>">
        <input type="hidden" name="category" value="<?= $catFilter ?>">
        <button class="table-card"
                onclick="return confirm('Move to <?=htmlspecialchars($t['table_name'])?>?')">
          <?= htmlspecialchars($t['table_name']) ?>
        </button>
      </form>
    <?php endforeach; ?>
  </div>

  <a href="table_management_waiter.php?category=<?= $catFilter ?>"
     class="cancel-btn">Cancel</a>
  <script src="script.js"></script>
</body>
</html>
