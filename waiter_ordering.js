let currentProduct, optionsData = {}, currentOptions = {}, currentCategory, order = [];
const waiterSocket = new WebSocket("ws://localhost:8080");

waiterSocket.onopen = () => {
  console.log("✅ WebSocket connected from waiter_ordering.js");
};

/**
 * Open the "choose options" pop-up for a product
 */
function openOptionsPopup(product) {
  currentProduct = product;
  document.getElementById("popup-product-name").innerText = product.name;
  document.getElementById("quantity-input").value = 1;

  document.getElementById("note-input").value = "";

  const catRow  = document.getElementById("option-categories");
  const itemRow = document.getElementById("option-items");
  catRow.innerHTML  = "Loading…";
  itemRow.innerHTML = "";

  fetch(`fetch_options.php?product_id=${product.id}`)
    .then(r => r.json())
    .then(data => {
      optionsData = data;
      const cats = Object.keys(data);
      if (!cats.length) {
        catRow.innerText = "No options";
        return;
      }
      // default picks
      currentCategory = cats[0];
      currentOptions = {};
      cats.forEach(cat => {
        currentOptions[cat] = data[cat][0]?.label || null;
      });

      // build category tabs
      catRow.innerHTML = "";
      cats.forEach(cat => {
        const btn = document.createElement("div");
        btn.className = "option-category-btn";
        btn.innerText = cat;
        btn.onclick = () => {
          currentCategory = cat;
          catRow.querySelectorAll(".option-category-btn")
                .forEach(b => b.classList.toggle("active", b === btn));
          renderItems();
        };
        catRow.appendChild(btn);
      });
      catRow.firstChild.classList.add("active");
      renderItems();
    })
    .catch(err => {
      console.error(err);
      catRow.innerText = "Failed to load options.";
    });

  document.getElementById("options-popup").style.display = "flex";
}

/**
 * Render the labels for the currently selected option-category
 */
function renderItems() {
  const itemRow = document.getElementById("option-items");
  itemRow.innerHTML = "";
  if (!currentCategory) return;

  optionsData[currentCategory].forEach(opt => {
    const pill = document.createElement("div");
    pill.className = "option-item";
    pill.innerText = opt.label;
    if (currentOptions[currentCategory] === opt.label) {
      pill.classList.add("selected");
    }
    pill.onclick = () => {
      currentOptions[currentCategory] = opt.label;
      itemRow.querySelectorAll(".option-item")
             .forEach(p => p.classList.toggle("selected", p === pill));
    };
    itemRow.appendChild(pill);
  });
}

function closePopup() {
  document.getElementById("options-popup").style.display = "none";
}

/**
 * Adjust quantity by delta, allow going down to 0
 */
function adjustQty(delta) {
  const inp = document.getElementById("quantity-input");
  let v = parseInt(inp.value) || 0;
  inp.value = Math.max(0, v + delta);
}

/**
 * Add current selection to the in-memory order
 */
function notifyKitchenProduct(productName, tableId, optionIDs) {
  // Convert IDs → labels, then send a WS message
  const labels = (Array.isArray(optionIDs) ? optionIDs : [])
    .map(getOptionLabel)
    .filter(lbl => lbl);

  if (waiterSocket.readyState === WebSocket.OPEN) {
    waiterSocket.send(JSON.stringify({
      type:    "add",
      table:   "Bàn " + tableId,
      product: productName,
      options: labels   // e.g. ["Nóng","Đá xay"]
    }));
  }
}


function addToOrder() {
  const qty = parseInt(document.getElementById("quantity-input").value, 10);
  if (isNaN(qty) || qty <= 0) return;

  const noteText = document.getElementById("note-input").value.trim();

  // 1) Map each chosen category→label to a numeric ID, using optionsData
  const selectedOptionIDs = Object.entries(currentOptions)
    .map(([cat, label]) => {
      const optList = optionsData[cat] || [];
      const found   = optList.find(o => o.label === label);
      return found ? found.id : null;
    })
    .filter(id => id != null);

  // 2) Combine identical line‐items (same product + same option IDs) by incrementing qty
  let foundItem = false;
  for (let item of order) {
    if (
      item.product.id === currentProduct.id &&
      item.note === noteText && 
      Array.isArray(item.options) &&
      item.options.length === selectedOptionIDs.length &&
      item.options.every((v,i) => v === selectedOptionIDs[i])
    ) {
      item.quantity += qty;
      foundItem = true;
      break;
    }
  }
  // 3) If not found, push a brand-new line
  if (!foundItem) {
    order.push({
      product: currentProduct,
      options: selectedOptionIDs,  // e.g. [2,5]
      quantity: qty,
      note: noteText 
    });
  }

  // 4) Send a real-time "add" WS message including the human labels
  notifyKitchenProduct(
    currentProduct.name,
    currentProduct.table_id || "???",
    selectedOptionIDs
  );

  // 5) Close the pop-up
  closePopup();
}



