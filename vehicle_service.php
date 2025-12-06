<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogins.php');
check_login();

$err = $success = '';
if(isset($_POST['add_service'])){
    $vehicle_id = (int)$_POST['vehicle_id'];
    $stype = trim($_POST['service_type']);
    $sdate = $_POST['service_date'];
    $next = $_POST['next_service_date'] ?: null;
    $odo = $_POST['odometer'] ?: null;
    $notes = trim($_POST['notes']);
    $by = $_SESSION['doc_number'];

    $stmt = $mysqli->prepare("INSERT INTO service_records (vehicle_id, service_type, service_date, next_service_date, odometer, notes, performed_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssiss", $vehicle_id, $stype, $sdate, $next, $odo, $notes, $by);
    if($stmt->execute()) $success = "Service recorded.";
    else $err = "Error: ".$mysqli->error;
    $stmt->close();
}
?>
<?php include("assets/inc/head.php"); ?>
<body><?php include("assets/inc/nav.php"); ?>

<div class="container mt-4">
  <h3>Vehicle Service Tracker</h3>
  <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
  <?php if($err) echo "<div class='alert alert-danger'>$err</div>"; ?>

  <div class="card-box">
    <form method="POST">
      <div class="form-row">
        <div class="form-group col-md-4">
          <label>Vehicle</label>
          <select name="vehicle_id" class="form-control">
            <?php $res = $mysqli->query("SELECT vehicle_id, vehicle_number FROM vehicles ORDER BY vehicle_number"); while($r = $res->fetch_assoc()) echo "<option value='{$r['vehicle_id']}'>".htmlentities($r['vehicle_number'])."</option>"; ?>
          </select>
        </div>
        <div class="form-group col-md-4">
          <label>Service Type</label>
          <input name="service_type" class="form-control" required>
        </div>
        <div class="form-group col-md-4">
          <label>Service Date</label>
          <input type="date" name="service_date" class="form-control" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-4"><label>Next Service</label><input type="date" name="next_service_date" class="form-control"></div>
        <div class="form-group col-md-4"><label>Odometer</label><input type="number" name="odometer" class="form-control"></div>
        <div class="form-group col-md-4"><label>Performed By</label><input name="performed_by" class="form-control" value="<?= htmlentities($_SESSION['doc_number']) ?>"></div>
      </div>

      <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>

      <button class="btn btn-secondary" name="add_service">Add Service</button>
    </form>
  </div>

  <div class="card-box mt-3">
    <h5>Recent Services</h5>
    <div class="table-responsive">
      <table class="table table-striped">
      <thead><tr><th>Date</th><th>Vehicle</th><th>Type</th><th>Odometer</th><th>Next</th><th>By</th></tr></thead>
      <tbody>
      <?php
      $sql = "SELECT sr.*, v.vehicle_number FROM service_records sr JOIN vehicles v ON sr.vehicle_id = v.vehicle_id ORDER BY sr.service_date DESC LIMIT 100";
      $res = $mysqli->query($sql);
      while($r = $res->fetch_assoc()){
        echo "<tr><td>{$r['service_date']}</td><td>".htmlentities($r['vehicle_number'])."</td><td>".htmlentities($r['service_type'])."</td><td>".htmlentities($r['odometer'])."</td><td>".htmlentities($r['next_service_date'])."</td><td>".htmlentities($r['performed_by'])."</td></tr>";
      }
      ?>
      </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>