<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogins.php');
check_login();

// summary by source type
$sql = "SELECT source_type, SUM(quantity) AS total_qty FROM diesel_log GROUP BY source_type";
$res = $mysqli->query($sql);
?>
<?php include("assets/inc/head.php"); ?>
<body><?php include("assets/inc/nav.php"); ?>

<div class="container mt-4">
  <h3>Diesel Usage Report</h3>
  <div class="card-box">
    <div class="table-responsive">
      <table class="table table-striped">
      <thead><tr><th>Source Type</th><th>Total (Litres)</th></tr></thead>
      <tbody>
      <?php while($r = $res->fetch_assoc()){ echo "<tr><td>{$r['source_type']}</td><td>{$r['total_qty']}</td></tr>"; } ?>
      </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>