<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user'],$_SESSION['user']['role']) || $_SESSION['user']['role']!=='waiter') {
  header('Location:index.php'); 
  exit;
}

// 1️⃣ Load table & ensure occupied
$t = intval($_GET['table_id'] ?? 0);
$stmt = $connection->prepare("SELECT * FROM tables WHERE id=?");
$stmt->bind_param("i",$t);
$stmt->execute();
$table = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$table || empty($table['current_order_id'])) {
  exit("No active order on this table.");
}

// 2️⃣ Fetch items & compute total
$oid = intval($table['current_order_id']);
$res = $connection->prepare("
  SELECT oi.quantity, p.price
  FROM order_items oi
  JOIN products p ON p.id=oi.product_id
  WHERE oi.order_id=?
");
$res->bind_param("i",$oid);
$res->execute();
$items = $res->get_result()->fetch_all(MYSQLI_ASSOC);
$res->close();
$total = 0;
foreach($items as $it) {
  $total += $it['quantity'] * $it['price'];
}

// 3️⃣ Fetch QR‐code image
$qr = $connection
    ->query("SELECT image_path FROM payment_settings LIMIT 1")
    ->fetch_assoc()['image_path']
  ?? '';
?>
<!DOCTYPE html>
<html><head>
  <meta charset="utf-8"><title>Checkout Table <?=$table['table_name']?></title>
  <link rel="stylesheet" href="style.css">
  <link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
  integrity="sha512-pQs4S2mM4FgYkWcPXz6dCv4tvHQqGY9u2tR6E6/jl2jvX0O4T0Y8qkGvulixFanKDF7olg/6t9Yp+UaVJrQT1w=="
  crossorigin="anonymous"
  referrerpolicy="no-referrer"
/>  
</head>
<body>
    <div class="main-payment-layout">
        <div class="top-bar">
            Total: <strong><?=number_format($total,2)?></strong>
        </div>

        <div class="payment-types-bar">
          <button id="cash-btn">
            <i class="fas fa-money-bill-wave"></i> Cash
          </button>
          <button id="qr-btn">
            <i class="fas fa-qrcode"></i> QR
          </button>
        </div>
        <div class="info-blocks">
            <div class="paid-container">
                <div style="border:none;"><strong>Paid</strong></div>
                <div id="paid-display" style="border:none;"><?=number_format($total)?>đ</div>
            </div>
            <div class="remaining-container">
                <div style="border:none;"><strong>Remaining</strong></div>
                <div id="rem-display" style="border:none;">0đ</div>
            </div>
        </div>

        <!-- Cash sidebar keyboard -->
        <div id="cash-sidebar" class="sidebar-calc">
            <div class="calc-display" id="calc-display"><?=number_format($total,2)?></div>
            <div class="calc-keys">
            <?php foreach([7,8,9,4,5,6,1,2,3,'C',0,'.','OK'] as $k): ?>
                <button data-key="<?=$k?>"><?=$k?></button>
            <?php endforeach;?>
            </div>
        </div>

        <!-- QR modal -->
        <div id="qr-modal">
            <img src="<?=htmlspecialchars($qr)?>" alt="Scan to Pay">
        </div>

        <div class="actions">
            <button id="cancel-btn">Cancel</button>
            <button id="complete-btn">Complete</button>
        </div>
    </div>
<script>
(() => {
  const total = <?=$total?>;
  let paid = total;
  let firstKey = true;   // flag to wipe on first digit

  // elements
  const paidEl = document.getElementById('paid-display');
  const remEl  = document.getElementById('rem-display');
  const cashBtn= document.getElementById('cash-btn');
  const qrBtn  = document.getElementById('qr-btn');
  const modal  = document.getElementById('qr-modal');
  const sidebar= document.getElementById('cash-sidebar');
  const disp   = document.getElementById('calc-display');
  const keys   = sidebar.querySelectorAll('button');
  const cancel = document.getElementById('cancel-btn');
  const complete = document.getElementById('complete-btn');

  function updateDisplays(){
    paidEl.innerText = paid.toFixed(0) + ' đ';   // no decimals
    let rem = paid - total;
    remEl.innerText = (rem > 0 ? rem.toFixed(0) : 0) + ' đ';
  }

  cashBtn.onclick = () => {
    modal.style.display = 'none';
    sidebar.classList.toggle('visible');
    // reset to current paid for editing
    disp.innerText = paid.toFixed(0);
    firstKey = true;
  };

  qrBtn.onclick = () => {
    sidebar.classList.remove('visible');
    paid = total;
    updateDisplays();
    modal.style.display = 'flex';
  };
  modal.onclick = () => modal.style.display = 'none';

  // keypad
  keys.forEach(btn => {
    const k = btn.dataset.key;
    btn.onclick = () => {
      let cur = disp.innerText.replace(/\D/g,''); // strip non‑digits
      if (k === 'C') {
        cur = '0';
        firstKey = true;
      } else if (k === 'OK') {
        paid = parseInt(cur,10) || 0;
        updateDisplays();
        sidebar.classList.remove('visible');
        return;
      } else {
        // digit pressed
        if (firstKey || cur === '0') {
          cur = k.toString();
        } else {
          cur += k.toString();
        }
        firstKey = false;
      }
      disp.innerText = cur;
    };
  });

  cancel.onclick = () => window.location = `table_info_waiter.php?table_id=<?=$t?>`;
  complete.onclick = () => {
    fetch('complete_payment.php',{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({table_id:<?=$t?>,paid})
    })
    .then(r=>r.json())
    .then(j=>{
      if(j.success) window.location = "table_management_waiter.php?category=<?=$table['table_category']?>";
      else alert('Error: '+j.error);
    });
  };

  // initialize displays
  updateDisplays();
})();

</script>

</body></html>
