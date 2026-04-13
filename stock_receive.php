<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogins.php');
check_login();

$err = $success = '';

if(isset($_POST['receive'])){
    $item = (int)$_POST['item_id'];
    $qty = (float)$_POST['quantity'];
    $supplier = trim($_POST['supplier']);
    $reference = trim($_POST['reference']);
    $cost_per_unit = (float)$_POST['cost_per_unit'];
    $received_by = $_SESSION['doc_number'];

    // Validation
    if($qty <= 0){
        $err = "Quantity must be greater than zero.";
    } else if(empty($supplier)){
        $err = "Supplier name is required.";
    } else {
        // Start transaction
        $mysqli->begin_transaction();

        try {
            // Insert stock entry (qty_in)
            $stmt = $mysqli->prepare("INSERT INTO stock_entries (item_id, qty_in, reference, note, created_by) VALUES (?, ?, ?, ?, ?)");
            $note = "Received from " . $supplier;
            $stmt->bind_param("idsss", $item, $qty, $reference, $note, $received_by);
            $stmt->execute();
            $stmt->close();

            // Update or insert stock balance
            $stmt = $mysqli->prepare("
                INSERT INTO stock_balance (item_id, quantity) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
            ");
            $stmt->bind_param("id", $item, $qty);
            $stmt->execute();
            $stmt->close();

            // Optional: Record cost in a separate table if you track costs
            // You may need to create a stock_costs table if it doesn't exist
            $stmt = $mysqli->prepare("
                INSERT INTO stock_costs (item_id, qty, cost_per_unit, total_cost, supplier, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            if($stmt){
                $total_cost = $qty * $cost_per_unit;
                $stmt->bind_param("iddsss", $item, $qty, $cost_per_unit, $total_cost, $supplier, $received_by);
                $stmt->execute();
                $stmt->close();
            }

            $mysqli->commit();
            $success = "Stock received successfully. Quantity: " . number_format($qty, 0) . " units";
        } catch(Exception $e){
            $mysqli->rollback();
            $err = "Error receiving stock: " . $e->getMessage();
        }
    }
}
?>
<?php include("assets/inc/head.php"); ?>
<body>
<?php include("assets/inc/nav.php"); ?>
<?php include("assets/inc/sidebar_admin.php"); ?>

<div class="content-page">
<div class="content container">
  <h3>Receive New Stock</h3>
  <?php if($success) echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>".$success."<button type='button' class='close' data-dismiss='alert'><span>&times;</span></button></div>"; ?>
  <?php if($err) echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>".$err."<button type='button' class='close' data-dismiss='alert'><span>&times;</span></button></div>"; ?>

  <div class="card-box">
    <form method="POST" onsubmit="return validateForm()">
      <div class="form-row">
        <div class="form-group col-12 col-md-6">
          <label>Item <span class="text-danger">*</span></label>
          <select name="item_id" id="item_id" class="form-control" required onchange="getItemDetails()">
            <option value="">-- Select Item --</option>
            <?php
            $res = $mysqli->query("SELECT item_id, item_name FROM items ORDER BY item_name");
            while($r = $res->fetch_assoc()){
              echo "<option value='{$r['item_id']}'>".htmlentities($r['item_name'])."</option>";
            }
            ?>
          </select>
        </div>

        <div class="form-group col-12 col-md-6">
          <label>Current Stock Level</label>
          <input type="text" id="current_stock" class="form-control" readonly placeholder="Select item to view">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-12 col-md-3">
          <label>Quantity <span class="text-danger">*</span></label>
          <input type="number" step="0.01" name="quantity" id="quantity" class="form-control" required placeholder="0.00" min="0.01">
        </div>

        <div class="form-group col-12 col-md-3">
          <label>Cost per Unit</label>
          <input type="number" step="0.01" name="cost_per_unit" id="cost_per_unit" class="form-control" placeholder="0.00" value="0">
        </div>

        <div class="form-group col-12 col-md-3">
          <label>Total Cost</label>
          <input type="text" id="total_cost" class="form-control" readonly placeholder="0.00">
        </div>

        <div class="form-group col-12 col-md-3">
          <label>&nbsp;</label>
          <button type="button" class="btn btn-secondary btn-block" onclick="calculateTotalCost()">Calculate</button>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-12 col-md-6">
          <label>Supplier Name <span class="text-danger">*</span></label>
          <input type="text" name="supplier" class="form-control" required placeholder="Supplier company name">
        </div>

        <div class="form-group col-12 col-md-6">
          <label>Reference / PO Number</label>
          <input type="text" name="reference" class="form-control" placeholder="Purchase Order or Invoice number">
        </div>
      </div>

      <div class="form-group">
        <button type="submit" name="receive" class="btn btn-success"><i class="fe-check"></i> Receive Stock</button>
        <button type="reset" class="btn btn-light"><i class="fe-x"></i> Clear</button>
      </div>
    </form>
  </div>

  <!-- Recent Stock Received -->
  <div class="card-box mt-4">
    <h5>Recent Stock Received</h5>
    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead>
          <tr>
            <th>Date</th>
            <th>Item</th>
            <th>Quantity</th>
            <th>Supplier</th>
            <th>Reference</th>
            <th>Received By</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $sql = "SELECT se.*, i.item_name 
                FROM stock_entries se 
                JOIN items i ON se.item_id = i.item_id 
                WHERE se.qty_in > 0
                ORDER BY se.created_at DESC 
                LIMIT 30";
        $res = $mysqli->query($sql);
        if($res && $res->num_rows > 0) {
            while($r = $res->fetch_assoc()) {
                echo "<tr>";
                echo "<td>".htmlentities($r['created_at'])."</td>";
                echo "<td>".htmlentities($r['item_name'])."</td>";
                echo "<td><strong>".number_format($r['qty_in'], 0)."</strong></td>";
                echo "<td>".htmlentities($r['note'])."</td>";
                echo "<td>".htmlentities($r['reference'])."</td>";
                echo "<td>".htmlentities($r['created_by'])."</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='6' class='text-center text-muted'>No stock received yet</td></tr>";
        }
        ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
</div>

<?php include("assets/inc/footer.php"); ?>

<script>
// Ensure functions are available on the global window and DOM elements exist
window.getItemDetails = function() {
  var el = document.getElementById('item_id');
  var target = document.getElementById('current_stock');
  if(!target) return;
  var item_id = el ? el.value : '';
  if(item_id) {
    fetch('get_item_stock.php?item_id=' + encodeURIComponent(item_id))
      .then(response => response.json())
      .then(data => {
        if(data && data.success) {
          target.value = data.current_stock;
        } else {
          target.value = 'Error loading';
        }
      })
      .catch(error => {
        console.error('Error:', error);
        target.value = 'Error loading';
      });
  } else {
    target.value = '';
  }
};

window.calculateTotalCost = function() {
  var qEl = document.getElementById('quantity');
  var cEl = document.getElementById('cost_per_unit');
  var tEl = document.getElementById('total_cost');
  if(!tEl) return;
  var quantity = parseFloat(qEl ? qEl.value : 0) || 0;
  var cost_per_unit = parseFloat(cEl ? cEl.value : 0) || 0;
  var total = (quantity * cost_per_unit).toFixed(2);
  tEl.value = total;
};

function validateForm() {
  var itemEl = document.getElementById('item_id');
  var qEl = document.getElementById('quantity');
  var item_id = itemEl ? itemEl.value : '';
  var quantity = qEl ? parseFloat(qEl.value) : NaN;
    
  if(!item_id) {
    alert('Please select an item');
    return false;
  }
    
  if(!quantity || quantity <= 0) {
    alert('Quantity must be greater than zero');
    return false;
  }
    
  return true;
}

// Attach listeners after DOM is ready
document.addEventListener('DOMContentLoaded', function(){
  var q = document.getElementById('quantity');
  var c = document.getElementById('cost_per_unit');
  if(q) q.addEventListener('change', window.calculateTotalCost);
  if(c) c.addEventListener('change', window.calculateTotalCost);

  // Also attach change handler to the select to be safe (works even if inline onchange fails)
  var itemSel = document.getElementById('item_id');
  if(itemSel) itemSel.addEventListener('change', window.getItemDetails);
});
</script>

</body>
</html>
