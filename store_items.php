<?php
session_start();
include("assets/inc/config.php");
include("assets/inc/checklogins.php");
check_login();

$page_title = "Store Items";
$err = $success = "";

// Handle item registration
if(isset($_POST['save_item'])){
	$name = trim($_POST['item_name'] ?? '');
	$category = (int) ($_POST['category_id'] ?? 0);
	$unit = trim($_POST['unit'] ?? '');

	$stmt = $mysqli->prepare("INSERT INTO items (item_name, category_id, unit) VALUES (?, ?, ?)");
	$stmt->bind_param("sis", $name, $category, $unit);

	if($stmt->execute()){
		$last_id = $mysqli->insert_id;
		$success = "Item registered successfully! (ID: " . $last_id . ")";
	} else {
		$err = "Error inserting item: " . $mysqli->error;
	}
}
?>

<?php include("assets/inc/head.php"); ?>
<body>
<?php include("assets/inc/nav.php"); ?>
<?php include("assets/inc/sidebar_admin.php"); ?>

<div class="content-page">
<div class="content container">

	<!-- Alerts -->
	<?php if($success): ?>
		<div class="alert alert-success"><?= $success ?></div>
	<?php endif; ?>

	<?php if($err): ?>
		<div class="alert alert-danger"><?= $err ?></div>
	<?php endif; ?>

	<!-- Register Item Form -->
	<div class="card-box p-4">
		<h5 class="mb-3">Register New Item</h5>

		<form method="POST">
			<div class="row">
				<div class="col-12 col-md-6 mb-3">
					<label>Item Name</label>
					<input name="item_name" class="form-control" required>
				</div>

				<div class="col-12 col-md-3 mb-3">
					<label>Category</label>
					<select name="category_id" class="form-control" required>
						<?php 
						$c = $mysqli->query("SELECT * FROM categories ORDER BY name");
						while($cat = $c->fetch_assoc()){
							echo "<option value='" . intval($cat['category_id']) . "'>" . htmlspecialchars($cat['name']) . "</option>";
						}
						?>
					</select>
				</div>

				<div class="col-12 col-md-3 mb-3">
					<label>Unit</label>
					<input name="unit" class="form-control" placeholder="pcs, litres, sets" required>
				</div>
			</div>

			<!-- Save Button -->
			<button type="submit" name="save_item" class="btn btn-primary mt-2">Save Item</button>

		</form>
	</div>

	<!-- Items Table -->
	<div class="table-wrapper mt-4">
		<h5>Existing Items</h5>

		<table class="table table-bordered">
			<thead>
				<tr>
					<th>Item</th>
					<th>Category</th>
					<th>Unit</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$q = $mysqli->query(
					"SELECT it.*, c.name AS category FROM items it LEFT JOIN categories c ON it.category_id = c.category_id ORDER BY it.item_name"
				);

				if($q === false){
					echo '<tr><td colspan="3" class="text-danger">Query error: ' . htmlspecialchars($mysqli->error) . '</td></tr>';
				} else {
					if($q->num_rows === 0){
						echo '<tr><td colspan="3">No items found.</td></tr>';
					} else {
						while($row = $q->fetch_assoc()){
							echo '<tr>';
							echo '<td>' . htmlspecialchars($row['item_name']) . '</td>';
							echo '<td>' . htmlspecialchars($row['category']) . '</td>';
							echo '<td>' . htmlspecialchars($row['unit']) . '</td>';
							echo '</tr>';
						}
					}
				}
				?>
			</tbody>
		</table>
	</div>

	<?php
	// Optional debug panel: show last 20 rows and counts when ?debug=1 is set
	if(isset($_GET['debug']) && $_GET['debug'] == '1'){
		echo '<div class="container mt-4"><div class="card"><div class="card-body"><h5>Debug: Last 20 items</h5>';

		$dq = $mysqli->query("SELECT * FROM items ORDER BY item_id DESC LIMIT 20");
		if($dq === false){
			echo '<div class="text-danger">Debug query error: ' . htmlspecialchars($mysqli->error) . '</div>';
		} else {
			echo '<div>Items in DB (last 20): <strong>' . intval($dq->num_rows) . '</strong></div>';
			echo '<table class="table table-sm"><thead><tr><th>item_id</th><th>item_name</th><th>category_id</th><th>unit</th></tr></thead><tbody>';
			while($r = $dq->fetch_assoc()){
				echo '<tr>';
				echo '<td>' . intval($r['item_id']) . '</td>';
				echo '<td>' . htmlspecialchars($r['item_name']) . '</td>';
				echo '<td>' . htmlspecialchars($r['category_id']) . '</td>';
				echo '<td>' . htmlspecialchars($r['unit']) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		}

		echo '</div></div></div>';
	}
	?>

<?php include("assets/inc/footer.php"); ?>
</div>
</div>

</body>
</html>
