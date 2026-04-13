<?php
require_once 'assets/inc/config.php';

echo "Initializing stock balance for all items...\n\n";

// Get all items
$items = $mysqli->query("SELECT item_id, item_name FROM items");

while($item = $items->fetch_assoc()){
    // Check if stock_balance entry exists
    $check = $mysqli->query("SELECT balance_id FROM stock_balance WHERE item_id = {$item['item_id']}");
    
    if($check->num_rows == 0){
        // Insert initial stock balance with 0 quantity
        $mysqli->query("INSERT INTO stock_balance (item_id, quantity) VALUES ({$item['item_id']}, 0)");
        echo "✓ Initialized stock for: {$item['item_name']} (Qty: 0)\n";
    } else {
        echo "- Already exists: {$item['item_name']}\n";
    }
}

echo "\nDone! Now go to Stock Management > Adjust Stock tab to add quantities.\n";
?>
