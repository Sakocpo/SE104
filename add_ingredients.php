<?php
session_start();
require 'config.php';
if (!$_SESSION['user']||$_SESSION['user']['role']!=='admin') {
  header('Location:index.php'); exit;
}

$cat = intval($_GET['category'] ?? 0);
// fetch categories + units
$cats  = $connection->query("SELECT * FROM ingredient_categories")->fetch_all(MYSQLI_ASSOC);
$units = $connection->query("SELECT * FROM unit_options")->fetch_all(MYSQLI_ASSOC);

// handle submission
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $name = trim($_POST['name']);
  $category = intval($_POST['category_id']);
  $unit     = intval($_POST['unit_id']);
  $qty      = floatval($_POST['quantity'] ?? 0);
  $imgpath  = null;
  if (!empty($_FILES['image']['name'])) {
    $imgpath = 'uploads/'.basename($_FILES['image']['name']);
    move_uploaded_file($_FILES['image']['tmp_name'],$imgpath);
  }
  $stmt = $connection->prepare("
    INSERT INTO ingredients(name,category_id,unit_id,quantity,image)
    VALUES(?,?,?,?,?)
  ");
  $stmt->bind_param("siiis",$name,$category,$unit,$qty,$imgpath);
  $stmt->execute();
  header("Location: ingredients_management.php?category=$category");
  exit;
}
?>
<!doctype html>
<html><head>
  <meta charset="utf-8"><title>Add Ingredient</title>
  <link rel="stylesheet" href="style.css">
</head><body>
  <div class="forms-container">
    <h3>Add Ingredient</h3>
    <form method="POST" enctype="multipart/form-data">
      <label>Name:</label>
      <input name="name" required>

      <label>Category:</label>
      <select name="category_id">
        <?php foreach($cats as $c): ?>
          <option value="<?=$c['id']?>" <?= $c['id']==$cat?'selected':''?>>
            <?=htmlspecialchars($c['name'])?>
          </option>
        <?php endforeach;?>
      </select>

      <label>Unit:</label>
      <div style="display:flex;gap:8px;align-items:center">
        <select id="unit-select" name="unit_id">
          <?php foreach($units as $u): ?>
            <option value="<?=$u['id']?>"><?=htmlspecialchars($u['name'])?></option>
          <?php endforeach;?>
        </select>
        <button type="button" id="add-unit">+ Add</button>
      </div>

      <label>Quantity:</label>
      <div style="display:flex;align-items:center;gap:8px">
        <button type="button" onclick="q(-1)">−</button>
        <input id="qty" name="quantity" type="number" step="0.01" value="0">
        <button type="button" onclick="q(+1)">+</button>
      </div>

      <label>Image:</label>
      <input type="file" name="image" accept="image/*">

      <button type="submit">Save</button>
      <a href="ingredients_management.php?category=<?=$cat?>">
        <button type="button">Cancel</button>
      </a>
    </form>
  </div>

  <script>
    function q(d){ 
      let i = document.getElementById('qty'),
          v = parseFloat(i.value)||0;
      i.value = Math.max(0, v + d);
    }

    document.getElementById('add-unit')
      .addEventListener('click',()=> {
        let name = prompt("New unit name?");
        if (!name) return;
        fetch('add_unit_option.php',{
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body:JSON.stringify({name})
        })
        .then(r=>r.json())
        .then(j=>{
          if(j.id){
            let sel = document.getElementById('unit-select'),
                opt = new Option(j.name,j.id);
            sel.add(opt);
            sel.value = j.id;
          } else {
            alert("Error: "+j.error);
          }
        });
      });
  </script>
</body></html>
