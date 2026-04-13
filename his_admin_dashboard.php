<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogins.php');
check_login();

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
                                    <h4 class="page-title">Directorate of Works Inventory Dashboard</h4>
                                    <p class="mb-0 text-muted">Welcome, <?php echo htmlspecialchars($full_name); ?> — role: <?php echo htmlspecialchars(ucfirst($role)); ?></p>
                                </div>
                            </div>
                        </div>     
                        <!-- end page title --> 

                        <?php
                        // Get inventory statistics
                        $total_items = 0;
                        $res = $mysqli->query("SELECT COUNT(*) AS cnt FROM items");
                        if($res){
                            $r = $res->fetch_assoc();
                            $total_items = $r['cnt'];
                        }

                        $total_stock = 0;
                        $res = $mysqli->query("SELECT COALESCE(SUM(quantity), 0) AS total FROM stock_balance");
                        if($res){
                            $r = $res->fetch_assoc();
                            $total_stock = $r['total'];
                        }

                        $low_stock_count = 0;
                        $res = $mysqli->query("SELECT COUNT(*) AS cnt FROM stock_balance WHERE quantity < 20 AND quantity > 0");
                        if($res){
                            $r = $res->fetch_assoc();
                            $low_stock_count = $r['cnt'];
                        }

                        $out_of_stock = 0;
                        $res = $mysqli->query("SELECT COUNT(*) AS cnt FROM stock_balance WHERE quantity <= 0");
                        if($res){
                            $r = $res->fetch_assoc();
                            $out_of_stock = $r['cnt'];
                        }

                        $total_issued = 0;
                        $res = $mysqli->query("SELECT COALESCE(SUM(quantity), 0) AS total FROM stock_issues");
                        if($res){
                            $r = $res->fetch_assoc();
                            $total_issued = $r['total'];
                        }
                        ?>

                        <!-- Summary Cards -->
                        <div class="row">
                            <!-- Total Items Card -->
                            <div class="col-12 col-sm-6 col-xl-3 mb-3">
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
                                                <p class="text-muted mb-1 text-truncate">Total Items</p>
                                                <small class="text-muted">In inventory</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Stock Card -->
                            <div class="col-12 col-sm-6 col-xl-3 mb-3">
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
                                                <p class="text-muted mb-1 text-truncate">Total Stock</p>
                                                <small class="text-muted">Units in stock</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Low Stock Card -->
                            <div class="col-12 col-sm-6 col-xl-3 mb-3">
                                <div class="widget-rounded-circle card-box">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="avatar-lg rounded-circle bg-soft-warning border-warning border">
                                                <i class="fas fa-exclamation-triangle font-22 avatar-title text-warning"></i>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-right">
                                                <h3 class="text-dark mt-1"><span data-plugin="counterup"><?php echo (int)$low_stock_count;?></span></h3>
                                                <p class="text-muted mb-1 text-truncate">Low Stock</p>
                                                <small class="text-muted">Below reorder level</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Out of Stock Card -->
                            <div class="col-12 col-sm-6 col-xl-3 mb-3">
                                <div class="widget-rounded-circle card-box">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="avatar-lg rounded-circle bg-soft-danger border-danger border">
                                                <i class="fas fa-times-circle font-22 avatar-title text-danger"></i>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-right">
                                                <h3 class="text-dark mt-1"><span data-plugin="counterup"><?php echo (int)$out_of_stock;?></span></h3>
                                                <p class="text-muted mb-1 text-truncate">Out of Stock</p>
                                                <small class="text-muted">Empty items</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Stock Activity -->
                        <div class="row mt-4">
                            <!-- Recent Stock Movements -->
                            <div class="col-xl-6">
                                <div class="card-box">
                                    <h4 class="header-title mb-3">Recent Stock Movements</h4>

                                    <div class="table-responsive">
                                        <table class="table table-borderless table-hover table-centered m-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Item</th>
                                                    <th>Type</th>
                                                    <th>Qty</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $sql = "SELECT se.*, i.item_name 
                                                    FROM stock_entries se 
                                                    JOIN items i ON se.item_id = i.item_id 
                                                    ORDER BY se.created_at DESC 
                                                    LIMIT 8";
                                            $res = $mysqli->query($sql);
                                            if($res && $res->num_rows > 0) {
                                                while($row = $res->fetch_assoc()) {
                                                    $type_badge = ($row['qty_in'] > 0) ? '<span class="badge badge-success">In</span>' : '<span class="badge badge-danger">Out</span>';
                                                    $qty = ($row['qty_in'] > 0) ? $row['qty_in'] : $row['qty_out'];
                                                    echo "<tr>";
                                                    echo "<td>".htmlentities($row['item_name'])."</td>";
                                                    echo "<td>".$type_badge."</td>";
                                                    echo "<td>".number_format($qty, 0)."</td>";
                                                    echo "<td><small>".date('M d, h:i A', strtotime($row['created_at']))."</small></td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='4' class='text-center text-muted'>No movements</td></tr>";
                                            }
                                            ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Low Stock Alert Items -->
                            <div class="col-xl-6">
                                <div class="card-box">
                                    <h4 class="header-title mb-3">Low Stock Alert Items</h4>

                                    <div class="table-responsive">
                                        <table class="table table-borderless table-hover table-centered m-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Item</th>
                                                    <th>Current</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $sql = "SELECT sb.item_id, sb.quantity, i.item_name 
                                                    FROM stock_balance sb 
                                                    JOIN items i ON sb.item_id = i.item_id 
                                                    WHERE sb.quantity <= 20 
                                                    ORDER BY sb.quantity ASC 
                                                    LIMIT 8";
                                            $res = $mysqli->query($sql);
                                            if($res && $res->num_rows > 0) {
                                                while($row = $res->fetch_assoc()) {
                                                    $status = ($row['quantity'] <= 0) ? '<span class="badge badge-danger">Out</span>' : '<span class="badge badge-warning">Low</span>';
                                                    echo "<tr>";
                                                    echo "<td>".htmlentities($row['item_name'])."</td>";
                                                    echo "<td><strong>".number_format($row['quantity'], 0)."</strong></td>";
                                                    echo "<td>".$status."</td>";
                                                    echo "<td><a href='stock_receive.php' class='btn btn-xs btn-primary'>Receive</a></td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='4' class='text-center text-muted'>All items in stock</td></tr>";
                                            }
                                            ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Action Links -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card-box">
                                    <h4 class="header-title mb-3">Quick Actions</h4>
                                    <div class="btn-group btn-group-lg" role="group">
                                        <a href="stock_receive.php" class="btn btn-primary"><i class="fas fa-arrow-down"></i> Receive Stock</a>
                                        <a href="issue_items.php" class="btn btn-warning"><i class="fas fa-arrow-up"></i> Issue Items</a>
                                        <a href="stock_management.php" class="btn btn-info"><i class="fas fa-sliders-h"></i> Manage Stock</a>
                                        <a href="stock_review.php" class="btn btn-success"><i class="fas fa-eye"></i> Stock Review</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
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

        <!-- Vendor js -->
        <script src="assets/js/vendor.min.js"></script>

        <!-- Plugins js-->
        <script src="assets/libs/flatpickr/flatpickr.min.js"></script>
        <script src="assets/libs/jquery-knob/jquery.knob.min.js"></script>
        <script src="assets/libs/jquery-sparkline/jquery.sparkline.min.js"></script>

        <!-- App js-->
        <script src="assets/js/app.min.js"></script>
        
    </body>

</html>