/**
 * Show the review panel, let you tweak each line
 */
function openReview() {
  const list  = document.getElementById("order-summary-list"),
        popup = document.getElementById("order-review");
  list.innerHTML = "";

  // Filter out items with quantity 0 first and update the order array
  order = order.filter(item => item.quantity > 0);

  // If no items left, show message and return
  if (order.length === 0) {
    const emptyMessage = document.createElement("div");
    emptyMessage.style.textAlign = "center";
    emptyMessage.style.padding = "20px";
    emptyMessage.style.color = "#666";
    emptyMessage.innerText = "Chưa Có Món Nào";
    list.appendChild(emptyMessage);
    popup.style.display = "flex";
    return;
  }

  order.forEach(item => {
    // ─── Row wrapper ───
    const row = document.createElement("div");
    row.className = "review-item";

    // ─── Left "info" column ───
    const info = document.createElement("div");
    info.className = "ri-info";

    // Product name
    const title = document.createElement("div");
    title.className = "ri-name";
    title.innerText = item.product.name;
    if (item.note)
    {
      title.innerText = `${item.product.name} [${item.note}]`;
    }
    else
    {
      title.innerText = item.product.name;
    }
    info.appendChild(title);
    // Option pills
    const optsDiv = document.createElement("div");
    optsDiv.className = "item-options";
    // Build the little “• Label” pills from the numeric IDs in item.options
item.options.forEach(optionId => {
  const pill = document.createElement("span");
  pill.className = "option-label";
  pill.innerHTML = `&bull; ${ getOptionLabel(optionId) }`;
  optsDiv.appendChild(pill);
});

    if (optsDiv.childElementCount) {
      info.appendChild(optsDiv);
    }

    // ─── Right "quantity" column ───
    const qc = document.createElement("div");
    qc.className = "ri-qty";

    const dec = document.createElement("button");
    dec.type = "button";
    dec.innerText = "−";
    dec.onclick = () => {
      item.quantity = Math.max(0, item.quantity - 1);
      inp.value = item.quantity;
      if (item.quantity === 0) {
        // Remove the item from the order array
        const index = order.indexOf(item);
        if (index > -1) {
          order.splice(index, 1);
        }
        row.remove(); // Remove the row from DOM
        
        // If no items left, show message
        if (order.length === 0) {
          const emptyMessage = document.createElement("div");
          emptyMessage.style.textAlign = "center";
          emptyMessage.style.padding = "20px";
          emptyMessage.style.color = "#666";
          emptyMessage.innerText = "No items in order";
          list.appendChild(emptyMessage);
        }
      }
    };

    const inp = document.createElement("input");
    inp.type = "number";
    inp.value = item.quantity;
    inp.onchange = () => {
      item.quantity = Math.max(0, parseInt(inp.value, 10) || 0);
      inp.value = item.quantity;
      if (item.quantity === 0) {
        // Remove the item from the order array
        const index = order.indexOf(item);
        if (index > -1) {
          order.splice(index, 1);
        }
        row.remove(); // Remove the row from DOM
        
        // If no items left, show message
        if (order.length === 0) {
          const emptyMessage = document.createElement("div");
          emptyMessage.style.textAlign = "center";
          emptyMessage.style.padding = "20px";
          emptyMessage.style.color = "#666";
          emptyMessage.innerText = "No items in order";
          list.appendChild(emptyMessage);
        }
      }
    };

    const inc = document.createElement("button");
    inc.type = "button";
    inc.innerText = "+";
    inc.onclick = () => {
      item.quantity++;
      inp.value = item.quantity;
      row.style.display = "";
    };

    qc.append(dec, inp, inc);

    // ─── Assemble and show ───
    row.appendChild(info);
    row.appendChild(qc);
    list.appendChild(row);
  });

  popup.style.display = "flex";
}

function closeReview() {
  document.getElementById("order-review").style.display = "none";
}

function getOptionLabel(optionId) {
  for (const category of Object.values(optionsData)) {
    const opt = category.find(o => o.id === optionId);
    if (opt) return opt.label;
  }
  return '';
}
function getProductName(productId) {
  const product = products.find(p => p.id === productId);
  return product ? product.name : 'Unknown';
}

/**
 * Submit only items with quantity > 0
 */
