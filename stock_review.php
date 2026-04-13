<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogins.php');
check_login();

$err = $success = '';

// Get summary statistics
$total_items = 0;
$total_stock = 0;
$low_stock_count = 0;
$critical_stock_count = 0;

// Total items count
$res = $mysqli->query("SELECT COUNT(*) AS cnt FROM items");
if($res) {
    $r = $res->fetch_assoc();
    $total_items = $r['cnt'];
}

// Total stock quantity
$res = $mysqli->query("SELECT COALESCE(SUM(quantity), 0) AS total FROM stock_balance");
if($res) {
    $r = $res->fetch_assoc();
    $total_stock = $r['total'];
}

// Low stock items (less than 20 units)
$res = $mysqli->query("SELECT COUNT(*) AS cnt FROM stock_balance WHERE quantity < 20 AND quantity > 0");
if($res) {
    $r = $res->fetch_assoc();
    $low_stock_count = $r['cnt'];
}

// Critical stock items (0 or less)
$res = $mysqli->query("SELECT COUNT(*) AS cnt FROM stock_balance WHERE quantity <= 0");
if($res) {
    $r = $res->fetch_assoc();
    $critical_stock_count = $r['cnt'];
}
?>
<?php include("assets/inc/head.php"); ?>
<body>
<?php include("assets/inc/nav.php"); ?>
<?php include("assets/inc/sidebar_admin.php"); ?>

