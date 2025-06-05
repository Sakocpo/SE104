<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user'], $_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'waiter') {
    header('Location: index.php');
    exit;
}

$t = intval($_GET['table_id'] ?? 0);
$stmt = $connection->prepare("SELECT * FROM tables WHERE id=?");
$stmt->bind_param("i", $t);
$stmt->execute();
$table = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$table || empty($table['current_order_id'])) {
    exit("No active order on this table.");
}

$oid = intval($table['current_order_id']);
$res = $connection->prepare("
  SELECT oi.quantity, p.price
  FROM order_items oi
  JOIN products p ON p.id=oi.product_id
  WHERE oi.order_id=?
");
$res->bind_param("i", $oid);
$res->execute();
$items = $res->get_result()->fetch_all(MYSQLI_ASSOC);
$res->close();
$total = 0;
foreach ($items as $it) {
    $total += $it['quantity'] * $it['price'];
}

$qr = $connection
    ->query("SELECT image_path FROM payment_settings LIMIT 1")
    ->fetch_assoc()['image_path']
    ?? '';

// Update order status to paid
$stmt = $connection->prepare("
    UPDATE orders 
    SET status = 'paid', 
        paid_amount = ?, 
        charged_at = NOW() 
    WHERE id = ?
");
$stmt->bind_param("di", $total, $oid);
$stmt->execute();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment - Table <?= htmlspecialchars($table['table_name']) ?></title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      background-image: url("uploads/waiter-page.jpg");
      background-color: transparent;
      background-repeat: no-repeat;
      background-position: center;
      background-size: cover;
      color: black;
    }
    .payment-wrapper {
      width: 100%;
      max-width: 500px;
      border-radius: 16px;
      overflow: hidden;
      opacity: 0.9;
      box-shadow: 0 4px 16px rgba(0,0,0,0.1);
      background: white;
    }
    .payment-header {
      display: flex;
      height: 25%;
    }
    .payment-mode {
      flex: 1;
      text-align: center;
      padding: 20px;
      cursor: pointer;
      font-weight: bold;
      font-size: 1.2em;
      transition: all 0.2s ease-in-out;
    }
    .payment-mode:hover {
      background: #d9edf7;
    }
    .payment-mode.active {
      background: #28a745;
      color: white;
    }

    .payment-body {
      height: 350px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      opacity: 0.9;
    }

    .payment-qr img {
      width: 100%;
      max-height: 100%;
      object-fit: contain;
    }

    .payment-cash {
      width: 100%;
    }

    .payment-cash div {
      margin-bottom: 20px;
      font-size: 1.2em;
    }

    .clickable-underline {
      border-bottom: 2px solid #007bff;
      text-align: center;
      display: inline-block;
      min-width: 80px;
      cursor: pointer;
    }

    .payment-actions {
      display: flex;
      justify-content: space-between;
      gap: 16px;
      padding: 20px;
    }

    .payment-actions button {
      flex: 1;
      padding: 14px;
      font-size: 1.1em;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
    }

    .payment-actions #cancel-btn {
      background: #6c757d;
      color: white;
    }

    .payment-actions #complete-btn {
      background: #28a745;
      color: white;
    }

    /* calculator sidebar */
    .sidebar-calc {
      position: fixed;
      top: 0;
      right: -320px;
      width: 300px;
      height: 100%;
      background: #E7F2E4;
      box-shadow: -2px 0 8px rgba(0,0,0,0.2);
      transition: right .3s;
      padding: 16px;
      z-index: 1000;
    }

    .sidebar-calc.visible {
      right: 0;
    }

    .calc-display {
      font-size: 1.5em;
      text-align: right;
      padding: 8px;
      border: 1px solid #ccc;
      margin-bottom: 12px;
    }

    .calc-keys {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 8px;
    }

    .calc-keys button {
      font-size: 1.2em;
      padding: 12px;
      background:rgb(253, 128, 128);
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }

    #qr-modal {
      display: none;
    }
  </style>
</head>
<body>

  <audio id="complete-sound" src="cash-sound.mp3" preload="auto"></audio>

