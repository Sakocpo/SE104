<?php
session_start();
require 'config.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='waiter') {
    header('Location:index.php');
    exit;
}

// 1️⃣ Source table
$src = intval($_GET['src'] ?? $_POST['src'] ?? 0);
if (!$src) exit('No source table.');

// 2️⃣ Category filter
$catFilter = intval($_GET['category'] ?? 0);

// 3️⃣ Load categories for the top bar
$categories = $connection
    ->query("SELECT * FROM table_categories")
    ->fetch_all(MYSQLI_ASSOC);

// 4️⃣ Ensure source actually has an order
$stmt = $connection->prepare("SELECT current_order_id FROM tables WHERE id = ?");
$stmt->bind_param("i",$src);
$stmt->execute();
$tbl = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();
if (empty($tbl['current_order_id'])) {
    exit('Source has no active order to merge.');
}
$srcOrder = intval($tbl['current_order_id']);

// 5️⃣ Handle the merge POST
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $dest = intval($_POST['dest'] ?? 0);
    if (!$dest) exit('No destination chosen.');

    // make sure dest is occupied
    $chk = $connection->prepare("SELECT current_order_id FROM tables WHERE id = ?");
    $chk->bind_param("i",$dest);
    $chk->execute();
    $drow = $chk->get_result()->fetch_assoc() ?: [];
    $chk->close();
    if (empty($drow['current_order_id'])) {
        exit('Destination must already have an order.');
    }
    $dstOrder = intval($drow['current_order_id']);

    $connection->begin_transaction();
    try {
        // A) reassign items
        $u = $connection->prepare(
            "UPDATE order_items SET order_id = ? WHERE order_id = ?"
        );
        $u->bind_param("ii", $dstOrder, $srcOrder);
        $u->execute(); $u->close();

        // B) clear source table
        $u = $connection->prepare(
            "UPDATE tables SET current_order_id = NULL WHERE id = ?"
        );
        $u->bind_param("i",$src);
        $u->execute(); $u->close();

        // C) remove the now‑empty order row
        $d = $connection->prepare("DELETE FROM orders WHERE id = ?");
        $d->bind_param("i",$srcOrder);
        $d->execute(); $d->close();

        $connection->commit();
        header("Location: table_management_waiter.php?category={$catFilter}");
        exit;
    } catch (Exception $e) {
        $connection->rollback();
        exit("Merge failed: ".$e->getMessage());
    }
}

// 6️⃣ Only fetch tables *if* a category is selected
if ($catFilter) {
    $q = $connection->prepare(
      "SELECT * FROM tables WHERE table_category = ?"
    );
    $q->bind_param("i",$catFilter);
    $q->execute();
    $tbls = $q->get_result()->fetch_all(MYSQLI_ASSOC);
    $q->close();
} else {
    $tbls = []; // no category chosen -> no tables shown
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Merge Table</title>
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
    .prompt {
      text-align:center;
      margin: 120px 0;
      font-style:italic;
      color:#666;
    }
  </style>
</head>
<body>
  <div id="sidebar" class="sidebar">…</div>

  <!-- Top horizontal category bar -->
  <div class="top-category-bar">
    <div class="category-scroll">
      <?php foreach ($categories as $cat): ?>
        <a href="merge_table.php?src=<?= $src ?>&category=<?= $cat['id'] ?>"
           class="category-btn <?= ($catFilter == $cat['id']) ? 'active' : '' ?>">
          <?= htmlspecialchars($cat['name']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

    <?php if ($catFilter): ?>
    <div class="table-grid">
      <?php foreach ($tbls as $t):
        // only occupied and not the source
        if (empty($t['current_order_id']) || $t['id']==$src) continue;
      ?>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="src"      value="<?= $src ?>">
          <input type="hidden" name="dest"     value="<?= $t['id'] ?>">
          <input type="hidden" name="category" value="<?= $catFilter ?>">
          <button class="table-card"
                  onclick="return confirm('Merge into <?=htmlspecialchars($t['table_name'])?>?')">
            <?= htmlspecialchars($t['table_name']) ?>
          </button>
        </form>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <a href="table_management_waiter.php?category=<?= $catFilter ?>"
     class="cancel-btn">Cancel</a>
  <script src="script.js"></script>
</body>
</html>
