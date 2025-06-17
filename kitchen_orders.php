<?php
session_start();
require_once 'config.php';

// ── 1) Only kitchen role allowed ──
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'kitchen') {
    http_response_code(403);
    echo 'Error 403: Unauthorized access';
    exit();
}

// ── 2) Build option ID → label map ──
$optLabels = [];
$res = $connection->query("SELECT id,label FROM options");
while ($row = $res->fetch_assoc()) {
    $optLabels[intval($row['id'])] = $row['label'];
}

// ── 3) Fetch all unserved, non-deleted items ──
$sql = "
  SELECT
    o.id           AS order_id,
    t.table_name   AS table_name,
    o.created_at   AS created_at,
    oi.product_id,
    p.name         AS product_name,
    oi.quantity    AS quantity,
    oi.options     AS options
  FROM order_items oi
  JOIN orders o    ON oi.order_id = o.id
  JOIN products p  ON oi.product_id = p.id
  JOIN tables t    ON o.table_id = t.id
  WHERE oi.served = 0
    AND o.status  != 'deleted'
  ORDER BY o.created_at ASC, oi.id ASC
";
$result = $connection->query($sql);

// Organize into $orders[order_id] = [ table_name, created_at, items => [ … ] ]
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

// ── 4) Prepare a PHP array for JS bootstrapping ──
$initialOrders = [];
foreach ($orders as $oid => $o) {
    $entry = [
        'order_id'   => $oid,
        'table_name' => $o['table_name'],
        'created_at' => $o['created_at'],
        'items'      => []
    ];
    foreach ($o['items'] as $it) {
        $labels = [];
        foreach (explode(',', $it['options']) as $optId) {
            $i = intval($optId);
            if (isset($optLabels[$i])) {
                $labels[] = $optLabels[$i];
            }
        }
        $entry['items'][] = [
            'product_name' => $it['product_name'],
            'quantity'     => intval($it['quantity']),
            'options'      => $labels,
            'note'         => ''    // no note in this table
        ];
    }
    $initialOrders[] = $entry;
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
      background: transparent url("uploads/kitchen-page.jpg") no-repeat center/cover fixed;
    }
    .order-card {
      border:1px solid #ccc; border-radius:8px;
      margin:16px auto; padding:16px; max-width:600px;
      background:#FBDB93; color:#641B2E;
    }
    .order-header { display:flex; justify-content:space-between; margin-bottom:12px; }
    table {
      width:100%; border-collapse:collapse; margin-bottom:12px;
    }
    th,td {
      border:1px solid #ddd; padding:8px; text-align:left;
    }
    tr { background:#F3C623; }
    th { background:#BE5B50; color:white; }
    .option-pill {
      display:inline-block; margin:4px 6px 0 0;
      padding:2px 6px; border:1px solid #ccc; border-radius:12px;
      background:#f5f5f5; font-size:.9em; color:#333;
    }
    .serve-btn {
      background:#28a745; color:#fff; border:none;
      padding:8px 12px; border-radius:4px; cursor:pointer;
    }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>

  <div class="main-kitchen-list"></div>

  <script src="script.js"></script>
  <script>
    // ─── Bootstrapped data from PHP ───
    const INITIAL_ORDERS = <?= json_encode($initialOrders, JSON_UNESCAPED_UNICODE) ?>;

    // ─── WebSocket setup ───
    const socket = new WebSocket("ws://localhost:8080");
    socket.onopen = () => {
      socket.send(JSON.stringify({ type: "debug", message: "Kitchen page loaded" }));
    };

    // ─── Build a <tr> from a serve‐message payload ───
    function buildServeRow(data) {
      const tr = document.createElement("tr");
      const optionHTML = (data.options||[]).map(opt =>
        `<span class="option-pill">${opt}</span>`).join("");
      tr.innerHTML = `
        <td>${data.product}</td>
        <td>${data.quantity}</td>
        <td>${optionHTML}</td>
        <td>${data.note||''}</td>
      `;
      return tr;
    }

    // ─── Shared serve logic ───
    function handleServeMessage(data) {
      const orderId = data.order_id;
      let tbody = document.getElementById(`order-items-${orderId}`);
      const row   = buildServeRow(data);

      if (!tbody) {
        // first item for this order → create card
        createOrderCard(orderId, data, row);
        return;
      }

      // otherwise merge or append
      let match = null;
      Array.from(tbody.querySelectorAll("tr")).forEach(r => {
        const cells  = r.querySelectorAll("td");
        const prod   = cells[0].textContent.trim();
        const optsIn = Array.from(cells[2].querySelectorAll(".option-pill"))
                             .map(p=>p.textContent.trim());
        if (prod===data.product
         && JSON.stringify(optsIn)===JSON.stringify(data.options)) {
          match = r;
        }
      });
      if (match) {
        const qtyCell     = match.querySelectorAll("td")[1];
        const existingQty = parseInt(qtyCell.textContent,10)||0;
        qtyCell.textContent = existingQty + parseInt(data.quantity,10);
      } else {
        tbody.appendChild(row);
      }
    }

    // ─── Handle incoming WS messages ───
    socket.onmessage = e => {
      const data = JSON.parse(e.data);
      if (data.type==='cancel') {
        document.getElementById('order-card-'+data.order_id)?.remove();
        return;
      }
      if (data.type === 'order') {
        const card = document.getElementById('order-card-' + data.order_id);
        const placed = card?.querySelector('.placed-at');
        if (placed) {
          placed.textContent = 'Đặt Lúc: ' + new Date().toLocaleString();
        }
        return;
      }
      if (data.type==='change_table') {
        const card   = document.getElementById('order-card-'+data.order_id);
        const hdrDiv = card?.querySelector('.order-header>div');
        const placed = hdrDiv?.querySelector('.placed-at');
        if (hdrDiv) {
          hdrDiv.innerHTML = `<strong>Đơn Mới</strong><br>Bàn: ${data.new_table}<br>`;
          placed&&hdrDiv.appendChild(placed);
        }
        return;
      }
      if (data.type==='merge_table') {
        const from = document.getElementById('order-card-'+data.from_order_id);
        const into = document.getElementById('order-card-'+data.into_order_id);
        if (from && into) {
          Array.from(from.querySelectorAll('tbody tr'))
               .forEach(r=>into.querySelector('tbody').appendChild(r));
          from.remove();
        }
        return;
      }
      if (data.type==='serve' && data.quantity!=null) {
        handleServeMessage(data);
      }
    };

    // ─── Create a new order card with its first row ───
    function createOrderCard(orderId,data,firstRow) {
      const container = document.querySelector(".main-kitchen-list");
      const placedAt  = data.created_at
        ? new Date(data.created_at.replace(' ','T')).toLocaleString()
        : new Date().toLocaleString();
      const card = document.createElement("div");
      card.className = "order-card";
      card.id        = "order-card-"+orderId;
      card.innerHTML = `
        <div class="order-header">
          <div>
            <strong>Đơn Mới</strong><br>
            Bàn: ${data.table}<br>
            <span class="placed-at">Đặt Lúc: ${placedAt}</span>
          </div>
        </div>
        <form class="serve-form" data-order-id="${orderId}">
          <input type="hidden" name="order_id" value="${orderId}">
          <table>
            <thead>
              <tr>
                <th>Sản Phẩm</th><th>Số Lượng</th><th>Tùy Chọn</th><th>Ghi Chú</th>
              </tr>
            </thead>
            <tbody id="order-items-${orderId}"></tbody>
          </table>
          <button type="submit" class="serve-btn">Hoàn Tất</button>
        </form>
      `;
      container.appendChild(card);
      document.getElementById(`order-items-${orderId}`).appendChild(firstRow);
      bindFormHandlers();
    }

    // ─── Wire up “Hoàn Tất” buttons ───
    function bindFormHandlers() {
      document.querySelectorAll('.serve-form').forEach(form => {
        form.onsubmit = e => {
          e.preventDefault();
          handleServeSubmit(form);
        };
      });
    }

    // ─── When serve‐form is submitted, push real‐time messages ───
    function handleServeSubmit(form) {
      const rows   = form.querySelectorAll('tbody tr');
      if (!rows.length) return;
      const orderId= form.dataset.orderId;
      const table  = form.closest('.order-card')
                        .querySelector('.order-header')
                        .innerText.split('\n')
                        .find(l=>l.startsWith('Bàn:'))
                        .split(':')[1].trim();

      rows.forEach(r => {
        const cels = r.querySelectorAll('td');
        const opts = Array.from(cels[2].querySelectorAll('.option-pill'))
                          .map(p=>p.textContent.trim());
        socket.send(JSON.stringify({
          type:     'serve',
          table,
          product:  cels[0].textContent.trim(),
          quantity: cels[1].textContent.trim(),
          order_id: orderId,
          options:  opts
        }));
      });
      socket.send(JSON.stringify({
        type:     'order',
        table,
        order_id: orderId
      }));
      fetch('mark_served.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({order_id:orderId})
      })
      .then(r=>r.json())
      .then(j=>{ if(j.success) form.closest('.order-card').remove() })
      .catch(console.error);
    }

    // ─── 5) Bootstrap on DOMContentLoaded ───
    document.addEventListener('DOMContentLoaded', () => {
  fetch('get_unserved_orders.php')
    .then(res => {
      if (!res.ok) throw new Error(res.statusText);
      return res.json();
    })
    .then(orders => {
      // for each order, each item → handleServeMessage
      orders.forEach(o => {
        o.items.forEach(item => {
          handleServeMessage({
            type:       'serve',
            order_id:   o.order_id,
            table:      o.table_name,
            created_at: o.created_at,
            product:    item.product_name,
            quantity:   item.quantity,
            options:    item.options,
            note:       item.note
          });
        });
      });
      // wire up your serve buttons
      bindFormHandlers();
    })
    .catch(err => console.error('Failed to load initial orders:', err));
});
  </script>
</body>
</html>
