<?php
require_once 'assets/inc/config.php';

echo "Checking Electrical Category and Items...\n\n";

// Get Electrical category ID
$cat_result = $mysqli->query("SELECT category_id FROM categories WHERE name = 'Electrical'");
if($cat_result->num_rows == 0){
    echo "✗ Electrical category not found!\n";
    exit;
}
$cat = $cat_result->fetch_assoc();
$electrical_cat_id = $cat['category_id'];
echo "✓ Electrical category ID: {$electrical_cat_id}\n\n";

// Check for existing electrical items
$check = $mysqli->query("SELECT COUNT(*) as cnt FROM items WHERE category_id = {$electrical_cat_id}");
$row = $check->fetch_assoc();
$count = $row['cnt'];

echo "Current electrical items: {$count}\n\n";

if($count == 0){
    echo "Adding sample electrical items...\n";
    echo "========================================\n";
    
    $electrical_items = [
        ['name' => 'LED Bulb 15W', 'unit' => 'pieces'],
        ['name' => 'LED Bulb 20W', 'unit' => 'pieces'],
        ['name' => 'LED Bulb 30W', 'unit' => 'pieces'],
        ['name' => 'Fluorescent Tube 40W', 'unit' => 'pieces'],
        ['name' => 'Energy Saving Bulb 25W', 'unit' => 'pieces'],
        ['name' => 'Ceiling Fan', 'unit' => 'pieces'],
        ['name' => 'Wall Socket', 'unit' => 'pieces'],
        ['name' => 'Light Switch', 'unit' => 'pieces'],
        ['name' => 'Extension Cable 5m', 'unit' => 'pieces'],
        ['name' => 'Circuit Breaker 20A', 'unit' => 'pieces']
    ];
    
    $stmt = $mysqli->prepare("INSERT INTO items (item_name, category_id, unit, created_at) VALUES (?, ?, ?, NOW())");
    
    foreach($electrical_items as $item){
        $stmt->bind_param("sis", $item['name'], $electrical_cat_id, $item['unit']);
        $stmt->execute();
        echo "✓ Added: {$item['name']}\n";
    }
    $stmt->close();
    
    echo "\n✅ Sample electrical items added successfully!\n";
} else {
    echo "Existing electrical items:\n";
    echo "========================================\n";
    $result = $mysqli->query("SELECT item_name, unit FROM items WHERE category_id = {$electrical_cat_id} ORDER BY item_name");
    while($row = $result->fetch_assoc()){
        echo "- {$row['item_name']} ({$row['unit']})\n";
    }
}

echo "\n✅ Done!\n";
?>
