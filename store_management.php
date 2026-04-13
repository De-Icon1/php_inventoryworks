<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogins.php');
check_login();

$err = $success = '';

if(isset($_POST['update_stock'])){
    $item = (int)$_POST['item_id'];
    $qty = (float)$_POST['quantity'];
    $reference = trim($_POST['reference']);
    $note = trim($_POST['note']);
    $by = $_SESSION['doc_number'];

    // insert into stock_entries as qty_in
    $stmt = $mysqli->prepare("INSERT INTO stock_entries (item_id, qty_in, reference, note, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("idsss", $item, $qty, $reference, $note, $by);
    if($stmt->execute()){
        // update balance (upsert)
        $stmt2 = $mysqli->prepare("INSERT INTO stock_balance (item_id, quantity) VALUES (?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
        $stmt2->bind_param("id", $item, $qty);
        $stmt2->execute();

        // reset notified flag if above threshold
        $stmt3 = $mysqli->prepare("SELECT threshold_qty FROM inventory_thresholds WHERE item_id = ?");
        $stmt3->bind_param("i", $item);
        $stmt3->execute();
        $stmt3->bind_result($th);
        if($stmt3->fetch() && $th !== null){
            // fetch current qty
            $stmt3->close();
            $stmt4 = $mysqli->prepare("SELECT quantity FROM stock_balance WHERE item_id = ?");
            $stmt4->bind_param("i", $item);
            $stmt4->execute();
            $stmt4->bind_result($cur_qty);
            if($stmt4->fetch()){
                if($cur_qty > $th){
                    $upd = $mysqli->prepare("UPDATE inventory_thresholds SET notified = 0 WHERE item_id = ?");
                    $upd->bind_param("i", $item);
                    $upd->execute();
                }
            }
            $stmt4->close();
        } else {
            $stmt3->close();
        }

        $success = "Stock updated.";
    } else {
        $err = "Error: ".$mysqli->error;
    }
    $stmt->close();
}
?>
<?php include("assets/inc/head.php"); ?>
<body>
<?php include("assets/inc/nav.php"); ?>
<?php include("assets/inc/sidebar_admin.php"); ?>

<div class="container mt-4">
  <h3>Manage Stock</h3>
  <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
  <?php if($err) echo "<div class='alert alert-danger'>$err</div>"; ?>

  <div class="card-box">
    <form method="POST">
      <div class="form-row">
        <div class="form-group col-12 col-md-6">
          <label>Item</label>
          <select name="item_id" class="form-control" required>
            <?php
            $res = $mysqli->query("SELECT item_id, item_name FROM items ORDER BY item_name");
            while($r = $res->fetch_assoc()){
              echo "<option value='{$r['item_id']}'>".htmlentities($r['item_name'])."</option>";
            }
            ?>
          </select>
        </div>
        <div class="form-group col-12 col-md-3">
          <label>Quantity (in)</label>
          <input type="number" step="0.01" name="quantity" class="form-control" required>
        </div>
        <div class="form-group col-12 col-md-3">
          <label>Reference</label>
          <input name="reference" class="form-control" placeholder="Invoice/GRN">
        </div>
      </div>

      <div class="form-group">
        <label>Note</label>
        <textarea name="note" class="form-control"></textarea>
      </div>

      <button class="btn btn-success" name="update_stock">Add Stock</button>
    </form>
  </div>

  <div class="card-box mt-3">
    <h5>Current Balances</h5>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead><tr><th>Item</th><th>Category</th><th>Quantity</th><th>Last Updated</th></tr></thead>
        <tbody>
          <?php
          $sql = "SELECT sb.quantity, sb.last_updated, it.item_name, c.name AS category FROM stock_balance sb JOIN items it ON sb.item_id = it.item_id LEFT JOIN categories c ON it.category_id = c.category_id ORDER BY it.item_name";
          $res = $mysqli->query($sql);
          while($row = $res->fetch_assoc()){
            echo "<tr>";
            echo "<td>".htmlentities($row['item_name'])."</td>";
            echo "<td>".htmlentities($row['category'])."</td>";
            echo "<td>".htmlentities($row['quantity'])."</td>";
            echo "<td>".htmlentities($row['last_updated'])."</td>";
            echo "</tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
</body>
</html>