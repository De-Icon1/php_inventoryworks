<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogins.php');
check_login();

$err = $success = '';
if(isset($_POST['upload'])){
    if(!isset($_FILES['file'])){ $err = "No file uploaded"; }
    else {
        $tmp = $_FILES['file']['tmp_name'];
        if(($handle = fopen($tmp, "r")) !== FALSE){
            // Expect CSV: item_name,quantity
            $mysqli->begin_transaction();
            try {
                while(($data = fgetcsv($handle, 1000, ",")) !== FALSE){
                    if(count($data) < 2) continue;
                    $item_name = trim($data[0]);
                    $qty = (float)trim($data[1]);

                    // find item
                    $stmt = $mysqli->prepare("SELECT item_id FROM items WHERE item_name = ?");
                    $stmt->bind_param("s", $item_name);
                    $stmt->execute();
                    $stmt->bind_result($item_id);
                    $stmt->fetch();
                    $stmt->close();

                    if($item_id){
                        // insert entry & update balance
                        $stmt = $mysqli->prepare("INSERT INTO stock_entries (item_id, qty_in, reference, note, created_by) VALUES (?, ?, 'CSV Upload', ?, ?)");
                        $note = "CSV upload";
                        $by = $_SESSION['doc_number'];
                        $stmt->bind_param("idss", $item_id, $qty, $note, $by);
                        $stmt->execute();
                        $stmt->close();

                        $stmt = $mysqli->prepare("INSERT INTO stock_balance (item_id, quantity) VALUES (?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
                        $stmt->bind_param("id", $item_id, $qty);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
                fclose($handle);
                $mysqli->commit();
                $success = "CSV processed.";
            } catch(Exception $e){
                $mysqli->rollback();
                $err = "CSV error: " . $e->getMessage();
            }
        } else {
            $err = "Unable to open uploaded file.";
        }
    }
}
?>
<?php include("assets/inc/head.php"); ?>
<body><?php include("assets/inc/nav.php"); ?>

<div class="container mt-4">
  <h3>Upload Stock (CSV)</h3>
  <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
  <?php if($err) echo "<div class='alert alert-danger'>$err</div>"; ?>

  <div class="card-box">
    <form method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <input type="file" name="file" accept=".csv" class="form-control" required>
      </div>
      <button class="btn btn-primary" name="upload">Upload</button>
    </form>
    <p class="mt-2"><small>CSV format: <code>item_name,quantity</code></small></p>
  </div>
</div>
</body>
</html>