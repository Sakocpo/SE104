let currentProduct = null;
let optionsData     = {};       // fetched from server: { categoryName: [ {id,label}, ... ] }
let currentOptions  = {};       // your picks: { categoryName: selectedLabel }
let currentCategory = null;     // which tab is open
let order          = [];       // { product, quantity, options: { categoryName: selectedLabel } }

function openOptionsPopup(product) {
  currentProduct = product;
  document.getElementById("popup-product-name").innerText = product.name;
  document.getElementById("quantity-input").value     = 1;

  const catRow  = document.getElementById("option-categories");
  const itemRow = document.getElementById("option-items");
  catRow.innerHTML  = "Loading…";
  itemRow.innerHTML = "";

  fetch(`fetch_options.php?product_id=${product.id}`)
    .then(r => r.json())
    .then(data => {
      optionsData    = data;
      currentOptions = {};
      catRow.innerHTML = "";

      // pick first category as default
      currentCategory = Object.keys(data)[0] || null;

      // initialize default choice = first label
      for (let cat of Object.keys(data)) {
        currentOptions[cat] = data[cat][0]?.label || null;
      }

      // build category tabs
      Object.keys(data).forEach(cat => {
        const btn = document.createElement("div");
        btn.className = "option-category-btn";
        btn.innerText = cat;
        btn.onclick = () => {
          currentCategory = cat;
          // refresh tab highlight & content
          document.querySelectorAll(".option-category-btn")
                  .forEach(b => b.classList.toggle("active", b === btn));
          renderItems();
        };
        catRow.appendChild(btn);
      });

      // mark first active
      catRow.querySelector(".option-category-btn").classList.add("active");
      renderItems();
    })
    .catch(err => {
      catRow.innerText = "Failed to load options.";
      console.error(err);
    });

  document.getElementById("options-popup").style.display = "flex";
}

// render only currentCategory’s labels
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
      // re-draw only this category’s pills:
      itemRow.querySelectorAll(".option-item")
             .forEach(p => p.classList.toggle("selected", p === pill));
    };
    itemRow.appendChild(pill);
  });
}

function closePopup() {
  document.getElementById("options-popup").style.display = "none";
}
function adjustQty(d) {
  const i = document.getElementById("quantity-input");
  let v = parseInt(i.value)||1; i.value = Math.max(1, v + d);
}
function addToOrder() {
  const qty = parseInt(document.getElementById("quantity-input").value)||1;
  order.push({ product: currentProduct, quantity: qty, options: {...currentOptions} });
  closePopup();
}
// … rest of your review/submit handlers …


function openReview() {
  const container = document.getElementById("order-summary-list");
  container.innerHTML = "";

  order.forEach((item, idx) => {
    const row = document.createElement("div");
    row.className = "review-item";

    // product name + options string
    let opts = [];
    for (let cat in item.options) {
      if (item.options[cat]) opts.push(`${cat}: ${item.options[cat]}`);
    }
    const title = document.createElement("div");
    title.className = "name";
    title.innerText = `${item.product.name} ${opts.length? '- '+opts.join(' | '): ''}`;

    // quantity controls
    const qc = document.createElement("div");
    qc.className = "qty-control";

    const btnDec = document.createElement("button");
    btnDec.innerText = "−";
    btnDec.onclick = () => {
      if (item.quantity > 1) {
        item.quantity--;
        qtyInput.value = item.quantity;
      }
    };

    const qtyInput = document.createElement("input");
    qtyInput.type = "number";
    qtyInput.value = item.quantity;
    qtyInput.min = 1;
    qtyInput.onchange = () => {
      let v = parseInt(qtyInput.value)||1;
      item.quantity = Math.max(1,v);
      qtyInput.value = item.quantity;
    };

    const btnInc = document.createElement("button");
    btnInc.innerText = "+";
    btnInc.onclick = () => {
      item.quantity++;
      qtyInput.value = item.quantity;
    };

    qc.append(btnDec, qtyInput, btnInc);

    row.append(title, qc);
    container.appendChild(row);
  });

  document.getElementById("order-review").style.display = "flex";
}


function closeReview() {
  document.getElementById("order-review").style.display = "none";
}

function submitOrder(tableId) {
  alert("Order sent!");
  window.location.href = `table_management_waiter.php?highlight=${tableId}`;
}


const categoryBoxes = document.querySelectorAll('.option-category-box');
const optionContents = document.querySelectorAll('.option-content');

categoryBoxes.forEach(box => {
  box.addEventListener('click', () => {
    // Remove active from all boxes
    categoryBoxes.forEach(b => b.classList.remove('active'));
    // Hide all option contents
    optionContents.forEach(c => c.classList.remove('visible'));

    // Activate clicked box
    box.classList.add('active');
    // Show its related option-content
    const content = box.querySelector('.option-content');
    if (content) {
      content.classList.add('visible');
    }
  });
});
