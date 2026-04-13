<?php
session_start();
include('assets/inc/config.php');

header('Content-Type: application/json');

$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

if($item_id <= 0) {
    echo json_encode(['success' => false, 'current_stock' => 0]);
    exit;
}

$stmt = $mysqli->prepare("SELECT COALESCE(quantity, 0) AS qty FROM stock_balance WHERE item_id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$stmt->bind_result($qty);
$stmt->fetch();
$stmt->close();

echo json_encode([
    'success' => true,
    'current_stock' => number_format($qty, 0)
]);
?>
