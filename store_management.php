<?php
session_start();
include("assets/inc/config.php");
include("assets/inc/checklogins.php");
check_login();

$page_title = "Stock Management";
$err = $success = "";

// Handle Stock Update
if(isset($_POST['update_stock'])){
    $item_id   = $_POST['item_id'];
    $qty_in    = $_POST['quantity'];
    $reference = trim($_POST['reference']);
    $note      = trim($_POST['note']);
    $by        = $_SESSION['username'];

    // 1. Insert into stock_entries
    $stmt = $mysqli->prepare("
        INSERT INTO stock_entries (item_id, qty_in, reference, note, created_by)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("idsss", $item_id, $qty_in, $reference, $note, $by);

    if($stmt->execute()){

        // 2. Update stock balance (upsert)
        $stmt2 = $mysqli->prepare("
            INSERT INTO stock_balance (item_id, quantity)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ");
        $stmt2->bind_param("id", $item_id, $qty_in);
        $stmt2->execute();

        $success = "Stock updated successfully!";
    } else {
        $err = "Error: " . $mysqli->error;
    }
}
?>

<?php include("assets/inc/head.php"); ?>
<body>

<?php include("assets/inc/nav.php"); ?>
<?php include("assets/inc/sidebar_admin.php"); ?>

<div class="content-page">
<div class="content container">

    <div class="page-title-box">
        <h3>Stock Management</h3>
        <p class="text-muted">Add new stock (GRN/Invoice) and track current balances.</p>
    </div>

    <?php if($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <?php if($err): ?>
        <div class="alert alert-danger"><?= $err ?></div>
    <?php endif; ?>


    <!-- STOCK ENTRY FORM -->
    <div class="card-box p-4">
        <h5 class="mb-3">Add Stock</h5>

        <form method="POST">
            <div class="row">

                <div class="col-md-6 mb-3">
                    <label>Item</label>
                    <select name="item_id" class="form-control" required>
                        <?php
                        $items = $mysqli->query("SELECT * FROM items ORDER BY item_name");
                        while($i = $items->fetch_assoc()){
                            echo "<option value='{$i['item_id']}'>{$i['item_name']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label>Quantity</label>
                    <input type="number" step="0.01" name="quantity" class="form-control" required>
                </div>

                <div class="col-md-3 mb-3">
                    <label>Reference (Invoice/GRN)</label>
                    <input name="reference" class="form-control" placeholder="e.g. INV-2025-0021">
                </div>
            </div>

            <div class="mb-3">
                <label>Note</label>
                <textarea name="note" class="form-control"></textarea>
            </div>

            <button class="btn btn-success" name="update_stock">Update Stock</button>
        </form>
    </div>



    <!-- CURRENT BALANCE TABLE -->
    <div class="table-wrapper">

        <h5 class="mb-3">Current Stock Balances</h5>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">

                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Unit</th>
                        <th>Quantity</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $q = $mysqli->query("
                        SELECT sb.quantity, sb.last_updated, it.item_name, it.unit, c.name AS cat
                        FROM stock_balance sb
                        JOIN items it ON sb.item_id = it.item_id
                        LEFT JOIN categories c ON it.category_id = c.category_id
                        ORDER BY it.item_name
                    ");

                    while($row = $q->fetch_assoc()){
                        echo "
                        <tr>
                            <td>{$row['item_name']}</td>
                            <td>{$row['cat']}</td>
                            <td>{$row['unit']}</td>
                            <td>{$row['quantity']}</td>
                            <td>{$row['last_updated']}</td>
                        </tr>";
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
