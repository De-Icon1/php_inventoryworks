<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogins.php');
check_login();

$err = $success = '';
if(isset($_POST['assign'])){
    $item = (int)$_POST['item_id']; // tyre item_id
    $vehicle = (int)$_POST['vehicle_id'];
    $notes = trim($_POST['notes']);
    $by = $_SESSION['doc_number'];

    $stmt = $mysqli->prepare("INSERT INTO tyre_assignment (item_id, vehicle_id, assigned_by, notes) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $item, $vehicle, $by, $notes);
    if($stmt->execute()) $success = "Tyre assigned.";
    else $err = "Error: ".$mysqli->error;
    $stmt->close();
}
?>
<?php include("assets/inc/head.php"); ?>
<body><?php include("assets/inc/nav.php"); ?>

<div class="container mt-4">
  <h3>Assign Tyre to Vehicle</h3>
  <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
  <?php if($err) echo "<div class='alert alert-danger'>$err</div>"; ?>

  <div class="card-box">
    <form method="POST">
      <div class="form-row">
        <div class="form-group col-md-5">
          <label>Tyre (Item)</label>
          <select name="item_id" class="form-control">
            <?php
            $res = $mysqli->query("SELECT item_id, item_name FROM items WHERE category_id = (SELECT category_id FROM categories WHERE name='Tyres' LIMIT 1)");
            while($r = $res->fetch_assoc()){
              echo "<option value='{$r['item_id']}'>".htmlentities($r['item_name'])."</option>";
            }
            ?>
          </select>
        </div>
        <div class="form-group col-md-5">
          <label>Vehicle</label>
          <select name="vehicle_id" class="form-control">
            <?php
            $res = $mysqli->query("SELECT vehicle_id, vehicle_number FROM vehicles ORDER BY vehicle_number");
            while($r = $res->fetch_assoc()){
              echo "<option value='{$r['vehicle_id']}'>".htmlentities($r['vehicle_number'])."</option>";
            }
            ?>
          </select>
        </div>
        <div class="form-group col-md-2">
          <label>&nbsp;</label><br>
          <button class="btn btn-info" name="assign">Assign</button>
        </div>
      </div>
      <div class="form-group">
        <label>Notes</label>
        <textarea name="notes" class="form-control"></textarea>
      </div>
    </form>
  </div>

  <div class="card-box mt-3">
    <h5>Assignments</h5>
    <div class="table-responsive">
      <table class="table table-striped">
      <thead><tr><th>When</th><th>Tyre</th><th>Vehicle</th><th>By</th><th>Notes</th></tr></thead>
      <tbody>
      <?php
      $sql = "SELECT ta.*, it.item_name, v.vehicle_number FROM tyre_assignment ta JOIN items it ON ta.item_id = it.item_id JOIN vehicles v ON ta.vehicle_id = v.vehicle_id ORDER BY ta.assigned_at DESC";
      $res = $mysqli->query($sql);
      while($r = $res->fetch_assoc()){
        echo "<tr><td>{$r['assigned_at']}</td><td>".htmlentities($r['item_name'])."</td><td>".htmlentities($r['vehicle_number'])."</td><td>".htmlentities($r['assigned_by'])."</td><td>".htmlentities($r['notes'])."</td></tr>";
      }
      ?>
      </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>