<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogins.php');
check_login();

$err = $success = '';
if(isset($_POST['log'])){
    $stype = $_POST['source_type'];
    $sname = trim($_POST['source_name']);
    $qty = (float)$_POST['quantity'];
    $meter = $_POST['meter_reading'] ?: null;
    $by = $_SESSION['doc_number'];

    $stmt = $mysqli->prepare("INSERT INTO diesel_log (source_type, source_name, quantity, meter_reading, logged_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdss", $stype, $sname, $qty, $meter, $by);
    if($stmt->execute()) $success = "Logged.";
    else $err = "Error: ".$mysqli->error;
    $stmt->close();
}
?>
<?php include("assets/inc/head.php"); ?>
<body><?php include("assets/inc/nav.php"); ?>

<div class="container mt-4">
  <h3>Diesel Consumption Log</h3>
  <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
  <?php if($err) echo "<div class='alert alert-danger'>$err</div>"; ?>

  <div class="card-box">
    <form method="POST" class="form-inline">
      <select name="source_type" class="form-control mr-2">
        <option>Generator</option><option>Vehicle</option><option>Other</option>
      </select>
      <input name="source_name" class="form-control mr-2" placeholder="Generator ID or Vehicle No" required>
      <input type="number" step="0.01" name="quantity" class="form-control mr-2" placeholder="Litres" required>
      <input type="number" name="meter_reading" class="form-control mr-2" placeholder="Meter">
      <button class="btn btn-dark" name="log">Log</button>
    </form>
  </div>

  <div class="card-box mt-3">
    <h5>Recent Logs</h5>
    <div class="table-responsive">
      <table class="table table-striped">
      <thead><tr><th>When</th><th>Source</th><th>Qty</th><th>Meter</th><th>By</th></tr></thead>
      <tbody>
      <?php
      $res = $mysqli->query("SELECT * FROM diesel_log ORDER BY logged_at DESC LIMIT 200");
      while($r = $res->fetch_assoc()){
        echo "<tr><td>{$r['logged_at']}</td><td>".htmlentities($r['source_type']." - ".$r['source_name'])."</td><td>{$r['quantity']}</td><td>{$r['meter_reading']}</td><td>".htmlentities($r['logged_by'])."</td></tr>";
      }
      ?>
      </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>