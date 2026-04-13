<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogins.php');
check_login();

// Get statistics for electrical unit - only items received by electrical unit
$electrical_user = $_SESSION['username'];
$total_appliances = $mysqli->query("SELECT COUNT(DISTINCT item_id) as count FROM stock_entries WHERE created_by = '$electrical_user' AND qty_in > 0")->fetch_assoc()['count'];
$total_stock = $mysqli->query("SELECT COALESCE(SUM(sb.quantity), 0) as count FROM stock_balance sb WHERE sb.item_id IN (SELECT DISTINCT item_id FROM stock_entries WHERE created_by = '$electrical_user' AND qty_in > 0)")->fetch_assoc()['count'];
$total_received = $mysqli->query("SELECT COALESCE(SUM(qty_in), 0) as count FROM stock_entries WHERE created_by = '$electrical_user' AND qty_in > 0")->fetch_assoc()['count'];
$total_issued = $mysqli->query("SELECT COALESCE(SUM(si.quantity), 0) as count FROM stock_issues si WHERE si.item_id IN (SELECT DISTINCT item_id FROM stock_entries WHERE created_by = '$electrical_user' AND qty_in > 0)")->fetch_assoc()['count'];
?>
<?php include("assets/inc/head.php"); ?>
<body>
<?php include("assets/inc/nav.php"); ?>
<?php include("assets/inc/sidebar_electrical.php"); ?>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <h4 class="page-title">Electrical Unit Dashboard</h4>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card widget-flat">
                        <div class="card-body">
                            <h5 class="text-muted font-weight-normal mt-0">Total Items</h5>
                            <h3 class="mt-3 mb-3"><?php echo (int)$total_appliances; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card widget-flat">
                        <div class="card-body">
                            <h5 class="text-muted font-weight-normal mt-0">Current Stock</h5>
                            <h3 class="mt-3 mb-3"><?php echo (int)$total_stock; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card widget-flat">
                        <div class="card-body">
                            <h5 class="text-muted font-weight-normal mt-0">Total Received</h5>
                            <h3 class="mt-3 mb-3"><?php echo (int)$total_received; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card widget-flat">
                        <div class="card-body">
                            <h5 class="text-muted font-weight-normal mt-0">Total Issued</h5>
                            <h3 class="mt-3 mb-3"><?php echo (int)$total_issued; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Receive and Dispense Section -->
            <div class="row">
                <div class="col-12 col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title mb-3">Receive Items from Store</h4>
                            
                            <?php
                            if(isset($_POST['receive'])){
                                $item = (int)$_POST['item_id'];
                                $quantity = (int)$_POST['quantity'];
                                $supplier = trim($_POST['supplier']);
                                $notes = trim($_POST['notes']);
                                $received_by = $_SESSION['username'] ?? 'Unknown';

                                $mysqli->begin_transaction();
                                
                                try {
                                    // Check if item exists in stock_balance, if not create it
                                    $check = $mysqli->query("SELECT balance_id FROM stock_balance WHERE item_id = $item");
                                    if($check->num_rows == 0){
                                        $mysqli->query("INSERT INTO stock_balance (item_id, quantity) VALUES ($item, 0)");
                                    }
                                    
                                    // Record stock entry (received)
                                    $stmt = $mysqli->prepare("INSERT INTO stock_entries (item_id, qty_in, reference, note, created_by) VALUES (?, ?, ?, ?, ?)");
                                    $reference = "Received from Store - $supplier";
                                    $stmt->bind_param("idsss", $item, $quantity, $reference, $notes, $received_by);
                                    $stmt->execute();
                                    $stmt->close();
                                    
                                    // Update stock balance
                                    $stmt = $mysqli->prepare("UPDATE stock_balance SET quantity = quantity + ? WHERE item_id = ?");
                                    $stmt->bind_param("ii", $quantity, $item);
                                    $stmt->execute();
                                    $stmt->close();
                                    
                                    $mysqli->commit();
                                    echo "<div class='alert alert-success'>Item received successfully!</div>";
                                } catch(Exception $e) {
                                    $mysqli->rollback();
                                    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
                                }
                            }
                            ?>

                            <form method="POST" class="needs-validation">
                                <div class="form-row">
                                    <div class="form-group col-12 col-md-6">
                                        <label>Select Item *</label>
                                        <select name="item_id" class="form-control" required>
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
                                        <label>Quantity *</label>
                                        <input type="number" name="quantity" class="form-control" min="1" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Supplier/Source *</label>
                                    <input type="text" name="supplier" class="form-control" placeholder="e.g., Main Store" required>
                                </div>

                                <div class="form-group">
                                    <label>Notes</label>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="Add any notes about this receipt..."></textarea>
                                </div>

                                <button type="submit" name="receive" class="btn btn-success btn-block">
                                    <i class="mdi mdi-check"></i> Receive Item
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title mb-3">Dispense to Offices</h4>
                            
                            <?php
                            if(isset($_POST['dispense'])){
                                $item = (int)$_POST['dispense_item_id'];
                                $quantity = (int)$_POST['dispense_quantity'];
                                $office = trim($_POST['office']);
                                $purpose = trim($_POST['purpose']);
                                $dispensed_by = $_SESSION['username'] ?? 'Unknown';

                                // Check available stock
                                $check_stock = $mysqli->query("SELECT COALESCE(quantity, 0) as stock FROM stock_balance WHERE item_id = $item");
                                $stock_row = $check_stock->fetch_assoc();
                                $available_stock = $stock_row['stock'] ?? 0;

                                if($quantity > $available_stock){
                                    echo "<div class='alert alert-danger'>Insufficient stock! Available: {$available_stock}</div>";
                                } else {
                                    $mysqli->begin_transaction();
                                    
                                    try {
                                        // Get unit_id for Electrical Unit
                                        $unit_result = $mysqli->query("SELECT unit_id FROM units WHERE unit_name = 'Electrical Unit'");
                                        $unit_id = 1; // Default
                                        if($unit_result->num_rows > 0){
                                            $unit_id = $unit_result->fetch_assoc()['unit_id'];
                                        }
                                        
                                        // Record stock issue
                                        $stmt = $mysqli->prepare("INSERT INTO stock_issues (item_id, unit_id, quantity, purpose, issued_by, issued_at) VALUES (?, ?, ?, ?, ?, NOW())");
                                        $full_purpose = "Dispensed to: $office - $purpose";
                                        $stmt->bind_param("iiiss", $item, $unit_id, $quantity, $full_purpose, $dispensed_by);
                                        $stmt->execute();
                                        $stmt->close();
                                        
                                        // Record stock entry (outgoing)
                                        $stmt = $mysqli->prepare("INSERT INTO stock_entries (item_id, qty_out, reference, note, created_by) VALUES (?, ?, ?, ?, ?)");
                                        $reference = "Dispensed to Office";
                                        $stmt->bind_param("idsss", $item, $quantity, $reference, $full_purpose, $dispensed_by);
                                        $stmt->execute();
                                        $stmt->close();
                                        
                                        // Update stock balance
                                        $stmt = $mysqli->prepare("UPDATE stock_balance SET quantity = quantity - ? WHERE item_id = ?");
                                        $stmt->bind_param("ii", $quantity, $item);
                                        $stmt->execute();
                                        $stmt->close();
                                        
                                        $mysqli->commit();
                                        echo "<div class='alert alert-success'>Item dispensed successfully!</div>";
                                    } catch(Exception $e) {
                                        $mysqli->rollback();
                                        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
                                    }
                                }
                            }
                            ?>

                            <form method="POST" class="needs-validation" id="dispenseForm">
                                <div class="form-row">
                                    <div class="form-group col-12 col-md-6">
                                        <label>Select Item *</label>
                                        <select name="dispense_item_id" class="form-control" id="dispenseItem" required>
                                            <option value="">-- Select Item --</option>
                                            <?php
                                            $electrical_user = $_SESSION['username'];
                                            $sql = "SELECT i.item_id, i.item_name, COALESCE(sb.quantity, 0) as stock
                                                    FROM items i
                                                    INNER JOIN stock_entries se ON i.item_id = se.item_id
                                                    LEFT JOIN stock_balance sb ON i.item_id = sb.item_id
                                                    WHERE se.created_by = '$electrical_user' AND se.qty_in > 0
                                                    GROUP BY i.item_id, i.item_name, sb.quantity
                                                    HAVING stock > 0
                                                    ORDER BY i.item_name";
                                            $res = $mysqli->query($sql);
                                            while($r = $res->fetch_assoc()){
                                                $stock = (int)$r['stock'];
                                                echo "<option value='{$r['item_id']}' data-stock='{$stock}'>".htmlentities($r['item_name'])." (Stock: {$stock})</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="form-group col-12 col-md-6">
                                        <label>Quantity *</label>
                                        <input type="number" name="dispense_quantity" class="form-control" id="dispenseQty" min="1" required>
                                        <small class="text-muted" id="stockInfo"></small>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Office/Department *</label>
                                    <input type="text" name="office" class="form-control" placeholder="e.g., Registrar's Office, Dean's Office" required>
                                </div>

                                <div class="form-group">
                                    <label>Purpose *</label>
                                    <textarea name="purpose" class="form-control" rows="2" placeholder="Reason for dispensing..." required></textarea>
                                </div>

                                <button type="submit" name="dispense" class="btn btn-warning btn-block">
                                    <i class="mdi mdi-hand-right"></i> Dispense Item
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            // Show available stock when item is selected
            document.getElementById('dispenseItem').addEventListener('change', function() {
                var selectedOption = this.options[this.selectedIndex];
                var stock = selectedOption.getAttribute('data-stock');
                var stockInfo = document.getElementById('stockInfo');
                var qtyInput = document.getElementById('dispenseQty');
                
                if(stock) {
                    stockInfo.innerHTML = 'Available stock: ' + stock;
                    qtyInput.max = stock;
                } else {
                    stockInfo.innerHTML = '';
                    qtyInput.max = '';
                }
            });
            </script>

            <!-- Recent Dispensed Items -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title mb-3">Recent Dispensed Items</h4>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Item</th>
                                            <th>Quantity</th>
                                            <th>Office/Department</th>
                                            <th>Purpose</th>
                                            <th>Dispensed By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $electrical_user = $_SESSION['username'];
                                        $sql = "SELECT si.*, i.item_name 
                                                FROM stock_issues si
                                                JOIN items i ON si.item_id = i.item_id
                                                WHERE si.issued_by = '$electrical_user'
                                                ORDER BY si.issued_at DESC 
                                                LIMIT 20";
                                        $res = $mysqli->query($sql);
                                        if($res->num_rows > 0){
                                            while($r = $res->fetch_assoc()){
                                                // Extract office from purpose
                                                preg_match('/Dispensed to: (.*?) - (.*)/', $r['purpose'], $matches);
                                                $office = $matches[1] ?? 'N/A';
                                                $purpose = $matches[2] ?? $r['purpose'];
                                                
                                                echo "<tr>";
                                                echo "<td>".date('M d, Y H:i', strtotime($r['issued_at']))."</td>";
                                                echo "<td>".htmlentities($r['item_name'])."</td>";
                                                echo "<td><span class='badge badge-warning'>".(int)$r['quantity']."</span></td>";
                                                echo "<td>".htmlentities($office)."</td>";
                                                echo "<td>".htmlentities($purpose)."</td>";
                                                echo "<td>".htmlentities($r['issued_by'])."</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='6' class='text-center text-muted'>No dispensed items yet</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Receipts -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title mb-3">Recent Receipts</h4>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Item</th>
                                            <th>Quantity</th>
                                            <th>Supplier/Source</th>
                                            <th>Received By</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $electrical_user = $_SESSION['username'];
                                        $sql = "SELECT se.*, i.item_name 
                                                FROM stock_entries se 
                                                JOIN items i ON se.item_id = i.item_id 
                                                WHERE se.created_by = '$electrical_user' AND se.qty_in > 0
                                                ORDER BY se.created_at DESC 
                                                LIMIT 20";
                                        $res = $mysqli->query($sql);
                                        if($res->num_rows > 0){
                                            while($r = $res->fetch_assoc()){
                                                echo "<tr>";
                                                echo "<td>".date('M d, Y H:i', strtotime($r['created_at']))."</td>";
                                                echo "<td>".htmlentities($r['item_name'])."</td>";
                                                echo "<td><span class='badge badge-success'>".(int)$r['qty_in']."</span></td>";
                                                echo "<td>".htmlentities($r['reference'])."</td>";
                                                echo "<td>".htmlentities($r['created_by'])."</td>";
                                                echo "<td>".htmlentities($r['note'])."</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='6' class='text-center text-muted'>No receipts yet</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Stock -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title mb-3">Current Stock</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Item Name</th>
                                            <th>Unit</th>
                                            <th>Current Stock</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $electrical_user = $_SESSION['username'];
                                        $sql = "SELECT i.item_id, i.item_name, i.unit, COALESCE(sb.quantity, 0) as stock
                                                FROM items i
                                                INNER JOIN stock_entries se ON i.item_id = se.item_id
                                                LEFT JOIN stock_balance sb ON i.item_id = sb.item_id
                                                WHERE se.created_by = '$electrical_user' AND se.qty_in > 0
                                                GROUP BY i.item_id, i.item_name, i.unit, sb.quantity
                                                ORDER BY i.item_name";
                                        
                                        $res = $mysqli->query($sql);
                                        
                                        if($res->num_rows > 0){
                                            while($r = $res->fetch_assoc()){
                                                $stock = (int)$r['stock'];
                                                $status_class = $stock == 0 ? 'badge-danger' : ($stock <= 10 ? 'badge-warning' : 'badge-success');
                                                $status_text = $stock == 0 ? 'Out of Stock' : ($stock <= 10 ? 'Low Stock' : 'In Stock');
                                                
                                                echo "<tr>";
                                                echo "<td>".htmlentities($r['item_name'])."</td>";
                                                echo "<td>".htmlentities($r['unit'])."</td>";
                                                echo "<td><strong>{$stock}</strong></td>";
                                                echo "<td><span class='badge {$status_class}'>{$status_text}</span></td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='4' class='text-center text-muted'>No electrical items found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include("assets/inc/footer.php"); ?>
</body>
</html>
