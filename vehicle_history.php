<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogins.php');
check_login();

$err = $success = '';
$selected_vehicle = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;

// Handle service record addition
if(isset($_POST['add_service'])){
    $vehicle_id = (int)$_POST['vehicle_id'];
    $service_type = trim($_POST['service_type']);
    $description = trim($_POST['description']);
    $cost = (float)$_POST['cost'];
    $service_date = $_POST['service_date'];
    $mileage = (int)$_POST['mileage'];
    $service_provider = trim($_POST['service_provider']);
    $recorded_by = $_SESSION['doc_number'];

    if(empty($service_type)){
        $err = "Service type is required.";
    } else if($service_date == ''){
        $err = "Service date is required.";
    } else {
        $stmt = $mysqli->prepare("INSERT INTO vehicle_service_history (vehicle_id, service_type, description, cost, service_date, mileage, service_provider, recorded_by, recorded_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issdisss", $vehicle_id, $service_type, $description, $cost, $service_date, $mileage, $service_provider, $recorded_by);
        
        if($stmt->execute()){
            $success = "Service record added successfully.";
            $selected_vehicle = $vehicle_id;
        } else {
            $err = "Error adding service record: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get vehicle list
$vehicles = [];
$res = $mysqli->query("SELECT vehicle_id, plate_number, vehicle_name FROM vehicles ORDER BY plate_number");
if($res){
    while($r = $res->fetch_assoc()){
        $vehicles[] = $r;
    }
}
?>
<?php include("assets/inc/head.php"); ?>
<body>
<?php include("assets/inc/nav.php"); ?>
<?php include("assets/inc/sidebar_admin.php"); ?>

<div class="content-page">
<div class="content container">
  <h3>Vehicle Service History</h3>
  <?php if($success) echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>".$success."<button type='button' class='close' data-dismiss='alert'><span>&times;</span></button></div>"; ?>
  <?php if($err) echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>".$err."<button type='button' class='close' data-dismiss='alert'><span>&times;</span></button></div>"; ?>

  <!-- Tab Navigation -->
  <ul class="nav nav-tabs" id="vehicleTabs" role="tablist">
    <li class="nav-item">
      <a class="nav-link active" id="history-tab" data-toggle="tab" href="#history-panel" role="tab">View History</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" id="add-tab" data-toggle="tab" href="#add-panel" role="tab">Add Service Record</a>
    </li>
  </ul>

  <!-- Tab Content -->
  <div class="tab-content">
    <!-- View History Tab -->
    <div class="tab-pane fade show active" id="history-panel" role="tabpanel">
      <div class="card-box mt-3">
        <h5>Select Vehicle</h5>
        <form method="GET" class="form-inline mb-3">
          <select name="vehicle_id" class="form-control mr-2" onchange="this.form.submit()">
            <option value="">-- Select a Vehicle --</option>
            <?php
            foreach($vehicles as $v){
              $selected = ($v['vehicle_id'] == $selected_vehicle) ? 'selected' : '';
              echo "<option value='{$v['vehicle_id']}' $selected>".htmlentities($v['plate_number'] . ' - ' . $v['vehicle_name'])."</option>";
            }
            ?>
          </select>
        </form>

        <?php if($selected_vehicle > 0): ?>
        <!-- Service History Table -->
        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead>
              <tr>
                <th>Date</th>
                <th>Service Type</th>
                <th>Description</th>
                <th>Mileage</th>
                <th>Cost</th>
                <th>Service Provider</th>
                <th>Recorded By</th>
              </tr>
            </thead>
            <tbody>
            <?php
            $sql = "SELECT * FROM vehicle_service_history WHERE vehicle_id = ? ORDER BY service_date DESC";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $selected_vehicle);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if($res->num_rows > 0){
              while($r = $res->fetch_assoc()){
                echo "<tr>";
                echo "<td>".htmlentities($r['service_date'])."</td>";
                echo "<td><strong>".htmlentities($r['service_type'])."</strong></td>";
                echo "<td>".htmlentities($r['description'])."</td>";
                echo "<td>".htmlentities($r['mileage'])." km</td>";
                echo "<td class='text-success'>UGX ".number_format($r['cost'], 0)."</td>";
                echo "<td>".htmlentities($r['service_provider'])."</td>";
                echo "<td><small>".htmlentities($r['recorded_by'])."</small></td>";
                echo "</tr>";
              }
            } else {
              echo "<tr><td colspan='7' class='text-center text-muted'>No service records found for this vehicle</td></tr>";
            }
            $stmt->close();
            ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div class="alert alert-info mt-3">Please select a vehicle to view its service history.</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Add Service Record Tab -->
    <div class="tab-pane fade" id="add-panel" role="tabpanel">
      <div class="card-box mt-3">
        <h5>Add Service Record</h5>
        <form method="POST">
          <div class="form-row">
            <div class="form-group col-12 col-md-6">
              <label>Vehicle <span class="text-danger">*</span></label>
              <select name="vehicle_id" id="vehicle_id" class="form-control" required onchange="getVehicleInfo()">
                <option value="">-- Select Vehicle --</option>
                <?php
                foreach($vehicles as $v){
                  echo "<option value='{$v['vehicle_id']}'>".htmlentities($v['plate_number'] . ' - ' . $v['vehicle_name'])."</option>";
                }
                ?>
              </select>
            </div>

            <div class="form-group col-12 col-md-6">
              <label>Service Date <span class="text-danger">*</span></label>
              <input type="date" name="service_date" class="form-control" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-12 col-md-6">
              <label>Service Type <span class="text-danger">*</span></label>
              <select name="service_type" class="form-control" required>
                <option value="">-- Select Service Type --</option>
                <option value="Oil Change">Oil Change</option>
                <option value="Tire Replacement">Tire Replacement</option>
                <option value="Filter Change">Filter Change</option>
                <option value="Brake Service">Brake Service</option>
                <option value="Engine Repair">Engine Repair</option>
                <option value="Transmission Service">Transmission Service</option>
                <option value="General Maintenance">General Maintenance</option>
                <option value="Inspection">Inspection</option>
                <option value="Suspension Work">Suspension Work</option>
                <option value="Electrical Work">Electrical Work</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div class="form-group col-12 col-md-6">
              <label>Mileage (km)</label>
              <input type="number" name="mileage" class="form-control" placeholder="Odometer reading" value="0">
            </div>
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Details of the service performed..."></textarea>
          </div>

          <div class="form-row">
            <div class="form-group col-12 col-md-6">
              <label>Service Provider</label>
              <input type="text" name="service_provider" class="form-control" placeholder="e.g., XYZ Auto Repair Shop">
            </div>

            <div class="form-group col-12 col-md-6">
              <label>Cost (UGX)</label>
              <input type="number" step="0.01" name="cost" class="form-control" placeholder="0.00" value="0">
            </div>
          </div>

          <button type="submit" name="add_service" class="btn btn-success"><i class="fe-check"></i> Add Service Record</button>
          <button type="reset" class="btn btn-light"><i class="fe-x"></i> Clear</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Service Summary by Type -->
  <div class="card-box mt-4">
    <h5>Service Summary by Type</h5>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>Service Type</th>
            <th>Total Count</th>
            <th>Total Cost (UGX)</th>
            <th>Last Service Date</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $sql = "SELECT 
                  service_type, 
                  COUNT(*) as count, 
                  SUM(cost) as total_cost,
                  MAX(service_date) as last_date
                FROM vehicle_service_history 
                GROUP BY service_type 
                ORDER BY count DESC";
        $res = $mysqli->query($sql);
        if($res && $res->num_rows > 0){
          while($r = $res->fetch_assoc()){
            echo "<tr>";
            echo "<td>".htmlentities($r['service_type'])."</td>";
            echo "<td>".htmlentities($r['count'])."</td>";
            echo "<td class='text-success'>UGX ".number_format($r['total_cost'], 0)."</td>";
            echo "<td>".htmlentities($r['last_date'])."</td>";
            echo "</tr>";
          }
        } else {
          echo "<tr><td colspan='4' class='text-center text-muted'>No service records</td></tr>";
        }
        ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- All Service Records (Paginated) -->
  <div class="card-box mt-4">
    <h5>All Service Records</h5>
    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead>
          <tr>
            <th>Vehicle</th>
            <th>Service Date</th>
            <th>Type</th>
            <th>Mileage</th>
            <th>Cost</th>
            <th>Provider</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $sql = "SELECT vsh.*, v.plate_number, v.vehicle_name 
                FROM vehicle_service_history vsh 
                JOIN vehicles v ON vsh.vehicle_id = v.vehicle_id 
                ORDER BY vsh.service_date DESC 
                LIMIT 50";
        $res = $mysqli->query($sql);
        if($res && $res->num_rows > 0){
          while($r = $res->fetch_assoc()){
            echo "<tr>";
            echo "<td>".htmlentities($r['plate_number'] . ' - ' . $r['vehicle_name'])."</td>";
            echo "<td>".htmlentities($r['service_date'])."</td>";
            echo "<td>".htmlentities($r['service_type'])."</td>";
            echo "<td>".htmlentities($r['mileage'])." km</td>";
            echo "<td class='text-success'>UGX ".number_format($r['cost'], 0)."</td>";
            echo "<td>".htmlentities($r['service_provider'])."</td>";
            echo "</tr>";
          }
        } else {
          echo "<tr><td colspan='6' class='text-center text-muted'>No service records</td></tr>";
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
function getVehicleInfo() {
    var vehicle_id = document.getElementById('vehicle_id').value;
    if(vehicle_id) {
        // Could fetch vehicle current mileage via AJAX here
        console.log('Vehicle selected: ' + vehicle_id);
    }
}
</script>

</body>
</html>
