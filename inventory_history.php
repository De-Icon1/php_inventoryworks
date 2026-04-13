<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogins.php');
check_login();

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$selected_unit = $_GET['unit_id'] ?? 'all';

$where = "";
$params = [];
if($from && $to){
    $where = " WHERE issued_at BETWEEN ? AND ? ";
}
?>
<?php include("assets/inc/head.php"); ?>
<body>
<?php include("assets/inc/nav.php"); ?>
<?php include("assets/inc/sidebar_admin.php"); ?>

<div class="content-page">
<div class="content container">
  <h3>Issuance History</h3>

  <div class="card-box">
    <?php $units_res = $mysqli->query("SELECT unit_id, unit_name FROM units WHERE unit_name != 'Vice Chancellor' ORDER BY unit_name"); ?>
    <form method="GET" class="form-inline mb-3">
      <label class="mr-2">From</label>
      <input type="date" name="from" value="<?= htmlentities($from) ?>" class="form-control mr-2">
      <label class="mr-2">To</label>
      <input type="date" name="to" value="<?= htmlentities($to) ?>" class="form-control mr-2">
      <label class="mr-2">Unit</label>
      <select name="unit_id" class="form-control mr-2">
        <option value="all">All Units</option>
        <?php while($u = $units_res->fetch_assoc()){ $sel = ($selected_unit != 'all' && (int)$selected_unit === (int)$u['unit_id']) ? 'selected' : ''; echo "<option value='{$u['unit_id']}' $sel>".htmlentities($u['unit_name'])."</option>"; } ?>
      </select>
      <button class="btn btn-secondary">Filter</button>
    </form>

    <div class="table-responsive">
      <table class="table table-striped">
        <thead><tr><th>Date</th><th>Item</th><th>Unit</th><th>Qty</th><th>By</th><th>Purpose</th></tr></thead>
        <tbody>
        <?php
        // Build query with optional date and unit filters
        $params = [];
        $types = '';
        $whereClauses = [];

        if($from && $to){
          $whereClauses[] = 'si.issued_at BETWEEN ? AND ?';
          $types .= 'ss';
          $params[] = $from;
          $params[] = $to;
        }
        if($selected_unit !== 'all'){
          $whereClauses[] = 'si.unit_id = ?';
          $types .= 'i';
          $params[] = (int)$selected_unit;
        }

        if(count($whereClauses) > 0){
          $where_sql = 'WHERE ' . implode(' AND ', $whereClauses);
          $stmt = $mysqli->prepare("SELECT si.issued_at, it.item_name, u.unit_name, si.quantity, si.issued_by, si.purpose FROM stock_issues si JOIN items it ON si.item_id = it.item_id JOIN units u ON si.unit_id = u.unit_id $where_sql ORDER BY si.issued_at DESC");
          if($types){
            $stmt->bind_param($types, ...$params);
          }
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
  </div>

<?php include("assets/inc/footer.php"); ?>

</body>
</html>