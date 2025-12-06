<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogins.php');
check_login();

$err = $success = '';

if(isset($_POST['issue'])){
    $item = (int)$_POST['item_id'];
    $unit = (int)$_POST['unit_id'];
    $qty = (float)$_POST['quantity'];
    $purpose = trim($_POST['purpose']);
    $by = $_SESSION['doc_number'];

    // check stock
    $stmt = $mysqli->prepare("SELECT quantity FROM stock_balance WHERE item_id = ?");
    $stmt->bind_param("i", $item);
    $stmt->execute();
    $stmt->bind_result($cur_qty);
    $stmt->fetch();
    $stmt->close();

    if($cur_qty === null) $cur_qty = 0;

    if($cur_qty < $qty){
        $err = "Not enough stock. Available: {$cur_qty}";
    } else {
        // start transaction
        $mysqli->begin_transaction();

        try {
            // record issue
            $ins = $mysqli->prepare("INSERT INTO stock_issues (item_id, unit_id, quantity, issued_by, purpose) VALUES (?, ?, ?, ?, ?)");
            $ins->bind_param("iidss", $item, $unit, $qty, $by, $purpose);
            $ins->execute();
            $ins->close();

            // record stock entry qty_out
            $ent = $mysqli->prepare("INSERT INTO stock_entries (item_id, qty_out, reference, note, created_by) VALUES (?, ?, ?, ?, ?)");
            $ent->bind_param("idsss", $item, $qty, $purpose, $purpose, $by);
            $ent->execute();
            $ent->close();

            // update balance
            $upd = $mysqli->prepare("UPDATE stock_balance SET quantity = quantity - ? WHERE item_id = ?");
            $upd->bind_param("di", $qty, $item);
            $upd->execute();
            $upd->close();

            $mysqli->commit();
            $success = "Issued successfully.";
        } catch(Exception $e){
            $mysqli->rollback();
            $err = "Error issuing: " . $e->getMessage();
        }
    }
}
?>
<?php include("assets/inc/head.php"); ?>
<body>
<?php include("assets/inc/nav.php"); ?>

<div class="container mt-4">
  <h3>Issue Items</h3>
  <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
  <?php if($err) echo "<div class='alert alert-danger'>$err</div>"; ?>

  <div class="card-box">
    <form method="POST">
      <div class="form-row">
        <div class="form-group col-md-5">
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

        <div class="form-group col-md-4">
          <label>Issue To (Unit)</label>
          <select name="unit_id" class="form-control" required>
            <?php
            $res = $mysqli->query("SELECT unit_id, unit_name FROM units ORDER BY unit_name");
            while($r = $res->fetch_assoc()){
              echo "<option value='{$r['unit_id']}'>".htmlentities($r['unit_name'])."</option>";
            }
            ?>
          </select>
        </div>

        <div class="form-group col-md-3">
          <label>Quantity</label>
          <input type="number" step="0.01" name="quantity" class="form-control" required>
        </div>
      </div>

      <div class="form-group">
        <label>Purpose / Reference</label>
        <input name="purpose" class="form-control" placeholder="Job card / purpose">
      </div>

      <button class="btn btn-warning" name="issue">Issue Item</button>
    </form>
  </div>

  <div class="card-box mt-3">
    <h5>Recent Issues</h5>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead><tr><th>Date</th><th>Item</th><th>Unit</th><th>Qty</th><th>By</th></tr></thead>
        <tbody>
        <?php
        $sql = "SELECT si.*, it.item_name, u.unit_name FROM stock_issues si JOIN items it ON si.item_id = it.item_id JOIN units u ON si.unit_id = u.unit_id ORDER BY si.issued_at DESC LIMIT 50";
        $res = $mysqli->query($sql);
        while($r = $res->fetch_assoc()){
          echo "<tr>";
          echo "<td>".htmlentities($r['issued_at'])."</td>";
          echo "<td>".htmlentities($r['item_name'])."</td>";
          echo "<td>".htmlentities($r['unit_name'])."</td>";
          echo "<td>".htmlentities($r['quantity'])."</td>";
          echo "<td>".htmlentities($r['issued_by'])."</td>";
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