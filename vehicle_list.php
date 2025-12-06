<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogins.php');
check_login();

$err = $success = '';
if(isset($_POST['add_vehicle'])){
    $vno = trim($_POST['vehicle_number']);
    $model = trim($_POST['model']);
    $dept = trim($_POST['department']);
    $stmt = $mysqli->prepare("INSERT INTO vehicles (vehicle_number, model, department) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $vno, $model, $dept);
    if($stmt->execute()) $success = "Vehicle added.";
    else $err = "Error: ".$mysqli->error;
    $stmt->close();
}
?>
<?php include("assets/inc/head.php"); ?>
<body><?php include("assets/inc/nav.php"); ?>

<div class="container mt-4">
  <h3>Vehicle Register</h3>
  <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
  <?php if($err) echo "<div class='alert alert-danger'>$err</div>"; ?>

  <div class="card-box">
    <form method="POST" class="form-inline">
      <input name="vehicle_number" class="form-control mr-2" placeholder="Vehicle No" required>
      <input name="model" class="form-control mr-2" placeholder="Model">
      <input name="department" class="form-control mr-2" placeholder="Department">
      <button class="btn btn-primary" name="add_vehicle">Add Vehicle</button>
    </form>
  </div>

  <div class="card-box mt-3">
    <h5>Vehicles</h5>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead><tr><th>Vehicle No</th><th>Model</th><th>Department</th></tr></thead>
        <tbody>
        <?php
        $res = $mysqli->query("SELECT * FROM vehicles ORDER BY vehicle_number");
        while($r = $res->fetch_assoc()){
          echo "<tr><td>".htmlentities($r['vehicle_number'])."</td><td>".htmlentities($r['model'])."</td><td>".htmlentities($r['department'])."</td></tr>";
        }
        ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>