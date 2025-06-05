<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user'], $_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'kitchen') {
    header("Location: index.php");
    exit();
}

// Option map
$optLabels = [];
$res = $connection->query("SELECT id,label FROM options");
while ($row = $res->fetch_assoc()) {
    $optLabels[intval($row['id'])] = $row['label'];
}

// Unserved items with order info
$sql = "
  SELECT
    o.id           AS order_id,
    t.table_name   AS table_name,
    o.created_at   AS created_at,
    oi.id          AS item_id,
    oi.product_id  AS product_id,
    p.name         AS product_name,
    oi.quantity    AS quantity,
    oi.options     AS options
  FROM order_items oi
  JOIN orders o    ON oi.order_id = o.id
  JOIN products p  ON oi.product_id = p.id
  JOIN tables t    ON o.table_id = t.id
  WHERE oi.served = 0
  ORDER BY o.created_at ASC, oi.id ASC
";
$result = $connection->query($sql);

$orders = [];
while ($row = $result->fetch_assoc()) {
    $oid = $row['order_id'];
    if (!isset($orders[$oid])) {
        $orders[$oid] = [
            'table_name' => $row['table_name'],
            'created_at' => $row['created_at'],
            'items'      => []
        ];
    }
    $orders[$oid]['items'][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Kitchen — New Items</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      background-image: url("uploads/kitchen-page.jpg");
      background-color: transparent;
      background-repeat: no-repeat;
      background-attachment: fixed;
      background-position: center;
      background-size: cover;
    }
    .order-card {
      border: 1px solid #ccc;
      border-radius: 8px;
      margin: 16px auto;
      padding: 16px;
      max-width: 600px;
      background: #FBDB93;
      color: #641B2E;
    }
    .order-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 12px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 12px;
    }
    th, td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: left;
    }
    tr { background: #F3C623; }
    th { background: #BE5B50; color: white; }
    .serve-btn {
      background: #28a745;
      color: #fff;
      border: none;
      padding: 8px 12px;
      border-radius: 4px;
      cursor: pointer;
    }
    .option-pill {
      display: inline-block;
      margin-right: 6px;
      margin-top: 4px;
      padding: 2px 6px;
      border-radius: 12px;
      background: #f5f5f5;
      border: 1px solid #ccc;
      font-size: 0.9em;
      color: #333;
    }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>
  
<div class="main-kitchen-list"></div>
<script src="script.js"></script>
<script>
const socket = new WebSocket("ws://localhost:8080");

socket.onopen = () => {
  socket.send(JSON.stringify({ type: "debug", message: "Kitchen page loaded" }));
};

socket.onmessage = function(event) {
  const data = JSON.parse(event.data);
  console.log('[KITCHEN] Received message:', data);
  if (data.type === 'cancel') {
    const card = document.getElementById('order-card-' + data.order_id);
    if (card) card.remove();
    return; 
  }

  // if (data.type === 'change_table' || data.type === 'merge_table') {
  //   console.log('[KITCHEN] table changed/merged, reloading…');
  //   window.location.reload();
  //   return;
  // }

  // change_table: just update the header's Table: ... line
  if (data.type === 'change_table') {
  const card = document.getElementById('order-card-' + data.order_id);
  if (card) {
    const hdrDiv = card.querySelector('.order-header > div');
    const placedSpan = hdrDiv.querySelector('span.placed-at');
    hdrDiv.innerHTML = `
      <strong>New Order</strong><br>
      Table: ${data.new_table}<br>
    `;
    // re-append the original timestamp span
    if (placedSpan) hdrDiv.appendChild(placedSpan);
  }
  return;
}


  // merge_table: move rows into the target card and remove the source
  if (data.type === 'merge_table') {
    const from = document.getElementById('order-card-' + data.from_order_id);
    const into = document.getElementById('order-card-' + data.into_order_id);
    if (from && into) {
      const rows = Array.from(from.querySelectorAll('tbody tr'));
      rows.forEach(r => into.querySelector('tbody').appendChild(r));
      from.remove();
    }
    return;
  }


  if (data.type === "serve") {
    if (data.quantity === undefined) return;
    const orderId = data.order_id;
    const cardId = "order-card-" + orderId;
    const tbodyId = "order-items-" + orderId;

    const card = document.getElementById(cardId);
    const tbody = document.getElementById(tbodyId);

    const optionHTML = (data.options || []).map(opt =>
      `<span class="option-pill">${opt}</span>`
    ).join('');

    const newRow = document.createElement("tr");
    newRow.innerHTML = `
      <td>${data.product}</td>
      <td>${data.quantity}</td>
      <td>${optionHTML}</td>
    `;

    if (tbody) {
      const alreadyExists = Array.from(tbody.querySelectorAll("tr")).some(row => {
        const cells = row.querySelectorAll("td");
        const productMatch = cells[0]?.textContent.trim() === data.product;
        const qtyMatch = cells[1]?.textContent.trim() === String(data.quantity);
        const optionsMatch = (data.options || []).every(opt => row.innerHTML.includes(opt));
        return productMatch && qtyMatch && optionsMatch;
      });

      if (!alreadyExists) tbody.appendChild(newRow);
    } else {
      createOrderCard(orderId, data, newRow);
    }
  }
};

function createOrderCard(orderId, data, newRow) {
  const container = document.querySelector(".main-kitchen-list");
  let placedAt;
  if (data.created_at) {
    // turn "YYYY-MM-DD hh:mm:ss" into ISO for the browser
    placedAt = new Date(data.created_at.replace(' ', 'T')).toLocaleString();
  } else {
    placedAt = new Date().toLocaleString();
  }

  const card = document.createElement("div");
  card.classList.add("order-card");
  card.id = "order-card-" + orderId;

  card.innerHTML = `
    <div class="order-header">
      <div>
        <strong>Đơn Mới</strong><br>
        Bàn: ${data.table}<br>
        <span class="placed-at">Đặt Lúc: ${placedAt}</span>
      </div>
    </div>
    <form type="button" class="serve-form" data-order-id="${orderId}">
      <input type="hidden" name="order_id" value="${orderId}">
      <table>
        <thead>
          <tr><th>Sản Phẩm</th><th>Số Lượng</th><th>Tùy Chọn</th></tr>
        </thead>
        <tbody id="order-items-${orderId}"></tbody>
      </table>
      <button type="submit" class="serve-btn">Hoàn Tất</button>
    </form>
  `;

  container.appendChild(card);
  document.getElementById("order-items-" + orderId).appendChild(newRow);
  bindFormHandlers();
}

function bindFormHandlers() {
  document.querySelectorAll('.serve-form').forEach(form => {
    form.onsubmit = function(event) {
      // event.preventDefault();
      handleServeSubmit(this);
    }
  });
}

function handleServeSubmit(form) {
  // 1) Gather ALL rows in the order
  const rows = form.querySelectorAll('tbody tr');
  if (!rows.length) return;

  // 2) Get orderId and table name
  const orderId = form.dataset.orderId;
  const table = form.closest('.order-card').querySelector('.order-header').innerText
    .split('\n').find(line => line.trim().startsWith('Bàn:'))
    ?.replace('Bàn:', '').trim() || '';

  // 3) For each row, send the serve message
  rows.forEach(row => {
    const product = row.cells[0]?.textContent.trim();
    const quantity = row.cells[1]?.textContent.trim();
    const options = Array.from(row.cells[2]?.querySelectorAll('.option-pill')).map(p => p.textContent.trim());

    socket.send(JSON.stringify({
      type: 'serve',
      table: table,
      product: product,
      quantity: quantity,
      order_id: orderId,
      options: options
    }));
  });

  // 4) After all, send the order done message
  socket.send(JSON.stringify({
    type: 'order',
    table: table,
    order_id: orderId
  }));

  // 5) Mark as served in DB
  fetch('mark_served.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ order_id: orderId })
  })
  .then(r => r.json())
  .then(json => {
    if (!json.success) {
      console.error('Serve failed:', json.error);
      return;
    }
    // Remove the card entirely
    form.closest('.order-card').remove();
  })
  .catch(err => console.error('Fetch error:', err));
}

    // ─── BOOTSTRAP EXISTING ORDERS ───
    <?php foreach($orders as $oid => $o): ?>
  (()=>{
    // 1) Build a JS-friendly order object, including all items
    const order = <?php echo json_encode([
      'order_id'   => $oid,
      'table'      => $o['table_name'],
      'created_at' => $o['created_at'],
      'items'      => array_map(fn($it) => [
        'product'  => $it['product_name'],
        'quantity' => intval($it['quantity']),
        'options'  => array_map(fn($optId) => $optLabels[intval($optId)] ?? '', explode(',', $it['options']))
      ], $o['items'])
    ]); ?>;

    // 2) For each item, build a <tr>
    order.items.forEach((it, idx) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${it.product}</td>
        <td>${it.quantity}</td>
        <td>${
          it.options.filter(Boolean)
            .map(o=>`<span class="option-pill">${o}</span>`)
            .join('')
        }</td>
      `;
      // 3) First item → create the card, subsequent → append into its tbody
      if (idx === 0) {
        createOrderCard(order.order_id, order, tr);
      } else {
        document
          .getElementById(`order-items-${order.order_id}`)
          .appendChild(tr);
      }
    });
  })();
  <?php endforeach; ?>

  // 4) Re-bind your serve buttons
  bindFormHandlers();

</script>
</body>
</html>
