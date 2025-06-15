<?php
session_start();
require 'config.php';

$error = '';

// Only kitchen users can view
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'kitchen') {
    http_response_code(403);
    echo 'Error 403: Unauthorized access';
    exit();
}
// ------------------------------------------------------
// 1) Build a PHP mapping: unit_id → unit_label
$unitLabels = [];
$unitResult = $connection->query("SELECT id, name FROM unit_options");
if ($unitResult) {
    while ($row = $unitResult->fetch_assoc()) {
        // Ensure we cast the ID to integer
        $unitLabels[intval($row['id'])] = $row['name'];
    }
    $unitResult->free();
}

// ------------------------------------------------------
// 2) Handle form submission for “take out” quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ingredient_id'], $_POST['take_out'])) {
    $ing_id   = intval($_POST['ingredient_id']);
    $take_out = floatval($_POST['take_out']);
    $cat_id   = isset($_POST['current_category']) ? intval($_POST['current_category']) : null;

    // --- 1) Fetch current stock, unit_id, and name ---
    $stmt = $connection->prepare("
        SELECT quantity, name, unit_id 
        FROM ingredients 
        WHERE id = ?
        AND deleted = 0
    ");
    $stmt->bind_param("i", $ing_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res) {
        $before_qty = floatval($res['quantity']);
        $unit_id    = intval($res['unit_id']);
        $ing_name   = $res['name'];

        // --- 2a) Validate user input ---
        if ($take_out <= 0) {
            $error = 'Vui lòng nhập số lượng > 0';
        } elseif ($take_out > $before_qty) {
            $error = 'Số lượng lấy vượt quá số đang có';
        } else {
            // --- 2b) Perform the stock update ---
            $after_qty = $before_qty - $take_out;
            $upd = $connection->prepare("
                UPDATE ingredients 
                SET quantity = ? 
                WHERE id = ?
            ");
            $upd->bind_param("di", $after_qty, $ing_id);
            $upd->execute();
            $upd->close();

            // --- 3) Insert a new row into `ingredient_logs` ---
            //     We record: ingredient_id, taken_quantity, unit_id, user_id.
            //     Assume the logged‐in user’s ID is $_SESSION['user']['id'].
            $user_id = isset($_SESSION['user']['id']) 
                       ? intval($_SESSION['user']['id']) 
                       : null;

            $change_amount = $take_out; // “how much was removed” (positive)

            // Prepare an INSERT into (ingredient_id, user_id, change_amount, before_qty, after_qty)
            $logStmt = $connection->prepare("
                INSERT INTO ingredient_logs
                  (ingredient_id, user_id, change_amount, before_qty, after_qty)
                VALUES (?, ?, ?, ?, ?)
            ");

            // Bind parameters: “i” = INT, “d” = DECIMAL/DOUBLE
            $logStmt->bind_param(
                "idddd",
                $ing_id,        // INT
                $user_id,       // INT
                $change_amount, // DECIMAL(10,2) → bind as double
                $before_qty,    // DECIMAL(10,2) → bind as double
                $after_qty      // DECIMAL(10,2) → bind as double
            );

            $logStmt->execute();
            $logStmt->close();

            // --- 4) Build a “taken_label” notification to show on redirect ---
            //     (We assume you have a PHP array $unitLabels[unit_id] => label.)
            $unitLabel = '';
            if (isset($unitLabels[$unit_id])) {
                $unitLabel = $unitLabels[$unit_id];
            }
            $label = "{$take_out} {$unitLabel} {$ing_name}";

            header(
              "Location: kitchen_ingredients.php?"
              . "category=" . ($cat_id !== null ? $cat_id : '') 
              . "&taken_label=" . urlencode($label)
            );
            exit;
        }
    } else {
        $error = 'Nguyên liệu không tồn tại';
    }
}

// ------------------------------------------------------
// 3) Determine current category from GET (if any)
$current_category_id = isset($_GET['category']) ? intval($_GET['category']) : null;
$taken_label        = isset($_GET['taken_label']) ? htmlspecialchars($_GET['taken_label'], ENT_QUOTES) : '';

// ------------------------------------------------------
// 4) Fetch all ingredient categories (for the top bar)
$categories_result = $connection->query("SELECT * FROM ingredient_categories");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

// ------------------------------------------------------
// 5) Fetch all ingredients in the selected category (if any)
$ingredients = [];
if ($current_category_id !== null) {
    $stmt = $connection->prepare("
        SELECT * 
        FROM ingredients 
        WHERE category = ?
        AND deleted = 0
    ");
    $stmt->bind_param("i", $current_category_id);
    $stmt->execute();
    $ingredients_result = $stmt->get_result();
    $ingredients = $ingredients_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!doctype html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kitchen – Ingredients</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      background-image: url("uploads/kitchen-page.jpg");
      background-color: transparent;
      background-repeat: no-repeat;
      background-attachment: fixed;
      background-position: center;
      background-size: cover;
      color: black;
    }
    /* Horizontal category bar */
    .top-category-bar {
      position: fixed;
      top: 0; left: 50px; right: 0;
      background: #f0f0f0;
      padding: 10px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      z-index: 1000;
      border-bottom: 1px solid #ccc;
    }
    .top-category-bar .cat-list {
      display: flex;
      gap: 12px;
      overflow-x: auto;
    }
    .top-category-bar .cat-list a {
      padding: 8px 14px;
      border-radius: 18px;
      text-decoration: none;
      white-space: nowrap;
      font-weight: normal;
      color: #333;
      background-color: #e0e0e0;
    }
    .top-category-bar .cat-list a.selected {
      background-color: #007bff;
      color: white;
      font-weight: bold;
    }
    /* Ingredient grid */
    .product-grid {
      display: flex;
      flex-wrap: wrap;
      margin-top: 80px; /* leave space for top bar */
      gap: 16px;
      padding: 20px;
      margin-left: 50px; /* align with left after sidebar */
    }
    .product-card .qty-display {
      font-weight: bold;
      color: #28a745;
    }
    /* Popup overlay */
    .overlay {
      display: none;
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.5);
      align-items: center;
      justify-content: center;
      z-index: 2000;
    }
    .overlay .popup {
      background: white;
      padding: 20px;
      border-radius: 8px;
      width: 300px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
      text-align: center;
    }
    .overlay .popup h3 {
      margin-top: 0;
      color: black;
    }
    .overlay .popup .controls {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 16px;
      margin: 16px 0;
    }
    .overlay .popup .controls button {
      width: 40px;
      height: 40px;
      color: black;
      font-size: 24px;
      border: 1px solid #ccc;
      border-radius: 4px;
      background: #f0f0f0;
      cursor: pointer;
    }
    .overlay .popup .controls span {
      display: inline-block;
      width: 80px;
      text-align: center;
      font-size: 18px;
      color: #28a745;
    }
    .overlay .popup .save-btn {
      background: #28a745;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
    }
    .overlay .popup .cancel-btn {
      background: #ccc;
      color: #333;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
    }
    /* Confirmation popup style */
    .confirm-popup {
      background: rgba(240,240,240,0.95);
      color: black;
      padding: 20px;
      border-radius: 6px;
      max-width: 280px;
      margin: 0 auto;
    }
    .confirm-popup h3 {
      margin-top: 0;
    }
    .confirm-popup form {
      margin-top: 12px;
      display: flex;
      gap: 12px;
      justify-content: space-between;
    }
    .confirm-popup .confirm-btn {
      background: #28a745;
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: 4px;
      cursor: pointer;
    }
    .confirm-popup .cancel-btn {
      background: #ccc;
      color: #333;
      border: none;
      padding: 6px 12px;
      border-radius: 4px;
      cursor: pointer;
    }
    /* Error popup if JS‐side error */
    .js-error-popup {
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      background: #f8d7da;
      color: #721c24;
      padding: 12px 20px;
      border: 1px solid #f5c6cb;
      border-radius: 6px;
      z-index: 3500;
      display: none;
    }
    /* Notification popup */
    .notification-popup {
      display: none;
      position: fixed;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      background: #d4edda;
      color: #155724;
      padding: 12px 20px;
      border: 1px solid #c3e6cb;
      border-radius: 6px;
      font-size: 1em;
      z-index: 3000;
    }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>

  <!-- Server‐side error (e.g. from POST ≔ quantity ≤ 0) -->
  <?php if ($error): ?>
    <div class="error-popup" style="
         position: fixed; 
         top: 20px; 
         left: 50%; 
         transform: translateX(-50%); 
         background: #f8d7da; 
         color: #721c24; 
         padding: 12px 20px; 
         border: 1px solid #f5c6cb; 
         border-radius: 6px; 
         z-index: 3000;
         ">
      <?= htmlspecialchars($error) ?>
      <button 
        style="margin-top: 10px; margin-bottom: 5px; padding: 5px;" 
        onclick="this.parentElement.style.display='none'">
        Close
      </button>
    </div>
  <?php endif; ?>

  <!-- JS‐side error container (for “qty = 0” client check) -->
  <div id="jsError" class="js-error-popup"></div>

  <!-- Horizontal category bar -->
  <div class="top-category-bar">
    <div class="cat-list">
      <?php foreach ($categories as $cat): 
        $cls = ($current_category_id == $cat['id']) ? 'selected' : '';
      ?>
        <a href="kitchen_ingredients.php?category=<?= $cat['id'] ?>"
           class="<?= $cls ?>">
          <?= htmlspecialchars($cat['name']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Ingredient grid (each card has data‐unit-label instead of raw ID) -->
  <div class="product-grid">
    <?php foreach ($ingredients as $ing): 
        // Look up human label for this ingredient’s unit_id
        $uLabel = isset($unitLabels[intval($ing['unit_id'])]) 
                  ? $unitLabels[intval($ing['unit_id'])] 
                  : '';
    ?>
      <div 
        class="product-card"
        data-id="<?= $ing['id'] ?>"
        data-name="<?= htmlspecialchars($ing['name'], ENT_QUOTES) ?>"
        data-qty="<?= $ing['quantity'] ?>"
        data-unit-label="<?= htmlspecialchars($uLabel, ENT_QUOTES) ?>"
        onclick="openAdjustPopup(this)"
      >
        <div class="product-block">
          <img src="<?= htmlspecialchars($ing['image'] ?? 'placeholder.png') ?>" alt="">
        </div>
        <div class="text-block" style="padding: 10px;">
          <h4 style="margin-bottom: 5px;"><?= htmlspecialchars($ing['name']) ?></h4>
          <div class="qty-display">Hiện Có: <?= number_format($ing['quantity'], 2) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Popup overlay for adjustment -->
  <div id="adjust-overlay" class="overlay">
  <div class="popup">
    <h3 id="popup-name">Tên Nguyên Liệu</h3>
    <form method="POST" id="adjust-form" style="margin: 0;">
      <input type="hidden" name="ingredient_id" id="form-ingredient-id">
      <input type="hidden" name="take_out" id="form-take-out">
      <input type="hidden" name="current_category" value="<?= $current_category_id ?>">

      <!-- =========================
           QUANTITY CONTROLS
           ========================= -->
      <div class="controls">
        <button 
          type="button" 
          onclick="changeQty(-1)"
          title="Giảm 1 đơn vị"
        >
          −
        </button>

        <!-- Now an <input> so user can type directly: -->
        <input 
          type="number" 
          id="popup-qty-input"
          value="0.00"
          min="0" 
          step="0.01"
          style="width: 80px; text-align: center; font-size: 18px; color: #28a745; border: 1px solid #ccc; border-radius: 4px; padding: 4px;"
          oninput="onQtyInputChange()"
        >

        <button 
          type="button" 
          onclick="changeQty(+1)"
          title="Tăng 1 đơn vị"
        >
          +
        </button>
      </div>

      <div>
        <button 
          type="button" 
          class="save-btn" 
          onclick="showConfirmPopup()"
        >
          Lưu
        </button>
        <button 
          type="button" 
          class="cancel-btn" 
          onclick="closeOverlay()"
        >
          Hủy
        </button>
      </div>
    </form>

    <!-- =========================
         CONFIRMATION PROMPT
         ========================= -->
    <div id="confirm-popup" class="confirm-popup" style="display:none;">
      <h3>Xác Nhận Lấy</h3>
      <p style="font-size: 0.9em;" id="confirm-message"></p>
      <form method="POST" id="confirm-form">
        <input type="hidden" name="ingredient_id" id="confirm-ingredient-id">
        <input type="hidden" name="take_out" id="confirm-take-out">
        <input type="hidden" name="current_category" value="<?= $current_category_id ?>">
        <button style="margin-bottom: 5px;" type="submit" class="confirm-btn">Xác Nhận</button>
        <button style="margin-bottom: 5px;" type="button" class="cancel-btn" onclick="hideConfirmPopup()">Hủy</button>
      </form>
    </div>
  </div>
</div>


  <script src="script.js"></script>
  <script>
    // =============================
// GLOBAL VARIABLES
// =============================
let originalQty     = 0;     // The maximum available quantity for this ingredient
let takeOutQty      = 0;     // How many units the user currently wants to take
let currentIngrName = '';    // Ingredient name (for message)
let currentUnit     = '';    // Unit label, e.g. “hộp”, “kg”


// =============================
// openAdjustPopup(cardElem)
//   Called when user clicks a .product-card.
//   Reads data-* attributes: data-qty and data-unit-label.
//   Initializes both the numeric INPUT and the hidden <input name="take_out">.
// =============================
function openAdjustPopup(cardElem) {
  const ingId        = parseInt(cardElem.dataset.id, 10);
  currentIngrName    = cardElem.dataset.name;
  originalQty        = parseFloat(cardElem.dataset.qty);
  currentUnit        = cardElem.dataset.unitLabel;
  takeOutQty         = 0;

  // Set popup title
  document.getElementById('popup-name').innerText = currentIngrName;

  // Hidden field for POST
  document.getElementById('form-ingredient-id').value = ingId;
  document.getElementById('form-take-out').value      = takeOutQty.toFixed(2);

  // The numeric <input> in the UI:
  const qtyInput = document.getElementById('popup-qty-input');
  qtyInput.value = takeOutQty.toFixed(2);
  qtyInput.min   = 0;               // ensure no negative typing
  qtyInput.max   = originalQty;     // we’ll clamp in JS
  qtyInput.step  = 0.01;

  // Show the adjust form, hide confirmation
  document.getElementById('adjust-form').style.display   = 'block';
  document.getElementById('confirm-popup').style.display = 'none';
  document.getElementById('adjust-overlay').style.display = 'flex';
}


// =============================
// changeQty(delta)
//   Called when clicking the “−” or “+” buttons.
//   Adjusts takeOutQty by delta, then clamps to [0, originalQty].
//   Updates both the numeric <input> and the hidden <input name="take_out">.
// =============================
function changeQty(delta) {
  // 1) Adjust
  takeOutQty = takeOutQty + delta;

  // 2) Clamp to [0, originalQty]
  if (takeOutQty < 0) {
    takeOutQty = 0;
  }
  if (takeOutQty > originalQty) {
    takeOutQty = originalQty;
  }

  // 3) Update the numeric <input>
  const qtyInput = document.getElementById('popup-qty-input');
  qtyInput.value = takeOutQty.toFixed(2);

  // 4) Update hidden form‐field
  document.getElementById('form-take-out').value = takeOutQty.toFixed(2);
}


// =============================
// onQtyInputChange()
//   Called whenever the user types/pastes into #popup-qty-input.
//   We parse the value, clamp it to [0, originalQty], and update takeOutQty.
//   Then push that clamped value back into both the <input> and the hidden field.
// =============================
function onQtyInputChange() {
  const qtyInput = document.getElementById('popup-qty-input');
  let val = parseFloat(qtyInput.value);

  // If the user typed something invalid (empty or non‐numeric), treat as 0
  if (isNaN(val) || val < 0) {
    val = 0;
  }

  // Clamp to max
  if (val > originalQty) {
    val = originalQty;
  }

  // Round to two decimal places
  val = Math.round(val * 100) / 100;

  // Update our global and both inputs
  takeOutQty = val;
  qtyInput.value = takeOutQty.toFixed(2);
  document.getElementById('form-take-out').value = takeOutQty.toFixed(2);
}


// =============================
// showConfirmPopup()
//   Called when user clicks “Lưu”. If takeOutQty === 0, show a JS‐error.
//   Otherwise, hide the adjust form and show the confirmation prompt.
// =============================
function showConfirmPopup() {
  // If zero or invalid, prevent submission and show an error
  if (takeOutQty <= 0) {
    showJSError('Vui lòng nhập số lượng > 0');
    return;
  }

  // Build confirmation text
  const msg = `Bạn có chắc chắn muốn lấy ${takeOutQty.toFixed(2)} ${currentUnit} ${currentIngrName} không?`;
  document.getElementById('confirm-message').innerText = msg;

  // Copy over hidden fields
  document.getElementById('confirm-ingredient-id').value = document.getElementById('form-ingredient-id').value;
  document.getElementById('confirm-take-out').value      = document.getElementById('form-take-out').value;

  // Show confirmation, hide adjust form
  document.getElementById('adjust-form').style.display   = 'none';
  document.getElementById('confirm-popup').style.display = 'block';
}


// =============================
// hideConfirmPopup()
//   Called when user clicks “Hủy” in the confirmation block.
//   Switches back to showing the adjust form.
// =============================
function hideConfirmPopup() {
  document.getElementById('confirm-popup').style.display = 'none';
  document.getElementById('adjust-form').style.display   = 'block';
}


// =============================
// closeOverlay()
//   Close the entire overlay (called by top‐level “Hủy” button).
// =============================
function closeOverlay() {
  document.getElementById('adjust-overlay').style.display = 'none';
  hideConfirmPopup();
}


// =============================
// showJSError(msg)
//   Show a small red banner at the top for 3 seconds.
// =============================
function showJSError(msg) {
  const errDiv = document.getElementById('jsError');
  errDiv.innerText = msg;
  errDiv.style.display = 'block';

  setTimeout(() => {
    errDiv.style.display = 'none';
  }, 3000);
}


// =============================
// Notification on DOMContentLoaded (unchanged)
// =============================
window.addEventListener('DOMContentLoaded', () => {
  const takenLabel = <?= $taken_label !== '' ? json_encode($taken_label) : 'null' ?>;
  if (takenLabel) {
    const popup = document.getElementById('notification');
    popup.innerText = `Đã lấy ${takenLabel}`;
    popup.style.display = 'block';
    const bell = document.getElementById('bell-sound');
    bell.currentTime = 0;
    bell.play();
    setTimeout(() => {
      popup.style.display = 'none';
    }, 3000);
  }
});

  </script>
</body>
</html>
