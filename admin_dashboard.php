<?php
session_start();
include('assets/inc/config.php'); // must define $mysqli
include('assets/inc/functions.php'); // optional, keep if you use log_action etc.

// Simple auth for the new users table
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$uid = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$role = $_SESSION['role'] ?? '';
$full_name = $_SESSION['full_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
    
    <!--Head Code-->

<?php include("assets/inc/head.php"); ?>
<body>
<?php include("assets/inc/nav.php"); ?>
<?php include("assets/inc/sidebar_admin.php"); ?>

        <!-- Begin page -->
        <div id="wrapper">

        
            <!-- ========== Left Sidebar Start ========== -->
            <?php include('assets/inc/sidebar_admin.php');?>
            <!-- Left Sidebar End -->

            <!-- ============================================================== -->
            <!-- Start Page Content here -->
            <!-- ============================================================== -->

            <div class="content-page">
                <div class="content">

                    <!-- Start Content-->
                    <div class="container-fluid">
                        
                        <!-- start page title -->
                        <div class="row">
                            <div class="col-12">
                                <div class="page-title-box">
                                    <h4 class="page-title">OOU Directorate of Works Dashboard</h4>
                                    <p class="mb-0 text-muted">Welcome, <?php echo htmlspecialchars($full_name); ?> — role: <?php echo htmlspecialchars(ucfirst($role)); ?></p>
                                </div>
                            </div>
                        </div>     
                        <!-- end page title --> 

                        <?php
                        // Basic counts for inventory cards using the advanced schema (items, stock_balance, stock_issues)
                        // Total distinct items
                        $total_items = 0;
                        $res = $mysqli->query("SELECT COUNT(*) AS cnt FROM items");
                        if($res){
                            $r = $res->fetch_assoc();
                            $total_items = $r['cnt'];
                        }

                        // Total stock across all items (sum quantity)
                        $total_stock = 0;
                        $res = $mysqli->query("SELECT COALESCE(SUM(quantity),0) AS total_stock FROM stock_balance");
                        if($res){
                            $r = $res->fetch_assoc();
                            $total_stock = (int)$r['total_stock'];
                        }

                        // Total issued quantity
                        $total_issued = 0;
                        $res = $mysqli->query("SELECT COALESCE(SUM(quantity),0) AS total_issued FROM stock_issues");
                        if($res){
                            $r = $res->fetch_assoc();
                            $total_issued = (int)$r['total_issued'];
                        }

                        // Tyres count (items in Tyres category)
                        $tyre_count = 0;
                        $res = $mysqli->query("SELECT c.category_id FROM categories c WHERE c.name = 'Tyres' LIMIT 1");
                        $tyreCategoryId = null;
                        if($res && $r = $res->fetch_assoc()){
                            $tyreCategoryId = $r['category_id'];
                        }
                        if ($tyreCategoryId !== null) {
                            $stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM items WHERE category_id = ?");
                            $stmt->bind_param("i", $tyreCategoryId);
                            $stmt->execute();
                            $stmt->bind_result($cnt);
                            $stmt->fetch();
                            $stmt->close();
                            $tyre_count = $cnt;
                        }

                        // Assigned tyres count
                        $assigned_tyres = 0;
                        $res = $mysqli->query("SELECT COUNT(*) AS cnt FROM tyre_assignment");
                        if($res){
                            $r = $res->fetch_assoc();
                            $assigned_tyres = $r['cnt'];
                        }

                        // Diesel total
                        $diesel_total = 0;
                        $res = $mysqli->query("SELECT COALESCE(SUM(quantity),0) AS total FROM diesel_log");
                        if($res){ $r = $res->fetch_assoc(); $diesel_total = (int)$r['total']; }

                        // Service count
                        $service_count = 0;
                        $res = $mysqli->query("SELECT COUNT(*) AS cnt FROM service_records");
                        if($res){ $r = $res->fetch_assoc(); $service_count = $r['cnt']; }
                        ?>

                        <div class="row">
                            <!-- Inventory: Store Items -->
                            <div class="col-12 col-md-6 col-xl-4 mb-3">
                                <div class="widget-rounded-circle card-box">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="avatar-lg rounded-circle bg-soft-primary border-primary border">
                                                <i class="fas fa-warehouse font-22 avatar-title text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-right">
                                                <h3 class="text-dark mt-1"><span data-plugin="counterup"><?php echo (int)$total_items;?></span></h3>
                                                <p class="text-muted mb-1 text-truncate">Store Items</p>
                                                <small class="text-muted">Register tyres, diesel, oils & parts</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Inventory: Total Stock -->
                            <div class="col-12 col-md-6 col-xl-4 mb-3">
                                <div class="widget-rounded-circle card-box">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="avatar-lg rounded-circle bg-soft-success border-success border">
                                                <i class="fas fa-boxes font-22 avatar-title text-success"></i>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-right">
                                                <h3 class="text-dark mt-1"><span data-plugin="counterup"><?php echo (int)$total_stock;?></span></h3>
                                                <p class="text-muted mb-1 text-truncate">Total Stock (Units)</p>
                                                <small class="text-muted">Sum of all stock quantities</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Inventory: Total Issued -->
                            <div class="col-12 col-md-6 col-xl-4 mb-3">
                                <div class="widget-rounded-circle card-box">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="avatar-lg rounded-circle bg-soft-warning border-warning border">
                                                <i class="fas fa-truck-loading font-22 avatar-title text-warning"></i>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-right">
                                                <h3 class="text-dark mt-1"><span data-plugin="counterup"><?php echo (int)$total_issued;?></span></h3>
                                                <p class="text-muted mb-1 text-truncate">Total Issued</p>
                                                <small class="text-muted">Distributed to units</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Inventory: Tyres -->
                            <div class="col-12 col-md-6 col-xl-4 mb-3">
                                <div class="widget-rounded-circle card-box">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="avatar-lg rounded-circle bg-soft-info border-info border">
                                                <i class="fas fa-tire font-22 avatar-title text-info"></i>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-right">
                                                <h3 class="text-dark mt-1"><span data-plugin="counterup"><?php echo (int)$tyre_count;?></span></h3>
                                                <p class="text-muted mb-1 text-truncate">Tyre Types</p>
                                                <small class="text-muted"><?php echo (int)$assigned_tyres;?> assigned</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Inventory: Diesel Logs -->
                            <div class="col-12 col-md-6 col-xl-4 mb-3">
                                <div class="widget-rounded-circle card-box">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="avatar-lg rounded-circle bg-soft-dark border-dark border">
                                                <i class="fas fa-gas-pump font-22 avatar-title text-dark"></i>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-right">
                                                <h3 class="text-dark mt-1"><span data-plugin="counterup"><?php echo (int)$diesel_total;?></span></h3>
                                                <p class="text-muted mb-1 text-truncate">Diesel (Litres)</p>
                                                <small class="text-muted">Logged consumption</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Inventory: Vehicle Service -->
                            <div class="col-12 col-md-6 col-xl-4 mb-3">
                                <div class="widget-rounded-circle card-box">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="avatar-lg rounded-circle bg-soft-secondary border-secondary border">
                                                <i class="fas fa-tools font-22 avatar-title text-secondary"></i>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-right">
                                                <h3 class="text-dark mt-1"><span data-plugin="counterup"><?php echo (int)$service_count;?></span></h3>
                                                <p class="text-muted mb-1 text-truncate">Service Records</p>
                                                <small class="text-muted">Vehicle servicing logged</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div> <!-- end row of cards -->

                        <!-- Quick action buttons -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card-box">
                                    <div class="btn-group" role="group" aria-label="Quick actions">
                                        <a href="store_items.php" class="btn btn-primary">Store Items</a>
                                        <a href="stock_management.php" class="btn btn-success">Manage Stock</a>
                                        <a href="issue_items.php" class="btn btn-warning">Issue Items</a>
                                        <a href="inventory_report.php" class="btn btn-info">Stock Report</a>
                                        <a href="inventory_history.php" class="btn btn-secondary">Issuance History</a>
                                        <a href="inventory_charts.php" class="btn btn-dark">Usage Charts</a>
                                        <a href="diesel_consumption.php" class="btn btn-outline-dark">Diesel Log</a>
                                        <a href="vehicle_service.php" class="btn btn-outline-secondary">Vehicle Service</a>
                                        <a href="tyre_assignment.php" class="btn btn-outline-info">Tyre Assignment</a>
                                        <a href="download_stock.php" class="btn btn-light">Download Stock (XLS)</a>
                                        <a href="upload_stock.php" class="btn btn-light">Upload Stock (CSV)</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php
                        // Low stock alert: check thresholds table first; fallback threshold 20
                        $low_items = [];
                        $table_exists = $mysqli->query("SHOW TABLES LIKE 'inventory_thresholds'")->num_rows;
                        if($table_exists){
                            $sql = "SELECT it.item_name, COALESCE(sb.quantity,0) AS qty, t.threshold_qty
                                    FROM items it
                                    LEFT JOIN stock_balance sb ON it.item_id = sb.item_id
                                    LEFT JOIN inventory_thresholds t ON it.item_id = t.item_id
                                    WHERE COALESCE(sb.quantity,0) <= COALESCE(t.threshold_qty, 20)
                                    ORDER BY COALESCE(sb.quantity,0) ASC
                                    LIMIT 10";
                            $res = $mysqli->query($sql);
                            if($res){
                                while($r = $res->fetch_assoc()){
                                    $low_items[] = $r;
                                }
                            }
                        } else {
                            $res = $mysqli->query("SELECT it.item_name, COALESCE(sb.quantity,0) AS qty FROM items it LEFT JOIN stock_balance sb ON it.item_id = sb.item_id WHERE COALESCE(sb.quantity,0) < 20 ORDER BY COALESCE(sb.quantity,0) ASC LIMIT 10");
                            if($res){
                                while($r = $res->fetch_assoc()){
                                    $r['threshold_qty'] = 20;
                                    $low_items[] = $r;
                                }
                            }
                        }
                        ?>

                        <?php if(count($low_items) > 0): ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-danger">
                                    <strong>Low Stock Warning:</strong>
                                    <ul class="mb-0 mt-2">
                                        <?php foreach($low_items as $li): ?>
                                            <li><?php echo htmlentities($li['item_name']); ?> — <?php echo $li['qty']; ?> (Threshold: <?php echo $li['threshold_qty']; ?>)</li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Recently Added Items (replaces hospital staff) -->
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="card-box">
                                    <h4 class="header-title mb-3">Recently Added Items</h4>

                                    <div class="table-responsive">
                                        <table class="table table-borderless table-hover table-centered m-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Added</th>
                                                    <th>Item</th>
                                                    <th>Category</th>
                                                    <th>Unit</th>
                                                    <th>Current Qty</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sql = "SELECT it.item_id, it.item_name, c.name AS category, it.unit, COALESCE(sb.quantity,0) AS qty, it.created_at
                                                        FROM items it
                                                        LEFT JOIN categories c ON it.category_id = c.category_id
                                                        LEFT JOIN stock_balance sb ON it.item_id = sb.item_id
                                                        ORDER BY it.created_at DESC
                                                        LIMIT 10";
                                                $res = $mysqli->query($sql);
                                                while($row = $res->fetch_assoc()){
                                                    echo "<tr>";
                                                    echo "<td>".htmlentities($row['created_at'])."</td>";
                                                    echo "<td>".htmlentities($row['item_name'])."</td>";
                                                    echo "<td>".htmlentities($row['category'])."</td>";
                                                    echo "<td>".htmlentities($row['unit'])."</td>";
                                                    echo "<td>".htmlentities($row['qty'])."</td>";
                                                    echo "<td><a href='store_items.php?edit_id={$row['item_id']}' class='btn btn-xs btn-primary'>Edit</a> <a href='stock_management.php?item_id={$row['item_id']}' class='btn btn-xs btn-success'>Manage</a></td>";
                                                    echo "</tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div> <!-- end col -->                                                                                                                                                                                                                                         

                        </div>
                        <!-- end row -->
                        
                    </div> <!-- container -->

                </div> <!-- content -->

                <!-- Footer Start -->
                <?php include('assets/inc/footer.php');?>
                <!-- end Footer -->

            </div>

            <!-- ============================================================== -->
            <!-- End Page content -->
            <!-- ============================================================== -->


        </div>
        <!-- END wrapper -->

        <!-- Right Sidebar -->
        <div class="right-bar">
            <div class="rightbar-title">
                <a href="javascript:void(0);" class="right-bar-toggle float-right">
                    <i class="dripicons-cross noti-icon"></i>
                </a>
                <h5 class="m-0 text-white">Settings</h5>
            </div>
            <div class="slimscroll-menu">
                <!-- User box -->
                <div class="user-box">
                    <div class="user-img">
                        <img src="assets/images/users/user-1.jpg" alt="user-img" title="Mat Helme" class="rounded-circle img-fluid">
                        <a href="javascript:void(0);" class="user-edit"><i class="mdi mdi-pencil"></i></a>
                    </div>
            
                    <h5><a href="javascript: void(0);"><?php echo htmlspecialchars($full_name); ?></a> </h5>
                    <p class="text-muted mb-0"><small><?php echo htmlspecialchars(ucfirst($role)); ?></small></p>
                </div>

                <!-- Settings -->
                <hr class="mt-0" />
                <h5 class="pl-3">Basic Settings</h5>
                <hr class="mb-0" />

                <div class="p-3">
                    <div class="checkbox checkbox-primary mb-2">
                        <input id="Rcheckbox1" type="checkbox" checked>
                        <label for="Rcheckbox1">
                            Notifications
                        </label>
                    </div>
                    <div class="checkbox checkbox-primary mb-2">
                        <input id="Rcheckbox2" type="checkbox" checked>
                        <label for="Rcheckbox2">
                            API Access
                        </label>
                    </div>
                    <div class="checkbox checkbox-primary mb-2">
                        <input id="Rcheckbox3" type="checkbox">
                        <label for="Rcheckbox3">
                            Auto Updates
                        </label>
                    </div>
                    <div class="checkbox checkbox-primary mb-2">
                        <input id="Rcheckbox4" type="checkbox" checked>
                        <label for="Rcheckbox4">
                            Online Status
                        </label>
                    </div>
                    <div class="checkbox checkbox-primary mb-0">
                        <input id="Rcheckbox5" type="checkbox" checked>
                        <label for="Rcheckbox5">
                            Auto Payout
                        </label>
                    </div>
                </div>

                <!-- Timeline -->
                <hr class="mt-0" />
                <h5 class="px-3">Messages <span class="float-right badge badge-pill badge-danger">25</span></h5>
                <hr class="mb-0" />
                <div class="p-3">
                    <div class="inbox-widget">
                        <div class="inbox-item">
                            <div class="inbox-item-img"><img src="assets/images/users/user-2.jpg" class="rounded-circle" alt=""></div>
                            <p class="inbox-item-author"><a href="javascript: void(0);" class="text-dark">Tomaslau</a></p>
                            <p class="inbox-item-text">I've finished it! See you so...</p>
                        </div>
                        <div class="inbox-item">
                            <div class="inbox-item-img"><img src="assets/images/users/user-3.jpg" class="rounded-circle" alt=""></div>
                            <p class="inbox-item-author"><a href="javascript: void(0);" class="text-dark">Stillnotdavid</a></p>
                            <p class="inbox-item-text">This theme is awesome!</p>
                        </div>
                        <div class="inbox-item">
                            <div class="inbox-item-img"><img src="assets/images/users/user-4.jpg" class="rounded-circle" alt=""></div>
                            <p class="inbox-item-author"><a href="javascript: void(0);" class="text-dark">Kurafire</a></p>
                            <p class="inbox-item-text">Nice to meet you</p>
                        </div>

                        <div class="inbox-item">
                            <div class="inbox-item-img"><img src="assets/images/users/user-5.jpg" class="rounded-circle" alt=""></div>
                            <p class="inbox-item-author"><a href="javascript: void(0);" class="text-dark">Shahedk</a></p>
                            <p class="inbox-item-text">Hey! there I'm available...</p>
                        </div>
                        <div class="inbox-item">
                            <div class="inbox-item-img"><img src="assets/images/users/user-6.jpg" class="rounded-circle" alt=""></div>
                            <p class="inbox-item-author"><a href="javascript: void(0);" class="text-dark">Adhamdannaway</a></p>
                            <p class="inbox-item-text">This theme is awesome!</p>
                        </div>
                    </div> <!-- end inbox-widget -->
                </div> <!-- end .p-3-->

            </div> <!-- end slimscroll-menu-->
        </div>
        <!-- /Right-bar -->

        <!-- Right bar overlay-->
        <div class="rightbar-overlay"></div>

        <!-- Vendor js -->
        <script src="assets/js/vendor.min.js"></script>

        <!-- Plugins js-->
        <script src="assets/libs/flatpickr/flatpickr.min.js"></script>
        <script src="assets/libs/jquery-knob/jquery.knob.min.js"></script>
        <script src="assets/libs/jquery-sparkline/jquery.sparkline.min.js"></script>
        <script src="assets/libs/flot-charts/jquery.flot.js"></script>
        <script src="assets/libs/flot-charts/jquery.flot.time.js"></script>
        <script src="assets/libs/flot-charts/jquery.flot.tooltip.min.js"></script>
        <script src="assets/libs/flot-charts/jquery.flot.selection.js"></script>
        <script src="assets/libs/flot-charts/jquery.flot.crosshair.js"></script>

        <!-- Dashboar 1 init js-->
        <script src="assets/js/pages/dashboard-1.init.js"></script>

        <!-- App js-->
        <script src="assets/js/app.min.js"></script>
        
    </body>

</html>
