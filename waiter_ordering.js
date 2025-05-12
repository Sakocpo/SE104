// waiter_ordering.js

let currentProduct, optionsData = {}, currentOptions = {}, currentCategory, order = [];

/**
 * Open the “choose options” pop‑up for a product
 */
function openOptionsPopup(product) {
  currentProduct = product;
  document.getElementById("popup-product-name").innerText = product.name;
  document.getElementById("quantity-input").value = 1;

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
 * Render the labels for the currently selected option‑category
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
 * Add current selection to the in‑memory order
 */
function addToOrder() {
  const qty = parseInt(document.getElementById("quantity-input").value) || 0;
  order.push({
    product : currentProduct,
    quantity: qty,
    options : { ...currentOptions }
  });
  closePopup();
}

/**
 * Show the review panel, let you tweak each line
 */
function openReview() {
  const list = document.getElementById("order-summary-list"),
        popup = document.getElementById("order-review");
  list.innerHTML = "";

  order.forEach((item, idx) => {
    const row = document.createElement("div");
    row.className = "review-item";

    // Product + options text
    const opts = [];
    for (let cat in item.options) {
      if (item.options[cat]) opts.push(`${cat}: ${item.options[cat]}`);
    }
    const title = document.createElement("div");
    title.className = "ri-name";
    title.innerText = item.product.name
                      + (opts.length ? " — " + opts.join(" | ") : "");

    // Qty controls
    const qc = document.createElement("div");
    qc.className = "ri-qty";

    const dec = document.createElement("button"); dec.type = "button"; dec.innerText = "−";
    const inp = document.createElement("input");
          inp.type = "number";
          inp.value = item.quantity;
          inp.onchange = () => {
            item.quantity = Math.max(0, parseInt(inp.value) || 0);
            inp.value = item.quantity;
            if (item.quantity === 0) row.style.display = "none";
          };
    const inc = document.createElement("button"); inc.type = "button"; inc.innerText = "+";

    dec.onclick = () => {
      item.quantity = Math.max(0, item.quantity - 1);
      inp.value = item.quantity;
      if (item.quantity === 0) row.style.display = "none";
    };
    inc.onclick = () => {
      item.quantity++;
      inp.value = item.quantity;
      row.style.display = "";
    };

    qc.append(dec, inp, inc);
    row.append(title, qc);
    list.appendChild(row);
  });

  popup.style.display = "flex";
}

function closeReview() {
  document.getElementById("order-review").style.display = "none";
}

/**
 * Submit only items with quantity > 0
 */
function submitOrder(tableId) {
  // build only the items with qty > 0
  const itemsPayload = order
    .filter(it => it.quantity > 0)
    .map(it => ({
      product_id: it.product.id,
      options: Object.entries(it.options)
        .map(([cat,label]) => {
          const opt = optionsData[cat].find(o=>o.label===label);
          return opt ? opt.id : null;
        })
        .filter(x=>x!=null),
      quantity: it.quantity
    }));

  // **NEW**: if nothing selected, stop here
  if (itemsPayload.length === 0) {
    alert("Please choose at least one product before sending to kitchen.");
    return;
  }

  const payload = { table_id: tableId, items: itemsPayload };

  fetch("submit_order.php", {
    method: "POST",
    headers: { "Content-Type":"application/json" },
    body: JSON.stringify(payload)
  })
  .then(r=>r.json())
  .then(json=>{
    if (json.success) {
      alert("Sent to kitchen!");
      window.location = "table_management_waiter.php";
    } else {
      alert("Error: " + (json.error||"unknown"));
    }
  })
  .catch(err=>{
    console.error(err);
    alert("Failed to submit order.");
  });
}
