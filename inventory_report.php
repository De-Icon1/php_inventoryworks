<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogins.php');
check_login();
?>
<?php include("assets/inc/head.php"); ?>
<body>
<?php include("assets/inc/nav.php"); ?>
<?php include("assets/inc/sidebar_admin.php"); ?>

<div class="content-page">
<div class="content container">
  <h3>Inventory Stock Report</h3>
  <div class="card-box">
    <?php
    // Prepare units for filtering (exclude Vice Chancellor)
    $units_res = $mysqli->query("SELECT unit_id, unit_name FROM units WHERE unit_name != 'Vice Chancellor' ORDER BY unit_name");
    $selected_unit = $_GET['unit_id'] ?? 'all';
    ?>
    <form method="GET" class="form-inline mb-3">
      <label class="mr-2">Unit</label>
      <select name="unit_id" class="form-control mr-2">
        <option value="all">All Units</option>
        <?php while($u = $units_res->fetch_assoc()){ $sel = ($selected_unit != 'all' && (int)$selected_unit === (int)$u['unit_id']) ? 'selected' : ''; echo "<option value='{$u['unit_id']}' $sel>".htmlentities($u['unit_name'])."</option>"; } ?>
      </select>
      <button class="btn btn-secondary">Filter</button>
    </form>
    <div class="table-responsive">
      <table class="table table-bordered">
        <thead><tr><th>Item</th><th>Category</th><th>Unit/Quantity</th><th>Current Stock</th><th>Issued To Selected Unit</th><th>Total Issued</th><th>Last Updated</th></tr></thead>
        <tbody>
        <?php
        // Build query; include issued-to-selected-unit as subquery
        $unit_filter = ($selected_unit !== 'all') ? (int)$selected_unit : null;
        $sql_all = "SELECT it.item_id, it.item_name, c.name AS category, it.unit, COALESCE(sb.quantity,0) AS stock, COALESCE(SUM(si.quantity),0) AS total_issued, sb.last_updated FROM items it LEFT JOIN categories c ON it.category_id = c.category_id LEFT JOIN stock_balance sb ON it.item_id = sb.item_id LEFT JOIN stock_issues si ON it.item_id = si.item_id GROUP BY it.item_id ORDER BY it.item_name";

        if($unit_filter){
          // Only include items that have been issued to the selected unit
          $stmt_items = $mysqli->prepare(
            "SELECT it.item_id, it.item_name, c.name AS category, it.unit, COALESCE(sb.quantity,0) AS stock, COALESCE(SUM(si.quantity),0) AS total_issued, sb.last_updated
             FROM items it
             LEFT JOIN categories c ON it.category_id = c.category_id
             LEFT JOIN stock_balance sb ON it.item_id = sb.item_id
             LEFT JOIN stock_issues si ON it.item_id = si.item_id
             WHERE it.item_id IN (SELECT DISTINCT item_id FROM stock_issues WHERE unit_id = ?)
             GROUP BY it.item_id
             ORDER BY it.item_name"
          );
          $stmt_items->bind_param('i', $unit_filter);
          $stmt_items->execute();
          $res = $stmt_items->get_result();
        } else {
          $res = $mysqli->query($sql_all);
        }
        while($r = $res->fetch_assoc()){
          // Remove decimal points for whole numbers
          $stock_display = number_format($r['stock'], 0);
          $issued_display = number_format($r['total_issued'], 0);
          // compute issued_to_selected_unit
          if($unit_filter){
            $stmt = $mysqli->prepare("SELECT COALESCE(SUM(quantity),0) as issued_to_unit FROM stock_issues WHERE item_id = ? AND unit_id = ?");
            $stmt->bind_param('ii', $r['item_id'], $unit_filter);
            $stmt->execute();
            $issued_row = $stmt->get_result()->fetch_assoc();
            $issued_to_unit = (int)$issued_row['issued_to_unit'];
            $stmt->close();
          } else {
            // sum for all units
            $issued_to_unit = (int)$r['total_issued'];
          }

          echo "<tr>";
          echo "<td>".htmlentities($r['item_name'])."</td>";
          echo "<td>".htmlentities($r['category'])."</td>";
          echo "<td>".htmlentities($r['unit'])."</td>";
          echo "<td>".$stock_display."</td>";
          echo "<td>".number_format($issued_to_unit,0)."</td>";
          echo "<td>".$issued_display."</td>";
          echo "<td>".($r['last_updated'] ? htmlentities($r['last_updated']) : 'N/A')."</td>";
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