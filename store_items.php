<?php
session_start();
include("assets/inc/config.php");
include("assets/inc/checklogins.php");
check_login();

$page_title = "Store Items";
$err = $success = "";

// Handle item registration
if(isset($_POST['save_item'])){
    $name = trim($_POST['item_name']);
    $category = $_POST['category_id'];
    $unit = $_POST['unit'];
    $sku = $_POST['sku'];

    $stmt = $mysqli->prepare("INSERT INTO items (item_name, category_id, unit, sku) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siss", $name, $category, $unit, $sku);

    if($stmt->execute()){
        $success = "Item registered successfully!";
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
        <h3>Store Items</h3>
        <p class="text-muted">Register tyres, diesel, engine oil, spare parts and other works materials.</p>
    </div>

    <?php if($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <?php if($err): ?>
        <div class="alert alert-danger"><?= $err ?></div>
    <?php endif; ?>

    <div class="card-box p-4">
        <h5 class="mb-3">Register New Item</h5>

        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Item Name</label>
                    <input name="item_name" class="form-control" required>
                </div>

                <div class="col-md-3 mb-3">
                    <label>Category</label>
                    <select name="category_id" class="form-control" required>
                        <?php 
                        $c = $mysqli->query("SELECT * FROM categories ORDER BY name");
                        while($cat = $c->fetch_assoc()){
                            echo "<option value='{$cat['category_id']}'>{$cat['name']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label>Unit</label>
                    <input name="unit" class="form-control" placeholder="pcs, litres, sets">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>SKU</label>
                    <input name="sku" class="form-control">
                </div>
            </div>

            <button class="btn btn-primary">Save Item</button>
        </form>
    </div>

    <div class="table-wrapper mt-4">
        <h5>Existing Items</h5>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th>SKU</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $q = $mysqli->query("
                    SELECT it.*, c.name AS category 
                    FROM items it 
                    LEFT JOIN categories c ON it.category_id = c.category_id
                    ORDER BY it.item_name
                ");

                while($row = $q->fetch_assoc()){
                    echo "
                    <tr>
                        <td>{$row['item_name']}</td>
                        <td>{$row['category']}</td>
                        <td>{$row['unit']}</td>
                        <td>{$row['sku']}</td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</div>
</div>

<?php include("assets/inc/footer.php"); ?>
</body>
</html>
