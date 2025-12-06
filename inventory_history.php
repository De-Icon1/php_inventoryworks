<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogins.php');
check_login();

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$where = "";
$params = [];
if($from && $to){
    $where = " WHERE issued_at BETWEEN ? AND ? ";
}
?>
<?php include("assets/inc/head.php"); ?>
<body><?php include("assets/inc/nav.php"); ?>

<div class="container mt-4">
  <h3>Issuance History</h3>

  <div class="card-box">
    <form method="GET" class="form-inline mb-3">
      <label class="mr-2">From</label>
      <input type="date" name="from" value="<?= htmlentities($from) ?>" class="form-control mr-2">
      <label class="mr-2">To</label>
      <input type="date" name="to" value="<?= htmlentities($to) ?>" class="form-control mr-2">
      <button class="btn btn-secondary">Filter</button>
    </form>

    <div class="table-responsive">
      <table class="table table-striped">
        <thead><tr><th>Date</th><th>Item</th><th>Unit</th><th>Qty</th><th>By</th><th>Purpose</th></tr></thead>
        <tbody>
        <?php
        if($from && $to){
            $stmt = $mysqli->prepare("SELECT si.issued_at, it.item_name, u.unit_name, si.quantity, si.issued_by, si.purpose FROM stock_issues si JOIN items it ON si.item_id = it.item_id JOIN units u ON si.unit_id = u.unit_id WHERE si.issued_at BETWEEN ? AND ? ORDER BY si.issued_at DESC");
            $stmt->bind_param("ss", $from, $to);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $res = $mysqli->query("SELECT si.issued_at, it.item_name, u.unit_name, si.quantity, si.issued_by, si.purpose FROM stock_issues si JOIN items it ON si.item_id = it.item_id JOIN units u ON si.unit_id = u.unit_id ORDER BY si.issued_at DESC LIMIT 200");
        }
        while($r = $res->fetch_assoc()){
          echo "<tr>";
          echo "<td>".htmlentities($r['issued_at'])."</td>";
          echo "<td>".htmlentities($r['item_name'])."</td>";
          echo "<td>".htmlentities($r['unit_name'])."</td>";
          echo "<td>".htmlentities($r['quantity'])."</td>";
          echo "<td>".htmlentities($r['issued_by'])."</td>";
          echo "<td>".htmlentities($r['purpose'])."</td>";
          echo "</tr>";
        }
        ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
</body>
</html>