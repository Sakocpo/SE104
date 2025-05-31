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
  <div id="sidebar" class="sidebar">
        <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
        <ul>
            <li><a href="kitchen.php">Trang Bếp</a></li>
            <li><a href="kitchen_orders.php">Nhận Đơn</a></li>
        </ul>
    </div>
<div class="main-kitchen-list"></div>
<script src="script.js"></script>
<script>
const socket = new WebSocket("ws://localhost:8080");

socket.onopen = () => {
  socket.send(JSON.stringify({ type: "debug", message: "Kitchen page loaded" }));
};

socket.onmessage = function(event) {
  const data = JSON.parse(event.data);
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
      <td><input type="checkbox" name="serve_items[]" data-product="${data.product}" data-table="${data.table}" data-order-id="${orderId}"></td>
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
  const currentTime = new Date().toLocaleString();

  const card = document.createElement("div");
  card.classList.add("order-card");
  card.id = "order-card-" + orderId;

  card.innerHTML = `
    <div class="order-header">
      <div>
        <strong>New Order</strong><br>
        Table: ${data.table}<br>
        Placed at: ${currentTime}
      </div>
    </div>
    <form type="button" class="serve-form" data-order-id="${orderId}">
      <input type="hidden" name="order_id" value="${orderId}">
      <table>
        <thead>
          <tr><th>Product</th><th>Qty</th><th>Options</th><th>Serve?</th></tr>
        </thead>
        <tbody id="order-items-${orderId}"></tbody>
      </table>
      <button type="submit" class="serve-btn">Mark Selected Served</button>
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
  // 1) Gather checkboxes
  const allBoxes = form.querySelectorAll('input[name="serve_items[]"]');
  const checked  = form.querySelectorAll('input[name="serve_items[]"]:checked');
  const selected = checked.length ? checked : allBoxes;
  if (!selected.length) return;

  const orderId = selected[0].dataset.orderId;

  // 2) WebSocket “serve” per item
  selected.forEach(cb => {
    socket.send(JSON.stringify({
      type: 'serve',
      table:   cb.dataset.table,
      product: cb.dataset.product,
      order_id: orderId
    }));
  });

  // 3) If you just served *all* items, send “order done”
  if (selected.length === allBoxes.length) {
    socket.send(JSON.stringify({
      type:     'order',
      table:    selected[0].dataset.table,
      order_id: orderId
    }));
  }

  // 4) Call API to mark in DB
  fetch('mark_served.php', {
    method:  'POST',
    headers: {'Content-Type':'application/json'},
    body:    JSON.stringify({ order_id: orderId })
  })
  .then(r => r.json())
  .then(json => {
    if (!json.success) {
      console.error('Serve failed:', json.error);
      return;
    }

    // 5) Remove only the served rows
    selected.forEach(cb => {
      const row = cb.closest('tr');
      if (row) row.remove();
    });

    // 6) If no more rows left, remove the card
    const tbody = form.querySelector('tbody');
    if (!tbody.querySelector('tr')) {
      form.closest('.order-card').remove();
    }
  })
  .catch(err => console.error('Fetch error:', err));
}

</script>
</body>
</html>