<div class="content-page">
<div class="content container">
  <h3>Stock Review Dashboard</h3>
  <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
  <?php if($err) echo "<div class='alert alert-danger'>$err</div>"; ?>

  <!-- Summary Cards -->
  <div class="row mt-4">
    <div class="col-12 col-sm-6 col-md-3 mb-3">
      <div class="card">
        <div class="card-body">
          <h6 class="card-title">Total Items</h6>
          <h3 class="text-primary"><?php echo $total_items; ?></h3>
          <small class="text-muted">Active inventory items</small>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-md-3 mb-3">
      <div class="card">
        <div class="card-body">
          <h6 class="card-title">Total Stock</h6>
          <h3 class="text-success"><?php echo number_format($total_stock, 0); ?></h3>
          <small class="text-muted">Units in stock</small>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-md-3 mb-3">
      <div class="card">
        <div class="card-body">
          <h6 class="card-title">Low Stock</h6>
          <h3 class="text-warning"><?php echo $low_stock_count; ?></h3>
          <small class="text-muted">Items below 20 units</small>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-md-3 mb-3">
      <div class="card">
        <div class="card-body">
          <h6 class="card-title">Critical Stock</h6>
          <h3 class="text-danger"><?php echo $critical_stock_count; ?></h3>
          <small class="text-muted">Out of stock items</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Low Stock Alert Section -->
  <div class="card-box mt-4">
    <h5>Low Stock Alert Items</h5>
    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead>
          <tr>
            <th>Item Name</th>
            <th>Current Stock</th>
            <th>Unit</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $sql = "SELECT sb.item_id, sb.quantity, i.item_name, u.unit_name 
                FROM stock_balance sb 
                JOIN items i ON sb.item_id = i.item_id 
                JOIN units u ON i.unit_id = u.unit_id 
                WHERE sb.quantity <= 20 
                ORDER BY sb.quantity ASC";
        $res = $mysqli->query($sql);
        if($res && $res->num_rows > 0) {
            while($r = $res->fetch_assoc()) {
                $status = $r['quantity'] <= 0 ? '<span class="badge badge-danger">Out of Stock</span>' : '<span class="badge badge-warning">Low Stock</span>';
                echo "<tr>";
                echo "<td>".htmlentities($r['item_name'])."</td>";
                echo "<td>".number_format($r['quantity'], 2)."</td>";
                echo "<td>".htmlentities($r['unit_name'])."</td>";
                echo "<td>".$status."</td>";
                echo "<td><a href='upload_stock.php' class='btn btn-sm btn-primary'>Replenish</a></td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5' class='text-center text-muted'>No low stock items</td></tr>";
        }
        ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Stock Movement Section -->
  <div class="card-box mt-4">
    <h5>Recent Stock Movements</h5>
    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead>
          <tr>
            <th>Date</th>
            <th>Item</th>
            <th>Type</th>
            <th>Quantity</th>
            <th>Reference</th>
            <th>By</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $sql = "SELECT se.*, i.item_name 
                FROM stock_entries se 
                JOIN items i ON se.item_id = i.item_id 
                ORDER BY se.created_at DESC 
                LIMIT 50";
        $res = $mysqli->query($sql);
        if($res && $res->num_rows > 0) {
            while($r = $res->fetch_assoc()) {
                $type = '';
                if($r['qty_in'] > 0) {
                    $type = '<span class="badge badge-success">In</span>';
                    $qty = $r['qty_in'];
                } else {
                    $type = '<span class="badge badge-danger">Out</span>';
                    $qty = $r['qty_out'];
                }
                echo "<tr>";
                echo "<td>".htmlentities($r['created_at'])."</td>";
                echo "<td>".htmlentities($r['item_name'])."</td>";
                echo "<td>".$type."</td>";
                echo "<td>".number_format($qty, 0)."</td>";
                echo "<td>".htmlentities($r['reference'])."</td>";
                echo "<td>".htmlentities($r['created_by'])."</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='6' class='text-center text-muted'>No stock movements</td></tr>";
        }
        ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Stock Issues Section -->
  <div class="card-box mt-4">
    <h5>Recent Stock Issues</h5>
    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead>
          <tr>
            <th>Date</th>
            <th>Item</th>
            <th>Unit Issued To</th>
            <th>Quantity</th>
            <th>Purpose</th>
            <th>Issued By</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $sql = "SELECT si.*, i.item_name, u.unit_name 
                FROM stock_issues si 
                JOIN items i ON si.item_id = i.item_id 
                JOIN units u ON si.unit_id = u.unit_id 
                ORDER BY si.issued_at DESC 
                LIMIT 30";
        $res = $mysqli->query($sql);
        if($res && $res->num_rows > 0) {
            while($r = $res->fetch_assoc()) {
                echo "<tr>";
                echo "<td>".htmlentities($r['issued_at'])."</td>";
                echo "<td>".htmlentities($r['item_name'])."</td>";
                echo "<td>".htmlentities($r['unit_name'])."</td>";
                echo "<td>".number_format($r['quantity'], 0)."</td>";
                echo "<td>".htmlentities($r['purpose'])."</td>";
                echo "<td>".htmlentities($r['issued_by'])."</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='6' class='text-center text-muted'>No stock issues</td></tr>";
        }
        ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Stock Balance by Item -->
  <div class="card-box mt-4">
    <h5>Current Stock Balance</h5>
    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead>
          <tr>
            <th>Item Name</th>
            <th>Quantity</th>
            <th>Unit</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $sql = "SELECT sb.*, i.item_name, u.unit_name 
                FROM stock_balance sb 
                JOIN items i ON sb.item_id = i.item_id 
                JOIN units u ON i.unit_id = u.unit_id 
                ORDER BY i.item_name ASC";
        $res = $mysqli->query($sql);
        if($res && $res->num_rows > 0) {
            while($r = $res->fetch_assoc()) {
                $status_class = 'success';
                $status_text = 'In Stock';
                if($r['quantity'] <= 0) {
                    $status_class = 'danger';
                    $status_text = 'Out of Stock';
                } else if($r['quantity'] < 20) {
                    $status_class = 'warning';
                    $status_text = 'Low Stock';
                }
                echo "<tr>";
                echo "<td>".htmlentities($r['item_name'])."</td>";
                echo "<td><strong>".number_format($r['quantity'], 0)."</strong></td>";
                echo "<td>".htmlentities($r['unit_name'])."</td>";
                echo "<td><span class='badge badge-".$status_class."'>".$status_text."</span></td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4' class='text-center text-muted'>No items in stock</td></tr>";
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
