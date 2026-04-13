<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogins.php');
check_login();

$err = $success = '';
if(isset($_POST['assign'])){
    $item = (int)$_POST['item_id']; // tyre item_id
    $vehicle = (int)$_POST['vehicle_id'];
    $quantity = (int)$_POST['quantity'];
    $notes = trim($_POST['notes']);
    $by = $_SESSION['username'] ?? 'Unknown';

    // Start transaction
    $mysqli->begin_transaction();
    
    try {
        // Check if sufficient stock exists
        $check = $mysqli->prepare("SELECT quantity FROM stock_balance WHERE item_id = ?");
        $check->bind_param("i", $item);
        $check->execute();
        $result = $check->get_result();
        $stock = $result->fetch_assoc();
        $check->close();
        
        if(!$stock || $stock['quantity'] < $quantity){
            throw new Exception("Insufficient stock available.");
        }
        
        // Insert tyre assignment
        $stmt = $mysqli->prepare("INSERT INTO tyre_assignment (item_id, quantity, vehicle_id, assigned_by, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $item, $quantity, $vehicle, $by, $notes);
        $stmt->execute();
        $stmt->close();
        
        // Deduct from stock_balance
        $update = $mysqli->prepare("UPDATE stock_balance SET quantity = quantity - ? WHERE item_id = ?");
        $update->bind_param("ii", $quantity, $item);
        $update->execute();
        $update->close();
        
        // Record in stock_issues
        $issue = $mysqli->prepare("INSERT INTO stock_issues (item_id, quantity, issued_to, purpose, issued_by) VALUES (?, ?, ?, ?, ?)");
        $vehicle_info = "Vehicle Assignment";
        $purpose = "Assigned to vehicle (Tyre Assignment)";
        $issue->bind_param("iisss", $item, $quantity, $vehicle_info, $purpose, $by);
        $issue->execute();
        $issue->close();
        
        // Record in stock_entries
        $entry = $mysqli->prepare("INSERT INTO stock_entries (item_id, entry_type, quantity, notes, user_id) VALUES (?, 'out', ?, ?, ?)");
        $user_id = $_SESSION['user_id'] ?? 0;
        $entry->bind_param("iisi", $item, $quantity, $notes, $user_id);
        $entry->execute();
        $entry->close();
        
        $mysqli->commit();
        $success = "Tyre assigned and stock updated.";
    } catch(Exception $e) {
        $mysqli->rollback();
        $err = "Error: " . $e->getMessage();
    }
}
?>
<?php include("assets/inc/head.php"); ?>
<body>
<?php include("assets/inc/nav.php"); ?>
<?php include("assets/inc/sidebar_admin.php"); ?>

<div class="content-page">
<div class="content container">
  <h3>Assign Tyre to Vehicle</h3>
  <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
  <?php if($err) echo "<div class='alert alert-danger'>$err</div>"; ?>

  <div class="card-box">
    <form method="POST">
      <div class="form-row">
        <div class="form-group col-12 col-md-5">
          <label>Tyre (Item)</label>
          <select name="item_id" class="form-control">
            <?php
            $res = $mysqli->query("SELECT item_id, item_name FROM items WHERE category_id = (SELECT category_id FROM categories WHERE name='Tyres' LIMIT 1)");
            while($r = $res->fetch_assoc()){
              echo "<option value='{$r['item_id']}'>".htmlentities($r['item_name'])."</option>";
            }
            ?>
          </select>
        </div>
        <div class="form-group col-12 col-md-4">
          <label>Vehicle</label>
          <select name="vehicle_id" class="form-control">
            <?php
            $res = $mysqli->query("SELECT vehicle_id, vehicle_number FROM vehicles ORDER BY vehicle_number");
            while($r = $res->fetch_assoc()){
              echo "<option value='{$r['vehicle_id']}'>".htmlentities($r['vehicle_number'])."</option>";
            }
            ?>
          </select>
        </div>
        <div class="form-group col-12 col-md-2">
          <label>Quantity</label>
          <input type="number" name="quantity" class="form-control" value="1" min="1" required>
        </div>
        <div class="form-group col-12 col-md-1">
          <label>&nbsp;</label><br>
          <button class="btn btn-info" name="assign">Assign</button>
        </div>
      </div>
      <div class="form-group">
        <label>Notes</label>
        <textarea name="notes" class="form-control"></textarea>
      </div>
    </form>
  </div>

  <div class="card-box mt-3">
    <h5>Assignments</h5>
    <div class="table-responsive">
      <table class="table table-striped">
      <thead><tr><th>When</th><th>Tyre</th><th>Quantity</th><th>Vehicle</th><th>By</th><th>Notes</th></tr></thead>
      <tbody>
      <?php
      $sql = "SELECT ta.*, it.item_name, v.vehicle_number FROM tyre_assignment ta JOIN items it ON ta.item_id = it.item_id JOIN vehicles v ON ta.vehicle_id = v.vehicle_id ORDER BY ta.assigned_at DESC";
      $res = $mysqli->query($sql);
      while($r = $res->fetch_assoc()){
        echo "<tr><td>{$r['assigned_at']}</td><td>".htmlentities($r['item_name'])."</td><td>{$r['quantity']}</td><td>".htmlentities($r['vehicle_number'])."</td><td>".htmlentities($r['assigned_by'])."</td><td>".htmlentities($r['notes'])."</td></tr>";
      }
      ?>
      </tbody>
      </table>
    </div>
  </div>
</div>
</div>

<?php include("assets/inc/footer.php"); ?>

</body>
</html>