/**
 * Submit the current `order` to the server, then broadcast each line‐item in real time
 * over WebSocket with actual labels so the kitchen can render them.
 */
function submitOrder(tableId) {
  if (!order.length) {
    console.log("submitOrder: no items to submit (order is empty).");
    if (waiterSocket.readyState === WebSocket.OPEN) {
      waiterSocket.send(JSON.stringify({
        type:    "debug",
        message: "submitOrder called with empty order."
      }));
    }
    return;
  }

  // 1) Log raw `order`
  console.log("submitOrder: raw order =", order);
  if (waiterSocket.readyState === WebSocket.OPEN) {
    waiterSocket.send(JSON.stringify({
      type:    "debug",
      message: "Raw order: " + JSON.stringify(order)
    }));
  }

  // 2) Build payload for PHP
  const itemsPayload = order.map(item => {
    const payloadItem = {
      product_id: item.product.id,
      quantity:   item.quantity,
      options:    Array.isArray(item.options) ? item.options : [],  // these are numeric IDs
      note:      item.note 
    };
    console.log("submitOrder: mapping item → payloadItem:", payloadItem);
    if (waiterSocket.readyState === WebSocket.OPEN) {
      waiterSocket.send(JSON.stringify({
        type:    "debug",
        message: "Mapping item → payloadItem: " + JSON.stringify(payloadItem)
      }));
    }
    return payloadItem;
  });

  const data = {
    table_id: tableId,
    items:    itemsPayload
  };

  // 3) Log final payload
  console.log("submitOrder: final data payload =", data);
  if (waiterSocket.readyState === WebSocket.OPEN) {
    waiterSocket.send(JSON.stringify({
      type:    "debug",
      message: "Final payload: " + JSON.stringify(data)
    }));
  }

  // 4) Post to server
  fetch("submit_order.php", {
    method:  "POST",
    headers: { "Content-Type": "application/json" },
    body:    JSON.stringify(data)
  })
    .then(response => {
      console.log("submitOrder: HTTP status =", response.status);
      if (waiterSocket.readyState === WebSocket.OPEN) {
        waiterSocket.send(JSON.stringify({
          type:    "debug",
          message: "HTTP response status: " + response.status
        }));
      }
      return response.json();
    })
    .then(json => {
      console.log("submitOrder: server JSON response =", json);
      if (waiterSocket.readyState === WebSocket.OPEN) {
        waiterSocket.send(JSON.stringify({
          type:    "debug",
          message: "Server JSON response: " + JSON.stringify(json)
        }));
      }

      if (json.success) {
        // 5) Send one WS “serve” message per line, with actual labels
        order.forEach(item => {
          const optionLabels = (Array.isArray(item.options) ? item.options : [])
            .map(optionId => getOptionLabel(optionId))
            .filter(lbl => lbl);

          const serveMsg = {
            type:      "serve",
            order_id:  json.order_id,
            table:     json.table,
            product:   item.product.name,
            quantity:  item.quantity,
            options:   optionLabels,
            note:      item.note
          };

          console.log("submitOrder: WS send serveMsg →", serveMsg);
          if (waiterSocket.readyState === WebSocket.OPEN) {
            waiterSocket.send(JSON.stringify(serveMsg));
          }
        });

        // 6) Send final WS “order complete” message
        const doneMsg = {
          type:     "order",
          order_id: json.order_id,
          table:    json.table
        };
        console.log("submitOrder: WS send doneMsg →", doneMsg);
        if (waiterSocket.readyState === WebSocket.OPEN) {
          waiterSocket.send(JSON.stringify(doneMsg));
        }

        console.log("submitOrder: submission succeeded, redirecting to waiter.php");
        if (waiterSocket.readyState === WebSocket.OPEN) {
          waiterSocket.send(JSON.stringify({
            type:    "debug",
            message: "Submission succeeded, redirecting."
          }));
        }
        window.location.href = "table_management_waiter.php";
      } else {
        console.warn("submitOrder: submission failed —", json.error);
        if (waiterSocket.readyState === WebSocket.OPEN) {
          waiterSocket.send(JSON.stringify({
            type:    "debug",
            message: "Submission failed: " + json.error
          }));
        }
        alert("Failed to submit order: " + json.error);
      }
    })
    .catch(err => {
      console.error("submitOrder: fetch error —", err);
      if (waiterSocket.readyState === WebSocket.OPEN) {
        waiterSocket.send(JSON.stringify({
          type:    "debug",
          message: "Fetch error: " + err.toString()
        }));
      }
      alert("Failed to submit order");
    });
}

