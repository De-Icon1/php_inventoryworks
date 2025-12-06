<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogins.php');
check_login();
?>
<?php include("assets/inc/head.php"); ?>
<body><?php include("assets/inc/nav.php"); ?>

<div class="container mt-4">
  <h3>Inventory Stock Report</h3>
  <div class="card-box">
    <div class="table-responsive">
      <table class="table table-bordered">
        <thead><tr><th>Item</th><th>Category</th><th>Unit</th><th>Stock</th><th>Total Issued</th><th>Last Updated</th></tr></thead>
        <tbody>
        <?php
        $sql = "
          SELECT it.item_id, it.item_name, c.name AS category, it.unit, COALESCE(sb.quantity,0) AS stock, COALESCE(SUM(si.quantity),0) AS total_issued, sb.last_updated
          FROM items it
          LEFT JOIN categories c ON it.category_id = c.category_id
          LEFT JOIN stock_balance sb ON it.item_id = sb.item_id
          LEFT JOIN stock_issues si ON it.item_id = si.item_id
          GROUP BY it.item_id
          ORDER BY it.item_name
        ";
        $res = $mysqli->query($sql);
        while($r = $res->fetch_assoc()){
          echo "<tr>";
          echo "<td>".htmlentities($r['item_name'])."</td>";
          echo "<td>".htmlentities($r['category'])."</td>";
          echo "<td>".htmlentities($r['unit'])."</td>";
          echo "<td>".htmlentities($r['stock'])."</td>";
          echo "<td>".htmlentities($r['total_issued'])."</td>";
          echo "<td>".htmlentities($r['last_updated'])."</td>";
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