<div class="payment-wrapper">
  <!-- Top Mode Selector -->
  <div class="payment-header">
    <div id="cash-btn" class="payment-mode active">💵 Tiền Mặt</div>
    <div id="qr-btn" class="payment-mode">📱 Mã QR</div>
  </div>

  <!-- Bottom 3/4 -->
  <div class="payment-body">
    <div id="cash-view" class="payment-cash">
      <div>Tổng: <strong><?= number_format($total) ?> đ</strong></div>
      <div>
        Khách Trả:
        <span id="received-amount" class="clickable-underline"> <?= number_format($total) ?> </span> đ
      </div>
      <div>
        Tiền Thừa:
        <span id="change-amount"><?= number_format(0) ?> đ</span>
      </div>
    </div>
    <div id="qr-view" class="payment-qr" style="display: none;">
      <img src="uploads/qr_2.png" alt="Mã QR" style="height: auto; display: block; margin: 0 auto;">
    </div>
  </div>

  <!-- Buttons -->
  <div class="payment-actions">
    <button id="cancel-btn">Quay Lại</button>
    <button id="complete-btn">Hoàn Tất</button>
  </div>
</div>

<!-- Sidebar Calculator -->
<div id="cash-sidebar" class="sidebar-calc">
  <div class="calc-display" id="calc-display"><?= number_format($total, 2) ?></div>
  <div class="calc-keys">
    <?php foreach ([7,8,9,4,5,6,1,2,3,'C',0,'.','OK'] as $k): ?>
      <button data-key="<?= $k ?>"><?= $k ?></button>
    <?php endforeach; ?>
  </div>
</div>

<script>
(() => {
  const total = <?= $total ?>;
  let paid = total;
  let firstKey = true;

  const cashBtn = document.getElementById('cash-btn');
  const qrBtn = document.getElementById('qr-btn');
  const cashView = document.getElementById('cash-view');
  const qrView = document.getElementById('qr-view');
  const received = document.getElementById('received-amount');
  const change = document.getElementById('change-amount');
  const sidebar = document.getElementById('cash-sidebar');
  const disp = document.getElementById('calc-display');
  const keys = sidebar.querySelectorAll('button');

  // Mode toggle
  cashBtn.onclick = () => {
    cashBtn.classList.add('active');
    qrBtn.classList.remove('active');
    cashView.style.display = 'block';
    qrView.style.display = 'none';
  };

  qrBtn.onclick = () => {
    qrBtn.classList.add('active');
    cashBtn.classList.remove('active');
    cashView.style.display = 'none';
    qrView.style.display = 'block';
    paid = total;
    updateDisplays();
  };

  // Show calculator when clicking received underline
  received.onclick = () => {
    sidebar.classList.add('visible');
    disp.innerText = paid.toFixed(0);
    firstKey = true;
  };

  // Calculator key logic
  keys.forEach(btn => {
    const k = btn.dataset.key;
    btn.onclick = () => {
      let cur = disp.innerText.replace(/\D/g,'');
      if (k === 'C') {
        cur = '0';
        firstKey = true;
      } else if (k === 'OK') {
        paid = parseInt(cur,10) || 0;
        updateDisplays();
        sidebar.classList.remove('visible');
        return;
      } else {
        cur = (firstKey || cur === '0') ? k.toString() : cur + k.toString();
        firstKey = false;
      }
      disp.innerText = cur;
    };
  });

  function updateDisplays() {
    received.innerText = paid.toFixed(0);
    let rem = paid - total;
    if (paid < total) {
      change.innerText = "Lỗi";
      document.getElementById('complete-btn').disabled = true;
      document.getElementById('complete-btn').style.opacity = '0.5';
    } else {
      change.innerText = rem.toFixed(0) + ' đ';
      document.getElementById('complete-btn').disabled = false;
      document.getElementById('complete-btn').style.opacity = '1';
    }
  }

  // Cancel and Complete actions
  document.getElementById('cancel-btn').onclick = () => {
    window.location = `table_info_waiter.php?table_id=<?= $t ?>`;
  };

  document.getElementById('complete-btn').onclick = () => {
  if (paid < total) {
    alert('Số tiền thanh toán không được nhỏ hơn tổng số tiền');
    return;
  }

  fetch('complete_payment.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ table_id: <?= $t ?>, paid })
  })
  .then(r => r.json())
  .then(j => {
    if (j.success) {
      const sound = document.getElementById('complete-sound');
      if (sound) {
        setTimeout(() => {
          sound.currentTime = 0;
          sound.play().catch(() => {});
        }, 100);
      }
      setTimeout(() => {
        window.location = "table_management_waiter.php?category=<?= $table['table_category'] ?>";
      }, 1000);
    } else {
      alert('Error: ' + j.error);
    }
  })
  .catch(() => {
    alert('Failed to complete payment');
  });
};


  updateDisplays();
})();
</script>
</body>
</html>
