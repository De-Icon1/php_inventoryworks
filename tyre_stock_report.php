<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogins.php');
check_login();
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
                        <h4 class="page-title">Tyre Stock Report</h4>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title mb-3">Available Tyre Stock</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Tyre Name</th>
                                            <th>Unit/Type</th>
                                            <th>Current Stock</th>
                                            <th>Total Assigned</th>
                                            <th>Available</th>
                                            <th>Last Updated</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT 
                                                    i.item_id,
                                                    i.item_name,
                                                    i.unit,
                                                    COALESCE(sb.quantity, 0) as current_stock,
                                                    COALESCE(SUM(ta.quantity), 0) as total_assigned,
                                                    COALESCE(sb.quantity, 0) as available,
                                                    sb.last_updated
                                                FROM items i
                                                JOIN categories c ON i.category_id = c.category_id
                                                LEFT JOIN stock_balance sb ON i.item_id = sb.item_id
                                                LEFT JOIN tyre_assignment ta ON i.item_id = ta.item_id
                                                WHERE c.name = 'Tyres'
                                                GROUP BY i.item_id
                                                ORDER BY i.item_name";
                                        
                                        $res = $mysqli->query($sql);
                                        
                                        if($res->num_rows > 0){
                                            while($r = $res->fetch_assoc()){
                                                $stock_class = $r['available'] == 0 ? 'text-danger font-weight-bold' : ($r['available'] <= 5 ? 'text-warning font-weight-bold' : '');
                                                
                                                echo "<tr>";
                                                echo "<td>".htmlentities($r['item_name'])."</td>";
                                                echo "<td>".htmlentities($r['unit'])."</td>";
                                                echo "<td class='{$stock_class}'>".(int)$r['current_stock']."</td>";
                                                echo "<td>".(int)$r['total_assigned']."</td>";
                                                echo "<td class='{$stock_class}'>".(int)$r['available']."</td>";
                                                echo "<td>".($r['last_updated'] ? date('M d, Y', strtotime($r['last_updated'])) : 'N/A')."</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='6' class='text-center text-muted'>No tyre stock available</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Distribution Summary -->
            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title mb-3">Tyre Distribution by Vehicle</h4>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Vehicle</th>
                                            <th>Type</th>
                                            <th>Tyres Assigned</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT 
                                                    v.vehicle_number,
                                                    v.model,
                                                    COUNT(ta.assign_id) as assignment_count,
                                                    SUM(ta.quantity) as total_tyres
                                                FROM vehicles v
                                                LEFT JOIN tyre_assignment ta ON v.vehicle_id = ta.vehicle_id
                                                GROUP BY v.vehicle_id
                                                HAVING total_tyres > 0
                                                ORDER BY total_tyres DESC";
                                        
                                        $res = $mysqli->query($sql);
                                        
                                        if($res->num_rows > 0){
                                            while($r = $res->fetch_assoc()){
                                                echo "<tr>";
                                                echo "<td>".htmlentities($r['vehicle_number'])."</td>";
                                                echo "<td>".htmlentities($r['model'])."</td>";
                                                echo "<td><span class='badge badge-primary'>{$r['total_tyres']}</span></td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='3' class='text-center text-muted'>No tyres assigned yet</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title mb-3">Distribution Summary</h4>
                            <?php
                            $total_tyres_in_stock = $mysqli->query("SELECT COALESCE(SUM(sb.quantity), 0) as total FROM stock_balance sb JOIN items i ON sb.item_id = i.item_id JOIN categories c ON i.category_id = c.category_id WHERE c.name = 'Tyres'")->fetch_assoc()['total'];
                            $total_assigned = $mysqli->query("SELECT COALESCE(SUM(quantity), 0) as total FROM tyre_assignment")->fetch_assoc()['total'];
                            $total_vehicles_with_tyres = $mysqli->query("SELECT COUNT(DISTINCT vehicle_id) as total FROM tyre_assignment")->fetch_assoc()['total'];
                            ?>
                            
                            <div class="mt-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total Tyres in Stock:</span>
                                    <strong><?php echo (int)$total_tyres_in_stock; ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total Tyres Assigned:</span>
                                    <strong><?php echo (int)$total_assigned; ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Vehicles with Tyres:</span>
                                    <strong><?php echo $total_vehicles_with_tyres; ?></strong>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <span class="font-weight-bold">Available for Distribution:</span>
                                    <strong class="text-primary"><?php echo (int)$total_tyres_in_stock; ?></strong>
                                </div>
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
