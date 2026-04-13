<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogins.php');
check_login();

// Get statistics for transport unit
$total_vehicles = $mysqli->query("SELECT COUNT(*) as count FROM vehicles")->fetch_assoc()['count'];
$total_tyres_result = $mysqli->query("SELECT COALESCE(SUM(sb.quantity), 0) as count FROM stock_balance sb JOIN items i ON sb.item_id = i.item_id JOIN categories c ON i.category_id = c.category_id WHERE c.name = 'Tyres'");
$total_tyres = round($total_tyres_result->fetch_assoc()['count']);
$assigned_tyres = $mysqli->query("SELECT COALESCE(SUM(quantity), 0) as count FROM tyre_assignment")->fetch_assoc()['count'];
$total_services = $mysqli->query("SELECT COUNT(*) as count FROM service_records")->fetch_assoc()['count'];
?>
<?php include("assets/inc/head.php"); ?>
<body>
<?php include("assets/inc/nav.php"); ?>
<?php include("assets/inc/sidebar_transport.php"); ?>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <h4 class="page-title">Transport Unit Dashboard</h4>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card widget-flat">
                        <div class="card-body">
                            <h5 class="text-muted font-weight-normal mt-0">Total Vehicles</h5>
                            <h3 class="mt-3 mb-3"><?php echo $total_vehicles; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card widget-flat">
                        <div class="card-body">
                            <h5 class="text-muted font-weight-normal mt-0">Available Tyres</h5>
                            <h3 class="mt-3 mb-3"><?php echo $total_tyres; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card widget-flat">
                        <div class="card-body">
                            <h5 class="text-muted font-weight-normal mt-0">Tyres Assigned</h5>
                            <h3 class="mt-3 mb-3"><?php echo $assigned_tyres; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card widget-flat">
                        <div class="card-body">
                            <h5 class="text-muted font-weight-normal mt-0">Total Services</h5>
                            <h3 class="mt-3 mb-3"><?php echo $total_services; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tyre Distribution Section -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title mb-3">Distribute Tyres to Vehicle</h4>
                            
                            <?php
                            if(isset($_POST['distribute'])){
                                $item = (int)$_POST['item_id'];
                                $vehicle = (int)$_POST['vehicle_id'];
                                $quantity = (int)$_POST['quantity'];
                                $notes = trim($_POST['notes']);
                                $by = $_SESSION['username'] ?? 'Unknown';

                                $mysqli->begin_transaction();
                                
                                try {
                                    // Check stock
                                    $check = $mysqli->prepare("SELECT quantity FROM stock_balance WHERE item_id = ?");
                                    $check->bind_param("i", $item);
                                    $check->execute();
                                    $result = $check->get_result();
                                    $stock = $result->fetch_assoc();
                                    $check->close();
                                    
                                    if(!$stock || $stock['quantity'] < $quantity){
                                        throw new Exception("Insufficient stock available.");
                                    }
                                    
                                    // Insert assignment
                                    $stmt = $mysqli->prepare("INSERT INTO tyre_assignment (item_id, quantity, vehicle_id, assigned_by, notes) VALUES (?, ?, ?, ?, ?)");
                                    $stmt->bind_param("iiiss", $item, $quantity, $vehicle, $by, $notes);
                                    $stmt->execute();
                                    $stmt->close();
                                    
                                    // Update stock
                                    $update = $mysqli->prepare("UPDATE stock_balance SET quantity = quantity - ? WHERE item_id = ?");
                                    $update->bind_param("ii", $quantity, $item);
                                    $update->execute();
                                    $update->close();
                                    
                                    // Record issue (stock_issues table has: item_id, unit_id, quantity, issued_by, purpose)
                                    $issue = $mysqli->prepare("INSERT INTO stock_issues (item_id, unit_id, quantity, purpose, issued_by) VALUES (?, ?, ?, ?, ?)");
                                    $unit_id = 1; // Default unit ID
                                    $purpose = "Assigned to vehicle (Transport Unit)";
                                    $issue->bind_param("iidss", $item, $unit_id, $quantity, $purpose, $by);
                                    $issue->execute();
                                    $issue->close();
                                    
                                    // Record entry (stock_entries table has: item_id, qty_in, qty_out, reference, note, created_by)
                                    $entry = $mysqli->prepare("INSERT INTO stock_entries (item_id, qty_out, reference, note, created_by) VALUES (?, ?, ?, ?, ?)");
                                    $reference = "Tyre Distribution";
                                    $entry->bind_param("idsss", $item, $quantity, $reference, $notes, $by);
                                    $entry->execute();
                                    $entry->close();
                                    
                                    $mysqli->commit();
                                    echo "<div class='alert alert-success'>Tyre distributed successfully and stock updated.</div>";
                                } catch(Exception $e) {
                                    $mysqli->rollback();
                                    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
                                }
                            }
                            ?>

                            <form method="POST" class="needs-validation">
                                <div class="form-row">
                                    <div class="form-group col-12 col-md-4">
                                        <label>Select Tyre *</label>
                                        <select name="item_id" class="form-control" required>
                                            <option value="">-- Select Tyre --</option>
                                            <?php
                                            $sql = "SELECT i.item_id, i.item_name, i.unit, COALESCE(sb.quantity, 0) as stock 
                                                    FROM items i 
                                                    LEFT JOIN stock_balance sb ON i.item_id = sb.item_id
                                                    JOIN categories c ON i.category_id = c.category_id 
                                                    WHERE c.name = 'Tyres' 
                                                    ORDER BY i.item_name";
                                            $res = $mysqli->query($sql);
                                            while($r = $res->fetch_assoc()){
                                                $stock_qty = (int)$r['stock'];
                                                $stock_info = $stock_qty > 0 ? " (Stock: {$stock_qty})" : " (Out of stock)";
                                                echo "<option value='{$r['item_id']}'>".htmlentities($r['item_name']).$stock_info."</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="form-group col-12 col-md-3">
                                        <label>Select Vehicle *</label>
                                        <select name="vehicle_id" class="form-control" required>
                                            <option value="">-- Select Vehicle --</option>
                                            <?php
                                            $res = $mysqli->query("SELECT vehicle_id, vehicle_number, model FROM vehicles ORDER BY vehicle_number");
                                            while($r = $res->fetch_assoc()){
                                                echo "<option value='{$r['vehicle_id']}'>".htmlentities($r['vehicle_number'])." (".htmlentities($r['model']).")</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="form-group col-12 col-md-2">
                                        <label>Quantity *</label>
                                        <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                                    </div>

                                    <div class="form-group col-12 col-md-3">
                                        <label>&nbsp;</label><br>
                                        <button type="submit" name="distribute" class="btn btn-primary btn-block">
                                            <i class="mdi mdi-check"></i> Distribute Tyre
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Notes</label>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="Add any notes about this distribution..."></textarea>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Tyre Distributions -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title mb-3">Recent Tyre Distributions</h4>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Tyre</th>
                                            <th>Quantity</th>
                                            <th>Vehicle</th>
                                            <th>Distributed By</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT ta.*, i.item_name, v.vehicle_number, v.model 
                                                FROM tyre_assignment ta 
                                                JOIN items i ON ta.item_id = i.item_id 
                                                JOIN vehicles v ON ta.vehicle_id = v.vehicle_id 
                                                ORDER BY ta.assigned_at DESC 
                                                LIMIT 10";
                                        $res = $mysqli->query($sql);
                                        if($res->num_rows > 0){
                                            while($r = $res->fetch_assoc()){
                                                echo "<tr>";
                                                echo "<td>".date('M d, Y H:i', strtotime($r['assigned_at']))."</td>";
                                                echo "<td>".htmlentities($r['item_name'])."</td>";
                                                echo "<td><span class='badge badge-info'>{$r['quantity']}</span></td>";
                                                echo "<td>".htmlentities($r['vehicle_number'])." <small class='text-muted'>(".htmlentities($r['model']).")</small></td>";
                                                echo "<td>".htmlentities($r['assigned_by'])."</td>";
                                                echo "<td>".htmlentities($r['notes'])."</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='6' class='text-center text-muted'>No distributions yet</td></tr>";
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
