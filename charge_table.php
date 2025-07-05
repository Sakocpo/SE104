<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'waiter') {
    http_response_code(403);
    echo 'Error 403: Unauthorized access';
    exit();
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

$qr_type = intval($_GET['qr_type'] ?? 1);
if ($qr_type < 1 || $qr_type > 2) $qr_type = 1;

$qrRows = [];
$resQr  = $connection->query("SELECT type_id, image_path FROM qr_codes ORDER BY type_id");
while ($r = $resQr->fetch_assoc()) {
    $qrRows[intval($r['type_id'])] = $r['image_path'];
}
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
      background: rgba(255,255,255,0.8);
      box-shadow: 0 4px 16px rgba(0,0,0,0.1);
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
    }

    .payment-qr {
      flex-direction: column;          
      align-items: center;
      justify-content: center;
    }

    .payment-qr img {
      width: 250px;
      height: 250px;
      margin: 0 auto;
      align-items: center;
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
    .toggle-btns {
      display: flex;
      justify-content: center;
      gap: 12px;
      margin-bottom: 10px;
    }
    .toggle-btn {
      background: rgba(103, 106, 108, 0.8);
      flex: 1;
      opacity: 1;
      width: 200px;
      border: 1px solid #ccc;
      padding: 6px 12px;
      border-radius: 4px;
      cursor: pointer;
    }
    .toggle-btn.active {
      background: #28a745;
      color: #fff;
      border-color: #28a745;
    }

    .inner-qr {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100%;
      width: 100%;
    }
  </style>
</head>
<body>

  <audio id="complete-sound" src="cash-sound.mp3" preload="auto"></audio>

<div id="notification" class="notification-popup"></div>
<audio id="bell-sound" src="uploads/bell.mp3" preload="auto"></audio>

<div class="payment-wrapper">
  <div class="payment-header">
    <div id="cash-btn" class="payment-mode active">💵 Tiền Mặt</div>
    <div id="qr-btn" class="payment-mode">📱 Mã QR</div>
  </div>

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
      <div class="inner-qr">
        <div class="toggle-btns">
          <button class="toggle-btn <?= $qr_type===1?'active':'' ?>"
                  data-type="1"
                  onclick="switchQR(1)">QR Ngân Hàng</button>
          <button class="toggle-btn <?= $qr_type===2?'active':'' ?>"
                  data-type="2"
                  onclick="switchQR(2)">QR MoMo</button>
        </div>
      <img
        id="qrImage"
        src="<?= htmlspecialchars($qrRows[1] ?? 'placeholder.png') ?>"
        alt="Mã QR"
      >
    </div>
    </div>
  </div>

  <div class="payment-actions">
    <button id="cancel-btn">Quay Lại</button>
    <button id="complete-btn">Hoàn Tất</button>
  </div>
</div>

<div id="cash-sidebar" class="sidebar-calc">
  <div class="calc-display" id="calc-display"><?= number_format($total, 2) ?></div>
  <div class="calc-keys">
    <?php foreach ([7,8,9,4,5,6,1,2,3,'C',0,'.','OK'] as $k): ?>
      <button data-key="<?= $k ?>"><?= $k ?></button>
    <?php endforeach; ?>
  </div>
</div>
<script src="notif_script.js"></script>
<script>

  const qrRows = <?= json_encode($qrRows, JSON_HEX_TAG) ?>;

    function switchQR(type) {
    document.querySelectorAll('.toggle-btn').forEach(btn => {
      btn.classList.remove('active');
    });

    const activeBtn = document.querySelector(`.toggle-btn[data-type="${type}"]`);
    if (activeBtn) activeBtn.classList.add('active');

    const img = document.getElementById('qrImage');
    img.src = qrRows[type] || 'placeholder.png';
  }

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
      alert('Gặp Lỗi: ' + j.error);
    }
  })
  .catch(() => {
    alert('Gặp Lỗi');
  });
};

  updateDisplays();
  
    document.addEventListener('click', e => {
    const sidebar = document.getElementById('cash-sidebar');
    const received = document.getElementById('received-amount');
    if (
      sidebar.classList.contains('visible') &&
      !sidebar.contains(e.target) &&
      e.target !== received
    ) {
      sidebar.classList.remove('visible');
    }
  });
})();
</script>
</body>
</html>
