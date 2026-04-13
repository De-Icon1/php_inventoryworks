<?php
require_once 'assets/inc/config.php';

$sql = "ALTER TABLE tyre_assignment ADD COLUMN quantity INT DEFAULT 1 AFTER item_id";
if($mysqli->query($sql)) {
    echo "Column 'quantity' added successfully to tyre_assignment table.\n";
} else {
    echo "Error: " . $mysqli->error . "\n";
}
?>
