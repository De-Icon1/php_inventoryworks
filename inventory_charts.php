<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogins.php');
check_login();

// prepare last 12 months labels
$labels = [];
$issued_data = [];
$diesel_data = [];

for($i = 11; $i >= 0; $i--){
    $ym = date('Y-m', strtotime("-{$i} months"));
    $labels[] = $ym;

    // issued
    $stmt = $mysqli->prepare("SELECT COALESCE(SUM(quantity),0) FROM stock_issues WHERE DATE_FORMAT(issued_at, '%Y-%m') = ?");
    $stmt->bind_param("s", $ym);
    $stmt->execute();
    $stmt->bind_result($issued_total);
    $stmt->fetch();
    $stmt->close();
    $issued_data[] = (float)$issued_total;

    // diesel
    $stmt = $mysqli->prepare("SELECT COALESCE(SUM(quantity),0) FROM diesel_log WHERE DATE_FORMAT(logged_at, '%Y-%m') = ?");
    $stmt->bind_param("s", $ym);
    $stmt->execute();
    $stmt->bind_result($diesel_total);
    $stmt->fetch();
    $stmt->close();
    $diesel_data[] = (float)$diesel_total;
}
?>
<?php include("assets/inc/head.php"); ?>
<body><?php include("assets/inc/nav.php"); ?>
<div class="container mt-4">
<?php include("assets/inc/sidebar_admin.php"); ?>
<div class="content-page">
<div class="content container">
  <h3>Usage Charts</h3>

  <div class="row">
    <div class="col-12 col-lg-6 mb-3">
      <div class="card-box">
        <h5>Items Issued (last 12 months)</h5>
        <canvas id="issuedChart" height="200"></canvas>
      </div>
    </div>
    <div class="col-12 col-lg-6 mb-3">
      <div class="card-box">
        <h5>Diesel Consumption (last 12 months)</h5>
        <canvas id="dieselChart" height="200"></canvas>
      </div>
    </div>
  </div>
</div>
</div>

<?php include("assets/inc/footer.php"); ?>

</body>
</html>
<script>
const labels = <?= json_encode($labels) ?>;
const issued = <?= json_encode($issued_data) ?>;
const diesel = <?= json_encode($diesel_data) ?>;

new Chart(document.getElementById('issuedChart'), {
  type: 'line',
  data: { labels: labels, datasets: [{ label: 'Issued', data: issued, fill:false, tension:0.2 }] }
});

new Chart(document.getElementById('dieselChart'), {
  type: 'bar',
  data: { labels: labels, datasets: [{ label: 'Diesel (L)', data: diesel }] }
});
</script>
</body>
</html>